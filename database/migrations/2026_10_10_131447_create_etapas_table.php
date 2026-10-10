<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();

            $table->string(
                'nome',
                100
            );

            $table->string(
                'slug',
                120
            )->unique();

            $table->string(
                'descricao',
                255
            )->nullable();

            $table->string(
                'cor',
                30
            )->default('secondary');

            $table->unsignedInteger(
                'ordem'
            )->default(0);

            $table->enum(
                'tipo',
                [
                    'inicial',
                    'normal',
                    'final',
                ]
            )->default('normal');

            $table->boolean(
                'aplica_nota'
            )->default(true);

            $table->boolean(
                'aplica_ordem_servico'
            )->default(true);

            $table->boolean(
                'ativo'
            )->default(true);

            $table->timestamps();

            $table->index([
                'ativo',
                'ordem',
            ]);

            $table->index([
                'aplica_nota',
                'ativo',
                'ordem',
            ]);

            $table->index([
                'aplica_ordem_servico',
                'ativo',
                'ordem',
            ]);

            $table->index([
                'tipo',
                'ativo',
            ]);
        });

        $agora = now();

        $etapas = [
            [
                'nome' => 'Recepção',

                'descricao' => 'Etapa inicial do atendimento.',

                'cor' => 'secondary',

                'ordem' => 10,

                'tipo' => 'inicial',
            ],

            [
                'nome' => 'Diagnóstico',

                'descricao' => 'Veículo ou serviço em diagnóstico.',

                'cor' => 'info',

                'ordem' => 20,

                'tipo' => 'normal',
            ],

            [
                'nome' => 'Aguardando aprovação',

                'descricao' => 'Aguardando aprovação do cliente.',

                'cor' => 'warning',

                'ordem' => 30,

                'tipo' => 'normal',
            ],

            [
                'nome' => 'Aguardando peças',

                'descricao' => 'Serviço aguardando peças ou materiais.',

                'cor' => 'primary',

                'ordem' => 40,

                'tipo' => 'normal',
            ],

            [
                'nome' => 'Em manutenção',

                'descricao' => 'Serviço em execução.',

                'cor' => 'primary',

                'ordem' => 50,

                'tipo' => 'normal',
            ],

            [
                'nome' => 'Teste / conferência',

                'descricao' => 'Serviço em teste ou conferência final.',

                'cor' => 'info',

                'ordem' => 60,

                'tipo' => 'normal',
            ],

            [
                'nome' => 'Pronto para entrega',

                'descricao' => 'Etapa operacional final.',

                'cor' => 'success',

                'ordem' => 70,

                'tipo' => 'final',
            ],
        ];

        foreach ($etapas as $etapa) {
            DB::table('etapas')->insert([
                'nome' => $etapa['nome'],

                'slug' => Str::slug(
                    $etapa['nome']
                ),

                'descricao' => $etapa['descricao'],

                'cor' => $etapa['cor'],

                'ordem' => $etapa['ordem'],

                'tipo' => $etapa['tipo'],

                'aplica_nota' => true,

                'aplica_ordem_servico' => true,

                'ativo' => true,

                'created_at' => $agora,

                'updated_at' => $agora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('etapas');
    }
};
