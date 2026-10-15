<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'colaborador',
                'permitions',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table
                ->boolean('colaborador')
                ->default(false)
                ->after('status');

            $table
                ->integer('permitions')
                ->default(2)
                ->after('colaborador');
        });
    }
};
