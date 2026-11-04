<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaReceberSocio;
use App\Models\FormaPagamento;
use App\Models\ParcelaContaReceberSocio;
use App\Models\RecebimentoContaReceberSocio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarRecebimentoSocio
{
    public function execute(ContaReceberSocio $conta, array $dados): RecebimentoContaReceberSocio
    {
        return DB::transaction(function () use ($conta, $dados) {
            $conta = ContaReceberSocio::query()->lockForUpdate()->findOrFail($conta->id);
            if (!in_array($conta->status, ['aberta', 'parcial'], true)) {
                throw new InvalidArgumentException('Esta conta não permite novos recebimentos.');
            }

            $parcela = ParcelaContaReceberSocio::query()
                ->whereKey($dados['parcela_id'])
                ->where('conta_receber_socio_id', $conta->id)
                ->lockForUpdate()->first();

            if (!$parcela) {
                throw new InvalidArgumentException('Parcela não pertence à conta informada.');
            }

            // Cadastro central de formas de pagamento. A entrada permanece pessoal.
            $forma = FormaPagamento::query()
                ->whereKey($dados['forma_pagamento_id'] ?? null)
                ->where('ativo', true)
                ->lockForUpdate()
                ->first();

            if (!$forma) {
                throw new InvalidArgumentException('A forma de pagamento não existe ou está inativa.');
            }

            $valor = DinheiroSocios::centavos($dados['valor']);
            $recebidoParcela = DinheiroSocios::centavos((string) $parcela->recebimentosAtivos()->sum('valor'));
            $saldoParcela = DinheiroSocios::centavos($parcela->valor) - $recebidoParcela;
            if ($valor <= 0 || $saldoParcela < $valor) {
                throw new InvalidArgumentException('Recebimento superior ao saldo da parcela ou valor inválido.');
            }

            $recebidoConta = DinheiroSocios::centavos((string) $conta->recebimentosAtivos()->sum('valor'));
            $saldoConta = DinheiroSocios::centavos($conta->valor) - $recebidoConta;
            if ($saldoConta < $valor) {
                throw new InvalidArgumentException('Recebimento superior ao saldo total.');
            }

            $recebimento = $conta->recebimentos()->create([
                'parcela_conta_receber_socio_id' => $parcela->id,
                'valor' => DinheiroSocios::decimal($valor),
                'data_recebimento' => $dados['data_recebimento'],
                // Coluna legada obrigatória, preservada para compatibilidade.
                'meio_recebimento' => mb_substr($forma->nome, 0, 30),
                // Vinculo e descricao completa na data do recebimento.
                'forma_pagamento_id' => $forma->id,
                'forma_pagamento_nome' => $forma->nome,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $novoTotal = $recebidoConta + $valor;
            $quitada = $novoTotal === DinheiroSocios::centavos($conta->valor);
            $conta->update([
                'status' => $quitada ? 'quitada' : 'parcial',
                'data_quitacao' => $quitada ? $dados['data_recebimento'] : null,
            ]);

            return $recebimento;
        });
    }
}
