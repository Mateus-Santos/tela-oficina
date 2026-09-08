<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->foreignId('categoria_financeira_id')
                ->nullable()
                ->after('nota_id')
                ->constrained('categorias_financeiras')
                ->restrictOnDelete();

            $table->foreignId('forma_pagamento_id')
                ->nullable()
                ->after('categoria_financeira_id')
                ->constrained('formas_pagamento')
                ->restrictOnDelete();
        });

        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->foreignId('forma_pagamento_id')
                ->nullable()
                ->after('data_pagamento')
                ->constrained('formas_pagamento')
                ->restrictOnDelete();
        });

        DB::statement("
            UPDATE pagamentos_contas_pagar p
            INNER JOIN formas_pagamento f
                ON LOWER(TRIM(p.forma_pagamento)) = LOWER(TRIM(f.nome))
            SET p.forma_pagamento_id = f.id
            WHERE p.forma_pagamento_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->dropForeign(['forma_pagamento_id']);
            $table->dropColumn('forma_pagamento_id');
        });

        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->dropForeign(['categoria_financeira_id']);
            $table->dropForeign(['forma_pagamento_id']);
            $table->dropColumn([
                'categoria_financeira_id',
                'forma_pagamento_id',
            ]);
        });
    }
};
