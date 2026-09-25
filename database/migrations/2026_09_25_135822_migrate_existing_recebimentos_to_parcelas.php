<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $recebimentos = DB::table('recebimentos')
                ->whereNull('parcela_conta_receber_id')
                ->orderBy('id')
                ->get([
                    'id',
                    'conta_receber_id',
                ]);

            foreach ($recebimentos as $recebimento) {
                $parcela = DB::table('parcelas_contas_receber')
                    ->where(
                        'conta_receber_id',
                        $recebimento->conta_receber_id
                    )
                    ->orderBy('numero')
                    ->first();

                if (!$parcela) {
                    throw new \RuntimeException(
                        "Não foi encontrada parcela para a conta a receber #{$recebimento->conta_receber_id}."
                    );
                }

                DB::table('recebimentos')
                    ->where('id', $recebimento->id)
                    ->update([
                        'parcela_conta_receber_id' => $parcela->id,
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('recebimentos')
            ->update([
                'parcela_conta_receber_id' => null,
            ]);
    }
};
