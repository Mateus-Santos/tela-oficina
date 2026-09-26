<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('marca');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('marca', 150)
                ->nullable()
                ->after('fornecedor_id');
        });

        DB::table('produtos')
            ->join('marcas', 'marcas.id', '=', 'produtos.marca_id')
            ->update([
                'produtos.marca' => DB::raw('marcas.nome'),
            ]);
    }
};
