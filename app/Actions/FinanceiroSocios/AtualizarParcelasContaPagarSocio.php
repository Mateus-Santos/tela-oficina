<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaPagarSocio;
use App\Models\ParcelaContaPagarSocio;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Reorganiza somente valores e vencimentos; nunca edita pagamentos existentes. */
class AtualizarParcelasContaPagarSocio
{
    public function execute(ContaPagarSocio $conta, array $dados, ?int $usuarioId = null): int
    {
        return DB::transaction(function () use ($conta, $dados, $usuarioId): int {
            $conta = ContaPagarSocio::query()->lockForUpdate()->findOrFail($conta->id);
            if (in_array($conta->status, ['paga', 'cancelada'], true)) {
                throw new InvalidArgumentException('Conta paga ou cancelada nao permite alteracao das parcelas.');
            }

            $entradas = $dados['parcelas'] ?? null;
            $motivo = trim((string) ($dados['motivo'] ?? ''));
            if (!is_array($entradas) || count($entradas) === 0 || count($entradas) > 120
                || mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
                throw new InvalidArgumentException('Informe todas as parcelas e uma justificativa de 10 a 500 caracteres.');
            }

            $parcelas = ParcelaContaPagarSocio::query()
                ->where('conta_pagar_socio_id', $conta->id)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($parcelas->count() !== count($entradas)) {
                throw new InvalidArgumentException('O conjunto de parcelas deve permanecer completo.');
            }

            $preparadas = [];
            $soma = 0;
            foreach ($entradas as $linha) {
                if (!is_array($linha)) {
                    throw new InvalidArgumentException('Parcela invalida.');
                }
                $id = filter_var($linha['id'] ?? null, FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]);
                if ($id === false || isset($preparadas[$id]) || !$parcelas->has($id)) {
                    throw new InvalidArgumentException('Parcela duplicada ou pertencente a outra conta.');
                }
                $valor = DinheiroSocios::centavos((string) ($linha['valor'] ?? ''));
                if ($valor <= 0) {
                    throw new InvalidArgumentException('Toda parcela deve ter valor positivo.');
                }
                $vencimento = (string) ($linha['data_vencimento'] ?? '');
                $data = DateTimeImmutable::createFromFormat('!Y-m-d', $vencimento);
                if (!$data || $data->format('Y-m-d') !== $vencimento
                    || $vencimento < $conta->data_emissao->format('Y-m-d')) {
                    throw new InvalidArgumentException('Data de vencimento invalida ou anterior a emissao.');
                }
                $parcela = $parcelas->get($id);
                // Com pagamentos ativos, a nova parcela nao pode ser menor que o ja pago.
                $pago = DinheiroSocios::centavos((string) $parcela->pagamentosAtivos()->sum('valor'));
                if ($valor < $pago) {
                    throw new InvalidArgumentException("Parcela #{$parcela->numero} menor que o total ja pago.");
                }
                $preparadas[$id] = [$parcela, $valor, $vencimento];
                $soma += $valor;
            }
            if ($soma !== DinheiroSocios::centavos((string) $conta->valor)) {
                throw new InvalidArgumentException('A soma das parcelas deve ser exatamente igual ao valor da divida.');
            }

            $alteradas = 0;
            foreach ($preparadas as [$parcela, $centavos, $vencimento]) {
                $valorAnterior = (string) $parcela->valor;
                $vencimentoAnterior = $parcela->data_vencimento->format('Y-m-d');
                $valorNovo = DinheiroSocios::decimal($centavos);
                if ($valorAnterior === $valorNovo && $vencimentoAnterior === $vencimento) {
                    continue;
                }
                $parcela->update(['valor' => $valorNovo, 'data_vencimento' => $vencimento]);
                DB::table('parcelas_contas_pagar_socios_ajustes')->insert([
                    'conta_pagar_socio_id' => $conta->id,
                    'parcela_conta_pagar_socio_id' => $parcela->id,
                    'user_id' => $usuarioId,
                    'valor_anterior' => $valorAnterior,
                    'valor_novo' => $valorNovo,
                    'vencimento_anterior' => $vencimentoAnterior,
                    'vencimento_novo' => $vencimento,
                    'motivo' => $motivo,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $alteradas++;
            }
            return $alteradas;
        }, 3);
    }
}
