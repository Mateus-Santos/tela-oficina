<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\SetorServico;

class SetorSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/setor_servico.csv');

        if (!file_exists($path)) {
            $this->command->error('Arquivo setor_servico.csv não encontrado.');
            return;
        }

        $csv = fopen($path, 'r');

        // Pula cabeçalho
        fgetcsv($csv, 0, ';');

        while (($data = fgetcsv($csv, 0, ';')) !== false) {

            if (count($data) < 2) {
                continue;
            }

            [$setor, $nivel] = $data;

            Log::info("Importando setor: $setor - Nível: $nivel");

            SetorServico::firstOrCreate(
                ['setor' => $setor],
                ['nivel' => $nivel]
            );
        }

        fclose($csv);

        $this->command->info('Importação de setores concluída.');
    }
}