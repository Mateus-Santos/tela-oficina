<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE ordem_servicos
            MODIFY status ENUM(
                'aberta',
                'finalizada',
                'cancelada'
            ) NOT NULL DEFAULT 'aberta'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE ordem_servicos
            MODIFY status ENUM(
                'aberta',
                'em_andamento',
                'aguardando_aprovacao',
                'finalizada',
                'cancelada'
            ) NOT NULL DEFAULT 'aberta'
        ");
    }
};
