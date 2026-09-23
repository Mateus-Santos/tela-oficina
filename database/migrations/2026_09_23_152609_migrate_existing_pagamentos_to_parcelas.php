<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $pagamentos = DB::table('pagamentos_contas_pagar')
                ->whereNull('parcela_conta_pagar_id')
                ->orderBy('id')
                ->get([
                    'id',
                    'conta_pagar_id',
                ]);

            foreach ($pagamentos as $pagamento) {
                $parcela = DB::table('parcelas_contas_pagar')
                    ->where('conta_pagar_id', $pagamento->conta_pagar_id)
                    ->orderBy('numero')
                    ->first();

                if (!$parcela) {
                    throw new RuntimeException(
                        "Não foi encontrada parcela para a conta a pagar #{$pagamento->conta_pagar_id}."
                    );
                }

                DB::table('pagamentos_contas_pagar')
                    ->where('id', $pagamento->id)
                    ->update([
                        'parcela_conta_pagar_id' => $parcela->id,
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('pagamentos_contas_pagar')
            ->update([
                'parcela_conta_pagar_id' => null,
            ]);
    }
};
