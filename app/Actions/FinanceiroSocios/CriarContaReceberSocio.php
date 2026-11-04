<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaReceberSocio;
use App\Models\Socio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CriarContaReceberSocio
{
    public function execute(Socio $socio, array $dados): ContaReceberSocio
    {
        $centavos = DinheiroSocios::centavos($dados['valor']);
        $quantidade = (int) ($dados['quantidade_parcelas'] ?? 1);

        if ($centavos <= 0 || $quantidade < 1 || $quantidade > 120 || $quantidade > $centavos) {
            throw new InvalidArgumentException('Valor ou quantidade de parcelas inválida.');
        }

        $origem = $dados['origem'];
        if (!in_array($origem, ['dinheiro', 'pix', 'cartao', 'transferencia', 'outros'], true)) {
            throw new InvalidArgumentException('Origem do valor inválida.');
        }

        $primeiro = Carbon::parse($dados['primeiro_vencimento']);
        $base = intdiv($centavos, $quantidade);
        $resto = $centavos % $quantidade;

        return DB::transaction(function () use ($socio, $dados, $origem, $primeiro, $quantidade, $base, $resto, $centavos) {
            $conta = $socio->recebiveis()->create([
                'devedor' => $dados['devedor'],
                'descricao' => $dados['descricao'],
                'origem' => $origem,
                'identificacao_cartao' => $origem === 'cartao' ? ($dados['identificacao_cartao'] ?? null) : null,
                'valor' => DinheiroSocios::decimal($centavos),
                'data_operacao' => $dados['data_operacao'],
                'status' => 'aberta',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            for ($numero = 1; $numero <= $quantidade; $numero++) {
                $valorParcela = $base + ($numero <= $resto ? 1 : 0);
                $conta->parcelas()->create([
                    'numero' => $numero,
                    'valor' => DinheiroSocios::decimal($valorParcela),
                    'data_vencimento' => $primeiro->copy()->addMonthsNoOverflow($numero - 1)->toDateString(),
                ]);
            }

            return $conta->load('parcelas');
        });
    }
}
