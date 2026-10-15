<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('perfil', 30)
                ->default('cliente')
                ->after('status');
        });

        /*
         * Preserva o comportamento de autorização atual.
         *
         * Atualmente qualquer usuário com:
         * - permitions = 1
         * OU
         * - colaborador = 1
         *
         * possui comportamento administrativo em alguma
         * parte do sistema.
         */
        DB::table('users')
            ->where(function ($query) {
                $query
                    ->where('permitions', 1)
                    ->orWhere('colaborador', true);
            })
            ->update([
                'perfil' => 'admin',
            ]);

        DB::table('users')
            ->whereNotIn('perfil', [
                'admin',
            ])
            ->update([
                'perfil' => 'cliente',
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('perfil');
        });
    }
};
