<?php

namespace App\Console\Commands;

use App\Services\Forge\PrepararStagingForge as Preparador;
use Illuminate\Console\Command;
use Throwable;

final class PrepararStagingForge extends Command
{
    protected $signature = 'forge:preparar-staging
        {pacote : Nome pronto-* de um pacote Forge}
        {--origem= : Caminho da pasta data/transferencias/laravel do Forge}';

    protected $description = 'Copia pacote Forge para staging privado e o revalida (sem MySQL)';

    public function handle(Preparador $preparador): int
    {
        $origem = trim((string) $this->option('origem'));
        if ($origem === '') {
            $this->error('Informe --origem=/caminho/transferencias/laravel.');
            return self::FAILURE;
        }

        try {
            $resumo = $preparador->preparar(
                $origem,
                (string) $this->argument('pacote'),
                storage_path('app/forge-v270')
            );
            $this->info('Copia publicada e revalidada. Nenhuma importacao foi executada.');
            $this->line('Pacote: '.$resumo['pacote']);
            $this->line('Destino: '.$resumo['destino']);
            $this->line('Manifesto SHA-256: '.$resumo['manifesto_sha256']);
            $this->line('CSVs: '.$resumo['arquivos'].' | Bytes: '.$resumo['bytes']);
            foreach ($resumo['alertas'] as $alerta) {
                $this->warn($alerta);
            }
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Staging reprovado: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
