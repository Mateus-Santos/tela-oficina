<?php

namespace Database\Seeders;

use App\Models\CategoriaFinanceira;
use Illuminate\Database\Seeder;

class CategoriaFinanceiraSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nome' => 'VENDAS E SERVIÇOS',
                'tipo' => 'entrada',
                'ativo' => true,
            ],
            [
                'nome' => 'VENDAS',
                'tipo' => 'entrada',
                'ativo' => true,
            ],
            [
                'nome' => 'SERVIÇOS',
                'tipo' => 'entrada',
                'ativo' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaFinanceira::updateOrCreate(
                [
                    'nome' => $categoria['nome'],
                    'tipo' => $categoria['tipo'],
                ],
                [
                    'ativo' => $categoria['ativo'],
                ]
            );
        }
    }
}
