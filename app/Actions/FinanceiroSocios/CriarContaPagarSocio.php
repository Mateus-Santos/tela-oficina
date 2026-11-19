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
        $personalizar = filter_var($dados['personalizar_parcelas'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $parcelasPersonalizadas = [];
        if ($personalizar) {
            $parcelas = $dados['parcelas'] ?? null;
            if (!is_array($parcelas) || count($parcelas) !== $quantidade) {
                throw new InvalidArgumentException('Informe valores e vencimentos de todas as parcelas.');
            }
            $soma = 0;
            foreach ($parcelas as $linha) {
                if (!is_array($linha)) {
                    throw new InvalidArgumentException('Dados de parcela invalidos.');
                }
                $valor = DinheiroSocios::centavos((string) ($linha['valor'] ?? ''));
                $data = (string) ($linha['data_vencimento'] ?? '');
                $venc = \DateTimeImmutable::createFromFormat('!Y-m-d', $data);
                if ($valor <= 0 || !$venc || $venc->format('Y-m-d') !== $data
                    || $data < (string) $dados['data_emissao']) {
                    throw new InvalidArgumentException('Valor ou vencimento invalido em parcela personalizada.');
                }
                $parcelasPersonalizadas[] = [$valor, $data];
                $soma += $valor;
            }
            if ($soma !== $total) {
                throw new InvalidArgumentException('A soma das parcelas deve ser igual ao valor da divida.');
            }
        }

        return DB::transaction(function () use ($socio, $dados, $quantidade, $base, $resto, $vencimento, $parcelasPersonalizadas) {
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
                $personalizada = $parcelasPersonalizadas[$numero - 1] ?? null;
                $valorParcela = $personalizada[0] ?? ($base + ($numero <= $resto ? 1 : 0));
                $dataParcela = $personalizada[1] ?? $vencimento->copy()
                    ->addMonthsNoOverflow($numero - 1)->toDateString();

                $conta->parcelas()->create([
                    'numero' => $numero,
                    'valor' => DinheiroSocios::decimal($valorParcela),
                    'data_vencimento' => $dataParcela,
                ]);
            }

            return $conta->load('parcelas');
        });
    }
}
