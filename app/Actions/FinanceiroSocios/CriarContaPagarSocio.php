<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaPagarSocio;
use App\Models\Socio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CriarContaPagarSocio
{
    public function execute(Socio $socio, array $dados): ContaPagarSocio
    {
        $total = DinheiroSocios::centavos($dados['valor']);
        $quantidade = (int) ($dados['quantidade_parcelas'] ?? 1);

        if ($total <= 0 || $quantidade < 1 || $quantidade > 120 || $quantidade > $total) {
            throw new InvalidArgumentException('Valor ou quantidade de parcelas invalida.');
        }

        $base = intdiv($total, $quantidade);
        $resto = $total % $quantidade;
        $vencimento = Carbon::parse($dados['primeiro_vencimento']);

        return DB::transaction(function () use ($socio, $dados, $quantidade, $base, $resto, $vencimento) {
            $conta = $socio->contas()->create([
                'descricao' => $dados['descricao'],
                'credor' => $dados['credor'] ?? null,
                'categoria' => $dados['categoria'] ?? 'Outros',
                'valor' => $dados['valor'],
                'data_emissao' => $dados['data_emissao'],
                'status' => 'aberta',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            for ($numero = 1; $numero <= $quantidade; $numero++) {
                $valorParcela = $base + ($numero <= $resto ? 1 : 0);

                $conta->parcelas()->create([
                    'numero' => $numero,
                    'valor' => DinheiroSocios::decimal($valorParcela),
                    'data_vencimento' => $vencimento->copy()
                        ->addMonthsNoOverflow($numero - 1)->toDateString(),
                ]);
            }

            return $conta->load('parcelas');
        });
    }
}
