<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Models\CompraConciliacaoEfetivacao;
use App\Models\CompraPreConciliacao;
use App\Models\ContaPagar;
use App\Models\ParcelaContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * D03B: efetiva abatimento apenas no caso estritamente simples e sem pagamento.
 * Credito = direito registrado, nao consumido; reembolso = a receber, nao quitado.
 * Nao altera movimentos de caixa nem estorna pagamentos.
 */
class EfetivarConciliacaoCompra
{
    public function execute(Compra $compra, array $dados, int $usuarioId): CompraConciliacaoEfetivacao
    {
        return DB::transaction(function () use ($compra, $dados, $usuarioId): CompraConciliacaoEfetivacao {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $registro = CompraPreConciliacao::query()
                ->where('compra_id', $compra->id)->lockForUpdate()->first();
            if ($registro === null) {
                throw new InvalidArgumentException('Crie uma proposta D03A antes da efetivacao.');
            }

            $uuid = (string) $dados['operacao_uuid'];
            $doc = trim((string) $dados['documento_confirmacao']);
            $justificativa = trim((string) $dados['justificativa']);
            $contaId = isset($dados['conta_pagar_id']) && $dados['conta_pagar_id'] !== ''
                ? (int) $dados['conta_pagar_id'] : null;
            $existente = CompraConciliacaoEfetivacao::query()
                ->where('compra_id', $compra->id)->first();
            if ($existente !== null) {
                if ($existente->operacao_uuid !== $uuid
                    || $existente->documento_confirmacao !== $doc
                    || $existente->justificativa !== $justificativa
                    || ($existente->conta_pagar_id === null ? null : (int) $existente->conta_pagar_id) !== $contaId) {
                    throw new InvalidArgumentException('Conciliacao ja executada: reenvio com dados divergentes.');
                }
                return $existente;
            }

            if ($compra->devolucoes()->where('modalidade', 'documental')
                ->where('situacao_financeira', 'aplicada')->exists()) {
                throw new InvalidArgumentException(
                    'Compra ja possui abatimento documental D04; outro abatimento automatico requer conciliacao manual.'
                );
            }
            if (empty($dados['confirmacao'])) {
                throw new InvalidArgumentException('Confirmacao explicita e obrigatoria.');
            }
            if (!$compra->estaAprovada() || $registro->status !== 'rascunho') {
                throw new InvalidArgumentException('Compra ou proposta nao disponivel para efetivacao.');
            }
            if (!$compra->possuiDivergenciaConferencia() && !$compra->devolucoes()->exists()) {
                throw new InvalidArgumentException('Divergencia ou devolucao nao encontrada.');
            }
            if (mb_strlen($doc) < 5 || mb_strlen($justificativa) < 10) {
                throw new InvalidArgumentException('Documento ou justificativa insuficientes.');
            }
            $tipo = $registro->tratamento_proposto;
            $valor = (int) round((float) $registro->valor_proposto * 100);
            $totalCompra = (int) round((float) $compra->valor_total * 100);
            if (!in_array($tipo, ['abatimento', 'credito', 'reembolso', 'sem_ajuste'], true)
                || $valor < 0 || $valor > $totalCompra
                || (($tipo === 'sem_ajuste') !== ($valor === 0))) {
                throw new InvalidArgumentException('Proposta financeira inconsistente.');
            }
            if ($tipo !== 'abatimento' && $contaId !== null) {
                throw new InvalidArgumentException('Conta a pagar so deve ser selecionada para abatimento.');
            }

            $status = match ($tipo) {
                'abatimento' => 'abatimento_aplicado',
                'credito' => 'credito_registrado_nao_utilizado',
                'reembolso' => 'reembolso_a_receber',
                default => 'concluida_sem_ajuste',
            };
            $valorAntes = null;
            $valorDepois = null;
            if ($tipo === 'abatimento') {
                if ($contaId === null) {
                    throw new InvalidArgumentException('Selecione a conta a pagar para abatimento.');
                }
                $conta = ContaPagar::query()->lockForUpdate()->findOrFail($contaId);
                if ((int) $conta->fornecedor_id !== (int) $compra->fornecedor_id || $conta->status !== 'aberta') {
                    throw new InvalidArgumentException('Abatimento exige conta aberta do mesmo fornecedor.');
                }
                $vinculos = DB::table('compra_conta_pagar')
                    ->where('conta_pagar_id', $contaId)->lockForUpdate()->get();
                if ($vinculos->count() !== 1 || (int) $vinculos->first()->compra_id !== (int) $compra->id
                    || ($conta->compra_id !== null && (int) $conta->compra_id !== (int) $compra->id)
                    || ContaPagar::query()->where('compra_id', $compra->id)->where('id', '!=', $contaId)->exists()) {
                    throw new InvalidArgumentException('Conta agrupada ou vinculos inconsistentes: conciliacao manual necessaria.');
                }
                $parcelas = ParcelaContaPagar::query()
                    ->where('conta_pagar_id', $contaId)->lockForUpdate()->get();
                if ($parcelas->count() !== 1) {
                    throw new InvalidArgumentException('Abatimento automatico exige parcela unica; demais casos serao manuais.');
                }
                if ($conta->pagamentos()->exists()) {
                    throw new InvalidArgumentException('Conta com historico de pagamentos requer conciliacao manual.');
                }
                $valorAntes = (int) round((float) $conta->valor * 100);
                $parcela = $parcelas->first();
                $valorParcela = (int) round((float) $parcela->valor * 100);
                $valorVinculado = (int) round((float) $vinculos->first()->valor_vinculado * 100);
                if ($valorAntes <= 0 || $valorParcela !== $valorAntes || $valorVinculado !== $valorAntes
                    || $valor <= 0 || $valor >= $valorAntes) {
                    throw new InvalidArgumentException('Saldo, parcela ou valor de abatimento incompatível.');
                }
                $valorDepois = $valorAntes - $valor;
                $novo = number_format($valorDepois / 100, 2, '.', '');
                $conta->update(['valor' => $novo]);
                $parcela->update(['valor' => $novo]);
                DB::table('compra_conta_pagar')
                    ->where('conta_pagar_id', $contaId)->where('compra_id', $compra->id)
                    ->update(['valor_vinculado' => $novo, 'updated_at' => now()]);
            } elseif ($tipo === 'credito' || $tipo === 'reembolso') {
                // Um credito/reembolso nao pode ser confundido com liquidacao de boleto ativo.
                $contasIds = DB::table('compra_conta_pagar')
                    ->where('compra_id', $compra->id)->pluck('conta_pagar_id');
                $existeContaAberta = ContaPagar::query()
                    ->where(function ($q) use ($compra, $contasIds): void {
                        $q->where('compra_id', $compra->id);
                        if ($contasIds->isNotEmpty()) $q->orWhereIn('id', $contasIds);
                    })
                    ->whereNotIn('status', ['cancelada', 'paga'])
                    ->exists();
                if ($existeContaAberta) {
                    throw new InvalidArgumentException(
                        'Existe conta a pagar nao liquidada: ajuste ou conciliacao manual antes de registrar direito.'
                    );
                }
            }

            $efeito = CompraConciliacaoEfetivacao::create([
                'compra_id' => $compra->id,
                'compra_pre_conciliacao_id' => $registro->id,
                'usuario_id' => $usuarioId,
                'operacao_uuid' => $uuid,
                'tratamento' => $tipo,
                'status' => $status,
                'valor' => number_format($valor / 100, 2, '.', ''),
                'conta_pagar_id' => $tipo === 'abatimento' ? $contaId : null,
                'valor_conta_antes' => $valorAntes === null ? null : number_format($valorAntes / 100, 2, '.', ''),
                'valor_conta_depois' => $valorDepois === null ? null : number_format($valorDepois / 100, 2, '.', ''),
                'documento_confirmacao' => $doc,
                'justificativa' => $justificativa,
            ]);
            $registro->update(['status' => 'efetivada']);
            return $efeito;
        }, 3);
    }
}
