<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recebimentos anteriores mantem meio_recebimento e ficam com ID nulo.
        Schema::table('recebimentos_contas_receber_socios', function (Blueprint $table) {
            $table->foreignId('forma_pagamento_id')->nullable()->after('meio_recebimento');
            $table->string('forma_pagamento_nome', 255)->nullable()->after('forma_pagamento_id');
            $table->index('forma_pagamento_id', 'rcr_socios_forma_idx');
            $table->foreign('forma_pagamento_id', 'rcr_socios_forma_fk')
                ->references('id')->on('formas_pagamento')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recebimentos_contas_receber_socios', function (Blueprint $table) {
            $table->dropForeign('rcr_socios_forma_fk');
            $table->dropIndex('rcr_socios_forma_idx');
            $table->dropColumn(['forma_pagamento_id', 'forma_pagamento_nome']);
        });
    }
};
