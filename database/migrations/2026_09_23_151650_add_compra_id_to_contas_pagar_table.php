<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->foreignId('compra_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('compras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->dropForeign(['compra_id']);
            $table->dropUnique(['compra_id']);
            $table->dropColumn('compra_id');
        });
    }
};
