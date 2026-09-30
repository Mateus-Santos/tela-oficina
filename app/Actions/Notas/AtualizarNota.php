<?php

namespace App\Actions\Notas;

use App\Actions\Financeiro\SincronizarContaReceberComNota;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtualizarNota
{
    public function __construct(
        private SincronizarContaReceberComNota $sincronizarContaReceberComNota
    ) {
    }

    public function execute(
        Nota $nota,
        array $dados
    ): Nota {
        return DB::transaction(
            function () use ($nota, $dados) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $nota->id
                    );

                if (
                    $nota->status
                    !== 'Aberto'
                ) {
                    throw ValidationException::withMessages([
                        'nota' =>
                            'Somente notas com status Aberto podem ser editadas.',
                    ]);
                }

                $clienteId =
                    !empty($dados['cliente_id'])
                        ? (int) $dados['cliente_id']
                        : null;

                $veiculoClienteId =
                    !empty($dados['veiculo_cliente_id'])
                        ? (int) $dados['veiculo_cliente_id']
                        : null;

                $itens =
                    $dados['itens']
                    ?? [];

                $this->validarClienteVeiculo(
                    $clienteId,
                    $veiculoClienteId
                );

                $this->validarProdutosDuplicados(
                    $itens
                );

                [$subtotalGeral, $descontoGeral] =
                    $this->validarECalcularItens(
                        $nota,
                        $itens,
                        $clienteId,
                        $veiculoClienteId
                    );

                $nota->update([
                    'cliente_id' =>
                        $clienteId,

                    'veiculo_cliente_id' =>
                        $veiculoClienteId,

                    'km' =>
                        $dados['km'] ?? null,

                    'km_proxima_troca_oleo' =>
                        $dados['km_proxima_troca_oleo']
                        ?? null,

                    'subtotal' =>
                        $subtotalGeral,

                    'desconto' =>
                        $descontoGeral,

                    'total' =>
                        max(
                            0,
                            $subtotalGeral
                            - $descontoGeral
                        ),
                ]);

                $idsEnviados =
                    collect($itens)
                        ->pluck('id')
                        ->filter()
                        ->map(
                            fn ($id) =>
                                (int) $id
                        )
                        ->values()
                        ->all();

                if (!empty($idsEnviados)) {
                    $nota
                        ->itens()
                        ->whereNotIn(
                            'id',
                            $idsEnviados
                        )
                        ->delete();
                } else {
                    $nota
                        ->itens()
                        ->delete();
                }

                foreach ($itens as $dadosItem) {
                    $this->salvarItem(
                        $nota,
                        $dadosItem
                    );
                }

                /*
                 * =====================================================
                 * SINCRONIZAÇÃO FINANCEIRA
                 * =====================================================
                 *
                 * Se a Nota já possuir Conta a Receber:
                 *
                 * - valor da Conta acompanha o novo total;
                 * - parcelas são ajustadas;
                 * - recebimentos ativos são preservados;
                 * - novo total abaixo do recebido é bloqueado.
                 *
                 * Como estamos dentro da mesma transaction,
                 * qualquer erro desfaz também a alteração da Nota.
                 */
                $nota->refresh();

                $this
                    ->sincronizarContaReceberComNota
                    ->execute(
                        $nota
                    );

                return $nota->fresh([
                    'cliente.pessoa',
                    'veiculosCliente',
                    'itens.itemable',
                    'contaReceber.parcelas',
                ]);
            }
        );
    }

    private function validarClienteVeiculo(
        ?int $clienteId,
        ?int $veiculoClienteId
    ): void {
        if (!$veiculoClienteId) {
            return;
        }

        if (!$clienteId) {
            throw ValidationException::withMessages([
                'cliente_id' =>
                    'Selecione o cliente responsável pelo veículo.',
            ]);
        }

        $vinculoExiste =
            VeiculosCliente::query()
                ->whereKey(
                    $veiculoClienteId
                )
                ->whereHas(
                    'clientes',
                    function ($query) use ($clienteId) {
                        $query->where(
                            'clientes.id',
                            $clienteId
                        );
                    }
                )
                ->exists();

        if (!$vinculoExiste) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' =>
                    'O cliente informado não está vinculado ao veículo selecionado.',
            ]);
        }
    }

    private function validarProdutosDuplicados(
        array $itens
    ): void {
        $produtos = [];

        foreach ($itens as $index => $item) {
            if (
                ($item['itemable_type'] ?? null)
                !== Produto::class
            ) {
                continue;
            }

            $produtoId =
                (int) (
                    $item['itemable_id']
                    ?? 0
                );

            if (!$produtoId) {
                continue;
            }

            if (isset($produtos[$produtoId])) {
                throw ValidationException::withMessages([
                    "itens.{$index}.itemable_id" =>
                        'O mesmo produto não pode ser adicionado mais de uma vez à Nota.',
                ]);
            }

            $produtos[$produtoId] =
                true;
        }
    }

    private function validarECalcularItens(
        Nota $nota,
        array $itens,
        ?int $clienteId,
        ?int $veiculoClienteId
    ): array {
        $subtotalGeral = 0;
        $descontoGeral = 0;

        foreach ($itens as $index => $dadosItem) {
            if (!empty($dadosItem['id'])) {
                $itemPertence =
                    NotasItem::query()
                        ->whereKey(
                            $dadosItem['id']
                        )
                        ->where(
                            'nota_id',
                            $nota->id
                        )
                        ->exists();

                if (!$itemPertence) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.id" =>
                            'Um dos itens enviados não pertence a esta Nota.',
                    ]);
                }
            }

            $tipo =
                $dadosItem['itemable_type'];

            $itemId =
                (int) $dadosItem['itemable_id'];

            $quantidade =
                (int) $dadosItem['quantidade'];

            $valorUnitario =
                (float) $dadosItem['valor_unitario'];

            $desconto =
                (float) (
                    $dadosItem['desconto']
                    ?? 0
                );

            if (
                $tipo
                === Produto::class
            ) {
                $produtoExiste =
                    Produto::query()
                        ->whereKey(
                            $itemId
                        )
                        ->exists();

                if (!$produtoExiste) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.itemable_id" =>
                            'O produto informado não existe.',
                    ]);
                }
            }

            if (
                $tipo
                === OrdemServico::class
            ) {
                $this->validarOrdemServico(
                    $nota,
                    $itemId,
                    $index,
                    $clienteId,
                    $veiculoClienteId
                );
            }

            $subtotalItem =
                $quantidade
                * $valorUnitario;

            if (
                $desconto
                > $subtotalItem
            ) {
                throw ValidationException::withMessages([
                    "itens.{$index}.desconto" =>
                        'O desconto do item não pode ser maior que o valor do item.',
                ]);
            }

            $subtotalGeral +=
                $subtotalItem;

            $descontoGeral +=
                $desconto;
        }

        return [
            $subtotalGeral,
            $descontoGeral,
        ];
    }

    private function validarOrdemServico(
        Nota $nota,
        int $ordemServicoId,
        int $index,
        ?int $clienteId,
        ?int $veiculoClienteId
    ): void {
        $ordemServico =
            OrdemServico::query()
                ->select([
                    'id',
                    'cliente_id',
                    'veiculo_cliente_id',
                ])
                ->find(
                    $ordemServicoId
                );

        if (!$ordemServico) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" =>
                    'A Ordem de Serviço informada não existe.',
            ]);
        }

        $vinculoOutraNota =
            NotasItem::query()
                ->where(
                    'itemable_type',
                    OrdemServico::class
                )
                ->where(
                    'itemable_id',
                    $ordemServicoId
                )
                ->where(
                    'nota_id',
                    '!=',
                    $nota->id
                )
                ->first();

        if ($vinculoOutraNota) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" =>
                    "A Ordem de Serviço #{$ordemServicoId} já está vinculada à Nota #{$vinculoOutraNota->nota_id}.",
            ]);
        }

        if (
            $clienteId
            && $ordemServico->cliente_id
            && (int) $ordemServico->cliente_id
                !== $clienteId
        ) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" =>
                    'A Ordem de Serviço pertence a outro cliente.',
            ]);
        }

        if (
            $veiculoClienteId
            && $ordemServico->veiculo_cliente_id
            && (int) $ordemServico->veiculo_cliente_id
                !== $veiculoClienteId
        ) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" =>
                    'A Ordem de Serviço pertence a outro veículo.',
            ]);
        }
    }

    private function salvarItem(
        Nota $nota,
        array $dadosItem
    ): NotasItem {
        $itemId =
            $dadosItem['id']
            ?? null;

        $quantidade =
            (int) $dadosItem['quantidade'];

        $valorUnitario =
            (float) $dadosItem['valor_unitario'];

        $desconto =
            (float) (
                $dadosItem['desconto']
                ?? 0
            );

        $valorTotal = max(
            0,
            (
                $quantidade
                * $valorUnitario
            ) - $desconto
        );

        $dadosParaSalvar = [
            'nota_id' =>
                $nota->id,

            'itemable_type' =>
                $dadosItem['itemable_type'],

            'itemable_id' =>
                $dadosItem['itemable_id'],

            'descricao' =>
                $dadosItem['descricao'],

            'quantidade' =>
                $quantidade,

            'valor_unitario' =>
                $valorUnitario,

            'desconto' =>
                $desconto,

            'valor_total' =>
                $valorTotal,

            'garantia_dias' =>
                null,

            'garantia_inicio' =>
                null,

            'garantia_fim' =>
                null,
        ];

        if (
            !empty(
                $dadosItem['garantia_dias']
            )
            && (int) $dadosItem['garantia_dias'] > 0
        ) {
            $garantiaDias =
                (int) $dadosItem['garantia_dias'];

            $dadosParaSalvar['garantia_dias'] =
                $garantiaDias;

            $dadosParaSalvar['garantia_inicio'] =
                now()->format('Y-m-d');

            $dadosParaSalvar['garantia_fim'] =
                now()
                    ->addDays(
                        $garantiaDias
                    )
                    ->format('Y-m-d');
        }

        if ($itemId) {
            $item = NotasItem::query()
                ->whereKey($itemId)
                ->where(
                    'nota_id',
                    $nota->id
                )
                ->first();

            if (!$item) {
                throw ValidationException::withMessages([
                    'itens' =>
                        'Um dos itens enviados para atualização não pertence a esta Nota.',
                ]);
            }

            $item->update(
                $dadosParaSalvar
            );

            return $item;
        }

        return NotasItem::create(
            $dadosParaSalvar
        );
    }
}
