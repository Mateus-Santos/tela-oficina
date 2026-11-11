<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table): void {
            // Preenchidos somente após identificação segura do cliente
            // e conversão autorizada pela oficina.
            $table->foreignId('cliente_id')
                ->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('nota_id')
                ->nullable()->unique()->constrained('notas')->nullOnDelete();

            // Localidade informada pelo próprio interessado, não inferida do IP.
            $table->string('cidade', 120)->nullable();
            $table->char('uf', 2)->nullable();

            // Somente IP cifrado, para tratamento restrito e retenção definida.
            // Nunca expor a coluna em consultas públicas ou analytics.
            $table->text('ip_criptografado')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table): void {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['nota_id']);
            $table->dropUnique(['nota_id']);
            $table->dropColumn([
                'cliente_id', 'nota_id', 'cidade', 'uf', 'ip_criptografado',
            ]);
        });
    }
};
