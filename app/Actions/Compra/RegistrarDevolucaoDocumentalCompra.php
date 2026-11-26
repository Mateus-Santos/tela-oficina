<?php

namespace App\Actions\Compra;

use App\Actions\Estoque\RegistrarSaida;
use App\Models\Compra;
use App\Models\CompraConciliacaoEfetivacao;
use App\Models\CompraDevolucao;
use App\Models\CompraDevolucaoItem;
use App\Models\ContaPagar;
use App\Models\ParcelaContaPagar;
use App\Services\Compra\SaldoDevolucaoCompra;
use App\Services\Compra\ValorDevidoCompra;
use App\Support\Estoque\Quantidade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * D04: documento de devolucao posterior a aprovacao. Nao emite NF-e.
 * A compra original e imutavel: o abatimento vai ao livro de devolucoes.
 * Conta vinculada e recalculada APENAS se aberta, integra, sem pagamentos
 * (inclusive historicos estornados). Caso contrario exige conciliacao manual.
 */
class RegistrarDevolucaoDocumentalCompra
{
    public function __construct(
        private RegistrarSaida $registrarSaida,
        private SaldoDevolucaoCompra $saldos,
        private ValorDevidoCompra $valorDevido
    ) {
    }

    public function execute(Compra $compra, array $dados, ?int $usuarioId): CompraDevolucao
    {
        return DB::transaction(function () use ($compra, $dados, $usuarioId): CompraDevolucao {
            $compra = Compra::query()->lockForUpdate()
                ->with('itens.produto')->findOrFail($compra->id);
            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException('Somente compras aprovadas admitem devolucao documental.');
            }
            if (empty($dados['confirmacao_fisica']) || empty($dados['confirmacao_financeira'])) {
                throw new InvalidArgumentException('Confirmacoes fisica e financeira sao obrigatorias.');
            }

            $solicitados = [];
            foreach ($dados['itens'] as $chave => $linha) {
                if (!ctype_digit((string) $chave) || !is_array($linha)) {
                    throw new InvalidArgumentException('Item invalido na devolucao.');
                }
                $id = (int) $chave;
                $qtd = Quantidade::milessimos((string) ($linha['quantidade'] ?? '0'));
                if ($qtd <= 0) {
                    continue;
                }
                $modalidade = (string) ($linha['modalidade'] ?? '');
                if (!in_array($modalidade, ['estoque', 'recusado'], true)) {
                    throw new InvalidArgumentException('Selecione o tipo de devolucao de cada item.');
                }
                $solicitados[$id] = ['quantidade' => $qtd, 'modalidade' => $modalidade];
            }
            if ($solicitados === []) {
                throw new InvalidArgumentException('Informe ao menos um item com quantidade maior que zero.');
            }
            ksort($solicitados);

            $referencia = trim((string) $dados['referencia_documento']);
            $motivo = trim((string) $dados['motivo']);
            $chave = $dados['chave_nfe'] ?? null;
            $chave = $chave === '' ? null : $chave;
            $assinatura = hash('sha256', json_encode([
                'compra' => $compra->id,
                'referencia' => $referencia,
                'chave' => $chave,
                'data' => $dados['data_documento'],
                'motivo' => $motivo,
                'itens' => $solicitados,
            ], JSON_THROW_ON_ERROR));

            $existente = CompraDevolucao::query()
                ->where('operacao_uuid', (string) $dados['operacao_uuid'])->first();
            if ($existente !== null) {
                if ((int) $existente->compra_id !== (int) $compra->id
                    || $existente->modalidade !== 'documental'
                    || $existente->assinatura_operacao !== $assinatura) {
                    throw new InvalidArgumentException('UUID ja utilizado com dados diferentes.');
                }
                return $existente;
            }
            if ($chave !== null && CompraDevolucao::query()->where('chave_nfe', $chave)->exists()) {
                throw new InvalidArgumentException('Chave de NF-e de devolucao ja cadastrada.');
            }

            $itensCompra = $compra->itens->keyBy('id');
            $saldos = $this->saldos->consultar($compra);
            $linhas = [];
            $valorAbatimento = 0;
            foreach ($solicitados as $id => $linha) {
                if (!$itensCompra->has($id)) {
                    throw new InvalidArgumentException('Item nao pertence a esta compra.');
                }
                $item = $itensCompra->get($id);
                $qtd = $linha['quantidade'];
                $modalidade = $linha['modalidade'];
                if ($qtd > $saldos[$id][$modalidade]) {
                    throw new InvalidArgumentException(
                        "Quantidade do item #{$id} excede o saldo disponivel para {$modalidade}."
                    );
                }
                if (!$item->produto && $modalidade === 'estoque') {
                    throw new InvalidArgumentException("Produto do item #{$id} nao existe mais.");
                }
                $centavos = $this->saldos->valorIncrementalCentavos($item, $qtd);
                if ($centavos <= 0) {
                    throw new InvalidArgumentException('Valor do item insuficiente para abatimento.');
                }
                $valorAbatimento += $centavos;
                $linhas[$id] = [
                    'item' => $item,
                    'quantidade' => $qtd,
                    'modalidade' => $modalidade,
                    'centavos' => $centavos,
                ];
            }

            $valorAntes = $this->valorDevido->valorLiquidoCentavos($compra);
            if ($valorAbatimento <= 0) {
                throw new InvalidArgumentException('Valor da devolucao precisa ser positivo.');
            }
            // Encargos/descontos globais podem fazer o item valer mais que o
            // saldo liquido. Nessa situacao, preserva o documento e o estoque,
            // mas bloqueia o abatimento automatico para conciliacao humana.
            $ajuste = $valorAbatimento > $valorAntes
                ? null
                : $this->planejarAjuste($compra, $valorAntes, $valorAbatimento);
            $aplicada = $ajuste !== null;

            $devolucao = CompraDevolucao::create([
                'compra_id' => $compra->id,
                'usuario_id' => $usuarioId,
                'operacao_uuid' => (string) $dados['operacao_uuid'],
                'referencia_documento' => $referencia,
                'chave_nfe' => $chave,
                'data_documento' => $dados['data_documento'],
                'motivo' => $motivo,
                'status' => 'efetivada',
                'modalidade' => 'documental',
                'situacao_financeira' => $aplicada ? 'aplicada' : 'pendente',
                'valor_abatimento' => $this->moeda($valorAbatimento),
                'assinatura_operacao' => $assinatura,
            ]);

            foreach ($linhas as $linha) {
                $item = $linha['item'];
                $devolvido = CompraDevolucaoItem::create([
                    'compra_devolucao_id' => $devolucao->id,
                    'compra_item_id' => $item->id,
                    'produto_id' => $item->produto_id,
                    'quantidade' => Quantidade::formatar($linha['quantidade']),
                    'modalidade' => $linha['modalidade'],
                    'valor_abatimento' => $this->moeda($linha['centavos']),
                ]);
                if ($linha['modalidade'] === 'estoque') {
                    $mov = $this->registrarSaida->execute(
                        produto: $item->produto,
                        quantidade: Quantidade::formatar($linha['quantidade']),
                        valorUnitario: (float) $item->valor_unitario,
                        origem: $devolvido,
                        observacoes: "Devolucao documental #{$devolucao->id} da Compra #{$compra->id}."
                    );
                    $devolvido->update(['movimentacao_estoque_id' => $mov->id]);
                }
            }

            // Sem conta vinculada: o novo valor liquido ja fica disponivel para vinculo futuro.
            // Com conta vinculada: conta, parcelas e pivo sao atualizados na MESMA transacao.
            if ($ajuste !== null && isset($ajuste['conta'])) {
                /** @var ContaPagar $conta */
                $conta = $ajuste['conta'];
                $conta->update(['valor' => $this->moeda($ajuste['novo_total'])]);
                foreach ($ajuste['parcelas'] as $id => $novoValor) {
                    ParcelaContaPagar::query()->whereKey($id)->update([
                        'valor' => $this->moeda($novoValor),
                    ]);
                }
                DB::table('compra_conta_pagar')
                    ->where('compra_id', $compra->id)
                    ->where('conta_pagar_id', $conta->id)
                    ->update([
                        'valor_vinculado' => $this->moeda($valorAntes - $valorAbatimento),
                        'updated_at' => now(),
                    ]);
            }

            return $devolucao->load('itens');
        }, 3);
    }

    /**
     * Retorna null para conciliacao pendente; nunca mexe em pagamentos,
     * contas canceladas, historicos D03 ou saldos divergentes.
     */
    private function planejarAjuste(Compra $compra, int $valorAntes, int $abatimento): ?array
    {
        if ($this->valorDevido->precisaConciliacao($compra)
            || CompraConciliacaoEfetivacao::query()->where('compra_id', $compra->id)->exists()) {
            return null;
        }

        $pivos = DB::table('compra_conta_pagar')
            ->where('compra_id', $compra->id)->lockForUpdate()->get();
        $legadas = ContaPagar::query()->where('compra_id', $compra->id)->get();
        if ($pivos->isEmpty() && $legadas->isEmpty()) {
            return []; // ainda sem conta; o valor liquido pode ser vinculado posteriormente
        }
        if ($pivos->count() !== 1) {
            return null;
        }
        $pivo = $pivos->first();
        $conta = ContaPagar::query()->lockForUpdate()->find($pivo->conta_pagar_id);
        if ($conta === null || $conta->status !== 'aberta'
            || (int) $conta->fornecedor_id !== (int) $compra->fornecedor_id
            || $conta->pagamentos()->exists()
            || $legadas->contains(fn ($l) => (int) $l->id !== (int) $conta->id)) {
            return null;
        }
        $valorPivo = (int) round((float) $pivo->valor_vinculado * 100);
        if ($valorPivo !== $valorAntes || $valorPivo <= $abatimento) {
            return null;
        }
        $todosPivos = DB::table('compra_conta_pagar')
            ->where('conta_pagar_id', $conta->id)->lockForUpdate()->get();
        $valorConta = (int) round((float) $conta->valor * 100);
        $totalPivos = $todosPivos->sum(fn ($p) => (int) round((float) $p->valor_vinculado * 100));
        if ($totalPivos !== $valorConta || $valorConta <= $abatimento) {
            return null;
        }
        $parcelas = ParcelaContaPagar::query()
            ->where('conta_pagar_id', $conta->id)
            ->orderBy('numero')->lockForUpdate()->get();
        if ($parcelas->isEmpty()
            || $parcelas->sum(fn ($p) => (int) round((float) $p->valor * 100)) !== $valorConta) {
            return null;
        }

        $novoTotal = $valorConta - $abatimento;
        $novasParcelas = [];
        $acumulado = 0;
        $ultima = $parcelas->count() - 1;
        foreach ($parcelas as $i => $parcela) {
            $valor = $i === $ultima
                ? $novoTotal - $acumulado
                : (int) floor((int) round((float) $parcela->valor * 100)
                    * $novoTotal / $valorConta);
            if ($valor <= 0) {
                return null; // nao criar parcelas zeradas nem mudar vencimentos
            }
            $novasParcelas[$parcela->id] = $valor;
            $acumulado += $valor;
        }
        if ($acumulado !== $novoTotal) {
            return null;
        }
        return [
            'conta' => $conta,
            'novo_total' => $novoTotal,
            'parcelas' => $novasParcelas,
        ];
    }

    private function moeda(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
