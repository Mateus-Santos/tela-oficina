<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo',
                'anexavel_type',
                'anexavel_id',
                'observacoes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->string('tipo', 50)
                ->default('outro')
                ->nullable();

            $table->string('anexavel_type')
                ->nullable();

            $table->unsignedBigInteger('anexavel_id')
                ->nullable();

            $table->text('observacoes')
                ->nullable();
        });
    }
};
