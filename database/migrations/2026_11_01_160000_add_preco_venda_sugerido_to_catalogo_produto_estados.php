<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_produto_estados', function (Blueprint $table) {
            // Valor arredondado e monotônico; o bruto permanece em preco_venda_observado.
            $table->decimal('preco_venda_sugerido', 14, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_produto_estados', function (Blueprint $table) {
            $table->dropColumn('preco_venda_sugerido');
        });
    }
};
