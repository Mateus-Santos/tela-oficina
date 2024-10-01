<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->boolean('status')->default(true); //1: Ativado, 0: Desativado
            $table->boolean('cliente')->default(true); //1: Ativado, 0: Desativado
            $table->boolean('colaborador')->default(false); //1: Ativado, 0: Desativado
            $table->boolean('google_id')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
