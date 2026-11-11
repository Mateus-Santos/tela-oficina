<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_lead_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_lead_id')
                ->constrained('marketing_leads')->cascadeOnDelete();
            $table->foreignId('produto_id')
                ->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('descricao', 180);
            $table->unsignedSmallInteger('quantidade')->default(1);
            $table->unsignedSmallInteger('ordem');
            $table->timestamps();

            $table->unique(
                ['marketing_lead_id', 'ordem'],
                'uq_marketing_lead_itens_ordem'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_lead_itens');
    }
};
