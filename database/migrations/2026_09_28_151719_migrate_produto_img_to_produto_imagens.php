<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('produtos', 'img')) {
            return;
        }

        DB::table('produtos')
            ->select('id', 'img')
            ->whereNotNull('img')
            ->where('img', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($produtos) {
                foreach ($produtos as $produto) {
                    $imagemExistente = DB::table('produto_imagens')
                        ->where('produto_id', $produto->id)
                        ->where('caminho', $produto->img)
                        ->first();

                    $imagens = DB::table('produto_imagens')
                        ->where('produto_id', $produto->id)
                        ->orderBy('ordem')
                        ->orderBy('id')
                        ->get();

                    $ordem = 1;

                    foreach ($imagens as $imagem) {
                        if (
                            $imagemExistente
                            && $imagem->id === $imagemExistente->id
                        ) {
                            continue;
                        }

                        DB::table('produto_imagens')
                            ->where('id', $imagem->id)
                            ->update([
                                'ordem' => $ordem,
                            ]);

                        $ordem++;
                    }

                    if ($imagemExistente) {
                        DB::table('produto_imagens')
                            ->where('id', $imagemExistente->id)
                            ->update([
                                'ordem' => 0,
                            ]);

                        continue;
                    }

                    DB::table('produto_imagens')->insert([
                        'produto_id' => $produto->id,
                        'caminho' => $produto->img,
                        'ordem' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('img');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('produtos', 'img')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->string('img')
                    ->nullable()
                    ->after('preco_uni');
            });
        }

        DB::table('produtos')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($produtos) {
                foreach ($produtos as $produto) {
                    $primeiraImagem = DB::table('produto_imagens')
                        ->where('produto_id', $produto->id)
                        ->orderBy('ordem')
                        ->orderBy('id')
                        ->first();

                    DB::table('produtos')
                        ->where('id', $produto->id)
                        ->update([
                            'img' => $primeiraImagem?->caminho,
                        ]);
                }
            });
    }
};
