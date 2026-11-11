<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_cadastros_pendentes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120);
            $table->string('email')->unique();
            $table->string('whatsapp', 13);
            $table->string('senha_hash');
            $table->char('token_hash', 64);
            $table->timestamp('expira_em')->index();
            $table->timestamp('consentido_em');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_cadastros_pendentes');
    }
};
