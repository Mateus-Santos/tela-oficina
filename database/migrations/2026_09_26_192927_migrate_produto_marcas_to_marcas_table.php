<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $produtos = DB::table('produtos')
            ->select('id', 'marca')
            ->orderBy('id')
            ->get();

        foreach ($produtos as $produto) {
            $nomeMarca = trim((string) $produto->marca);

            if ($nomeMarca === '') {
                throw new \RuntimeException(
                    "Produto #{$produto->id} possui marca vazia."
                );
            }

            $marca = DB::table('marcas')
                ->whereRaw('UPPER(TRIM(nome)) = UPPER(?)', [$nomeMarca])
                ->first();

            if (!$marca) {
                $marcaId = DB::table('marcas')->insertGetId([
                    'nome' => $nomeMarca,
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $marcaId = $marca->id;
            }

            DB::table('produtos')
                ->where('id', $produto->id)
                ->update([
                    'marca_id' => $marcaId,
                ]);
        }

        $semMarca = DB::table('produtos')
            ->whereNull('marca_id')
            ->count();

        if ($semMarca > 0) {
            throw new \RuntimeException(
                "A migração terminou com {$semMarca} produto(s) sem marca_id."
            );
        }
    }

    public function down(): void
    {
        DB::table('produtos')->update([
            'marca_id' => null,
        ]);

        DB::table('marcas')->delete();
    }
};
