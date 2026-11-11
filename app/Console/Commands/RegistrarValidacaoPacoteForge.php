<?php

namespace App\Console\Commands;

use App\Services\Forge\RelatoriosValidacaoForge;
use App\Services\Forge\ValidarPacoteForge;
use Illuminate\Console\Command;
use Throwable;

final class RegistrarValidacaoPacoteForge extends Command
{
    protected $signature = 'forge:validar-e-registrar {pacote : Nome pronto-* dentro do staging privado}';
    protected $description = 'Valida pacote Forge e registra relatorio privado; sem importacao nem acesso ao MySQL';

    public function handle(ValidarPacoteForge $validador, RelatoriosValidacaoForge $relatorios): int
    {
        $nome = (string) $this->argument('pacote');
        $raiz = (string) config('forge.staging_root');

        if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $nome)) {
            $this->error('Nome de pacote invalido.');
            return self::FAILURE;
        }

        try {
            $validacao = $validador->validar($raiz, $nome);
        } catch (Throwable $e) {
            $this->error('Pacote reprovado: '.$e->getMessage());
            try {
                $relatorios->registrar($raiz, $nome, null);
                $this->warn('Resultado reprovado registrado no historico privado.');
            } catch (Throwable $registro) {
                $this->error('Nao foi possivel registrar a reprovacao: '.$registro->getMessage());
            }
            return self::FAILURE;
        }

        try {
            $relatorios->registrar($raiz, $nome, $validacao);
        } catch (Throwable $e) {
            $this->error('Integridade aprovada, mas o registro do relatorio falhou: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info('Pacote validado e relatorio privado registrado.');
        $this->line('Pacote: '.$nome);
        $this->line('Manifesto SHA-256: '.$validacao['manifesto_sha256']);
        $this->line('CSVs: '.count($validacao['arquivos']));
        $this->warn('Resultado historico; revalidar antes de qualquer importacao futura.');
        return self::SUCCESS;
    }
}
