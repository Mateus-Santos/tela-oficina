<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->string('tipo', 50)
                ->default('outro')
                ->nullable()
                ->change();

            $table->string('anexavel_type')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('anexavel_id')
                ->nullable()
                ->change();

            $table->text('observacoes')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->string('tipo', 50)
                ->default('outro')
                ->nullable(false)
                ->change();

            $table->string('anexavel_type')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('anexavel_id')
                ->nullable(false)
                ->change();

            $table->text('observacoes')
                ->nullable()
                ->change();
        });
    }
};
