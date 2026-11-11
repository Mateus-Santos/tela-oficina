<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('protocolo', 24)->unique();
            $table->string('nome', 120);
            $table->string('whatsapp', 13);
            $table->string('peca', 180);
            $table->string('veiculo', 160)->nullable();
            $table->string('ano', 20)->nullable();
            $table->string('motor', 80)->nullable();
            $table->text('detalhes')->nullable();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('gclid', 200)->nullable();
            $table->timestamp('consentido_em');
            $table->string('status', 20)->default('novo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leads');
    }
};
