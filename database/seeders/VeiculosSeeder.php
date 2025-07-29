<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;
use App\Models\Montadora;
use App\Models\Veiculo;

class VeiculosSeeder extends Seeder
{
    public function run()
    {
        $csv = fopen(storage_path('app/veiculos.csv'), 'r');
        $header = fgetcsv($csv, 0, ';'); // Pula o cabeçalho

        while (($data = fgetcsv($csv, 0, ';')) !== false) {

            if (count($data) < 3) {
                continue;
            }

            [$nomeVeiculo, $nomeMontadora, $montadora_id] = $data;

            // Log de debug
            \Log::info("Importando: $nomeVeiculo - $nomeMontadora - $montadora_id");

            $montadora = Montadora::firstOrCreate(
                ['id' => $montadora_id],
                ['nome' => $nomeMontadora]
            );

            Veiculo::firstOrCreate([
                'nome' => $nomeVeiculo,
                'montadora_id' => $montadora->id
            ]);
        }

        fclose($csv);
    }

}
