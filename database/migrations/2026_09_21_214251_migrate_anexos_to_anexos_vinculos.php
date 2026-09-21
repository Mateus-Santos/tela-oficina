<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('anexos')
            ->orderBy('id')
            ->eachById(function (object $anexo): void {
                DB::table('anexos_vinculos')->insert([
                    'anexo_id' => $anexo->id,
                    'vinculavel_type' => $anexo->anexavel_type,
                    'vinculavel_id' => $anexo->anexavel_id,
                    'tipo' => $anexo->tipo,
                    'observacoes' => $anexo->observacoes,
                    'created_at' => $anexo->created_at,
                    'updated_at' => $anexo->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('anexos_vinculos')
            ->whereIn(
                'anexo_id',
                DB::table('anexos')->pluck('id')
            )
            ->delete();
    }
};
