<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('chave_nf_cancelada', 44)->nullable()->after('chave_nf');
        });
        // Migra SOMENTE compras ja canceladas; preserva chave original em coluna auditavel.
        DB::table('compras')->where('status', 'cancelada')
            ->whereNotNull('chave_nf')->orderBy('id')
            ->chunkById(200, function ($linhas): void {
                foreach ($linhas as $linha) {
                    DB::table('compras')->where('id', $linha->id)->where('status', 'cancelada')
                        ->update(['chave_nf_cancelada' => $linha->chave_nf, 'chave_nf' => null]);
                }
            });
    }

    public function down(): void
    {
        // Nao descartar chaves arquivadas nem restaurar duplicidades.
        $arquivo = DB::table('compras')->whereNotNull('chave_nf_cancelada')->get(['id','chave_nf_cancelada']);
        $chaves = $arquivo->pluck('chave_nf_cancelada');
        if ($chaves->count() !== $chaves->unique()->count()) {
            throw new RuntimeException('Rollback proibido: varias compras canceladas compartilham chave NF-e arquivada.');
        }
        foreach ($arquivo as $linha) {
            if (DB::table('compras')->where('chave_nf', $linha->chave_nf_cancelada)->exists()) {
                throw new RuntimeException('Rollback proibido: chave NF-e ja reutilizada por outra compra.');
            }
        }
        foreach ($arquivo as $linha) {
            DB::table('compras')->where('id', $linha->id)->update([
                'chave_nf' => $linha->chave_nf_cancelada,
                'chave_nf_cancelada' => null,
            ]);
        }
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn('chave_nf_cancelada');
        });
    }
};
