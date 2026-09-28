<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto_imagens', function (Blueprint $table) {
            $table->foreignId('produto_id')
                ->after('id')
                ->constrained('produtos')
                ->cascadeOnDelete();

            $table->string('caminho')
                ->after('produto_id');

            $table->unsignedInteger('ordem')
                ->default(0)
                ->after('caminho');

            $table->index([
                'produto_id',
                'ordem',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('produto_imagens', function (Blueprint $table) {
            $table->dropForeign([
                'produto_id',
            ]);

            $table->dropIndex([
                'produto_id',
                'ordem',
            ]);

            $table->dropColumn([
                'produto_id',
                'caminho',
                'ordem',
            ]);
        });
    }
};
