<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recebimentos', function (Blueprint $table) {
            $table->dateTime('estornado_em')
                ->nullable()
                ->after('observacoes');

            $table->text('motivo_estorno')
                ->nullable()
                ->after('estornado_em');

            $table->index('estornado_em');
        });
    }

    public function down(): void
    {
        Schema::table('recebimentos', function (Blueprint $table) {
            $table->dropIndex([
                'estornado_em',
            ]);

            $table->dropColumn([
                'estornado_em',
                'motivo_estorno',
            ]);
        });
    }
};
