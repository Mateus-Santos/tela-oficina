<?php

namespace App\Actions\Compra;

use App\Actions\Estoque\RegistrarSaida;
use App\Models\Compra;
use App\Models\CompraDevolucao;
use App\Models\CompraDevolucaoItem;
use App\Models\MovimentacaoEstoque;
use App\Support\Estoque\Quantidade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * D01A: devolucao operacional. Nao emite NF-e nem altera financeiro.
 * Exige conferencia fisica e ausencia de conta a pagar ativa.
 */
class RegistrarDevolucaoCompra
{
    public function __construct(private RegistrarSaida $registrarSaida)
    {
    }

    public function execute(Compra $compra, array $dados, ?int $usuarioId): CompraDevolucao
    {
        return DB::transaction(function () use ($compra, $dados, $usuarioId): CompraDevolucao {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens.produto')
                ->findOrFail($compra->id);

            if (empty($dados['confirmacao_fisica'])) {
                throw new InvalidArgumentException('Confirme a separacao fisica da mercadoria.');
            }
            $uuid = (string) $dados['operacao_uuid'];
            $existente = CompraDevolucao::query()->where('operacao_uuid', $uuid)->first();
            if ($existente !== null) {
                if ((int) $existente->compra_id !== (int) $compra->id) {
                    throw new InvalidArgumentException('Identificador de operacao ja utilizado por outra compra.');
                }

                // Repeticoes exatas sao idempotentes, mas o mesmo UUID nao pode
                // confirmar silenciosamente quantidades ou documentos alterados.
                $anteriores = $existente->itens()
                    ->get(['compra_item_id', 'quantidade'])
                    ->mapWithKeys(fn (CompraDevolucaoItem $linha) => [
                        (int) $linha->compra_item_id => (string) $linha->quantidade,
                    ])->all();
                $reenviados = [];
                foreach ($dados['itens'] as $id => $valor) {
                    if (!ctype_digit((string) $id)) {
                        throw new InvalidArgumentException('Item invalido na repeticao da operacao.');
                    }
                    $quantidade = Quantidade::milessimos((string) $valor);
                    if ($quantidade > 0) {
                        $reenviados[(int) $id] = Quantidade::formatar($quantidade);
                    }
                }
                ksort($anteriores);
                ksort($reenviados);
                if (
                    $existente->status !== 'efetivada'
                    || $existente->referencia_documento !== trim((string) $dados['referencia_documento'])
                    || $existente->chave_nfe !== ($dados['chave_nfe'] ?? null)
                    || $existente->data_documento?->format('Y-m-d') !== $dados['data_documento']
                    || $existente->motivo !== trim((string) $dados['motivo'])
                    || $anteriores !== $reenviados
                ) {
                    throw new InvalidArgumentException(
                        'Operacao ja registrada com dados diferentes. Reabra o formulario para uma nova devolucao.'
                    );
                }
                return $existente;
            }

            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException('Somente compras aprovadas admitem devolucao operacional.');
            }

            if ($compra->contaPagar()->where('status', '!=', 'cancelada')->exists()
                || $compra->contasPagar()->where('contas_pagar.status', '!=', 'cancelada')->exists()) {
                throw new InvalidArgumentException(
                    'Compra vinculada a conta a pagar ativa. A conciliacao financeira D03 e obrigatoria antes da devolucao operacional.'
                );
            }

            $itens = $compra->itens->keyBy('id');
            $solicitados = [];
            foreach ($dados['itens'] as $id => $valor) {
                if (!ctype_digit((string) $id) || !$itens->has((int) $id)) {
                    throw new InvalidArgumentException('O item informado nao pertence a esta compra.');
                }
                $qtd = Quantidade::milessimos((string) $valor);
                if ($qtd > 0) {
                    $solicitados[(int) $id] = $qtd;
                }
            }
            if ($solicitados === []) {
                throw new InvalidArgumentException('Informe uma quantidade maior que zero em pelo menos um item.');
            }

            // As quantidades sao confrontadas com a entrada historica e devolucoes anteriores.
            // O saldo de estoque e verificado novamente sob lock em RegistrarSaida.
            foreach ($solicitados as $id => $qtd) {
                $item = $itens->get($id);
                if (!$item->produto) {
                    throw new InvalidArgumentException('Produto de compra inexistente.');
                }
                $base = MovimentacaoEstoque::query()
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id);
                if ((clone $base)->where('tipo', 'saida')->exists()) {
                    throw new InvalidArgumentException('Item possui baixa legada de estorno: regularizacao manual necessaria.');
                }
                $entrada = Quantidade::milessimos((string) (clone $base)
                    ->where('tipo', 'entrada')->sum('quantidade'));
                $devolvida = Quantidade::milessimos((string) CompraDevolucaoItem::query()
                    ->where('compra_item_id', $item->id)
                    ->whereHas('devolucao', fn ($q) => $q->where('status', 'efetivada'))
                    ->sum('quantidade'));
                if ($entrada <= 0 || $qtd > $entrada - $devolvida) {
                    throw new InvalidArgumentException(
                        "Quantidade de devolucao excede o saldo comprovado da entrada para o item #{$item->id}."
                    );
                }
            }

            $devolucao = CompraDevolucao::create([
                'compra_id' => $compra->id,
                'usuario_id' => $usuarioId,
                'operacao_uuid' => $uuid,
                'referencia_documento' => trim((string) $dados['referencia_documento']),
                'chave_nfe' => $dados['chave_nfe'] ?? null,
                'data_documento' => $dados['data_documento'],
                'motivo' => trim((string) $dados['motivo']),
                'status' => 'efetivada',
            ]);

            foreach ($solicitados as $id => $qtd) {
                $item = $itens->get($id);
                $linha = CompraDevolucaoItem::create([
                    'compra_devolucao_id' => $devolucao->id,
                    'compra_item_id' => $item->id,
                    'produto_id' => $item->produto_id,
                    'quantidade' => Quantidade::formatar($qtd),
                ]);
                $movimento = $this->registrarSaida->execute(
                    produto: $item->produto,
                    quantidade: Quantidade::formatar($qtd),
                    valorUnitario: (float) $item->valor_unitario,
                    origem: $linha,
                    observacoes: "Devolucao operacional de Compra #{$compra->id} / Devolucao #{$devolucao->id}."
                );
                $linha->update(['movimentacao_estoque_id' => $movimento->id]);
            }

            return $devolucao->load('itens');
        }, 3);
    }
}
