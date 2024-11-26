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
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
            $table->boolean('status')->default(true); //1: Ativado, 0: Bloqueado;
            $table->boolean('cliente')->default(true); //1: É cliente, 0: Não é cliente;
            $table->boolean('colaborador')->default(false); //1: É colaborador, 0: Não é Colaborador;
            $table->integer('permitions')->default(false); //1: É colaborador, 0: Não é Colaborador;
            $table->boolean('google_id')->nullable();
            $table->string('cpf', 11)->nullable();
            $table->string('rg', 13)->nullable();
            $table->date('data_nascimento');
            $table->string('telefone_1', 11);
            $table->string('telefone_2', 11)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
