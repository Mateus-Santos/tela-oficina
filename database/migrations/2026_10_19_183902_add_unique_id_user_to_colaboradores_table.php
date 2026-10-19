<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'colaboradores',
            function (Blueprint $table) {
                $table
                    ->unique(
                        'id_user',
                        'colaboradores_id_user_unique'
                    );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'colaboradores',
            function (Blueprint $table) {
                $table->dropUnique(
                    'colaboradores_id_user_unique'
                );
            }
        );
    }
};
