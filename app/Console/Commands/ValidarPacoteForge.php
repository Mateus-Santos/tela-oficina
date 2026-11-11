<?php

namespace App\Console\Commands;

use App\Services\Forge\ValidarPacoteForge as Validador;
use Illuminate\Console\Command;
use Throwable;

class ValidarPacoteForge extends Command
{
    protected $signature = 'forge:validar-pacote {pacote : Nome do diretorio pronto-* na area privada}';

    protected $description = 'Valida integridade e relacoes de pacote Forge sem escrever no banco';

    public function handle(Validador $validador): int
    {
        try {
            $resultado = $validador->validar(
                storage_path('app/forge-v270'),
                (string) $this->argument('pacote')
            );

            $this->info('Pacote aprovado estruturalmente (nao importado).');
            $this->line('Pacote: '.$resultado['pacote']);
            $this->line('Formato: '.$resultado['formato']);
            $this->line('SHA-256 manifesto: '.$resultado['manifesto_sha256']);
            $this->table(
                ['Arquivo', 'Linhas', 'Bytes'],
                array_map(
                    static fn (string $nome, array $dados): array => [
                        $nome, $dados['linhas'], $dados['bytes'],
                    ],
                    array_keys($resultado['arquivos']),
                    array_values($resultado['arquivos'])
                )
            );
            $this->table(
                ['Relacao', 'Total'],
                array_map(
                    static fn (string $nome, int $valor): array => [$nome, $valor],
                    array_keys($resultado['relacoes']),
                    array_values($resultado['relacoes'])
                )
            );
            foreach ($resultado['alertas'] as $alerta) {
                $this->warn($alerta);
            }
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Pacote reprovado: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
