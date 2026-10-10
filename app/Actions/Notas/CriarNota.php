<?php

namespace App\Actions\Notas;

use App\Models\Etapa;
use App\Models\Nota;
use App\Models\NotaEtapaHistorico;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CriarNota
{
    public function execute(
        array $dados,
        ?int $userId = null
    ): Nota {
        return DB::transaction(function () use (
            $dados,
            $userId
        ) {
            $clienteId = ! empty($dados['cliente_id'])
                ? (int) $dados['cliente_id']
                : null;

            $veiculoClienteId = ! empty($dados['veiculo_cliente_id'])
                ? (int) $dados['veiculo_cliente_id']
                : null;

            $itens = $dados['itens'] ?? [];

            $this->validarClienteVeiculo(
                $clienteId,
                $veiculoClienteId
            );

            $this->validarProdutosDuplicados(
                $itens
            );

            [$subtotalGeral, $descontoGeral] =
                $this->validarECalcularItens(
                    $itens,
                    $clienteId,
                    $veiculoClienteId
                );

            $etapaInicial =
                $this->obterEtapaInicial();

            $nota = Nota::create([
                'cliente_id' => $clienteId,

                'veiculo_cliente_id' => $veiculoClienteId,

                'etapa_id' => $etapaInicial->id,

                'tipo' => 'Venda',

                'status' => 'Aberto',

                'km' => $dados['km'] ?? null,

                'km_proxima_troca_oleo' => $dados['km_proxima_troca_oleo']
                    ?? null,

                'subtotal' => $subtotalGeral,

                'desconto' => $descontoGeral,

                'total' => max(
                    0,
                    $subtotalGeral - $descontoGeral
                ),
            ]);

            NotaEtapaHistorico::create([
                'nota_id' => $nota->id,

                'etapa_origem_id' => null,

                'etapa_destino_id' => $etapaInicial->id,

                'etapa_origem_nome' => null,

                'etapa_destino_nome' => $etapaInicial->nome,

                'user_id' => $userId,

                'motivo' => 'Etapa inicial definida na criação da Nota.',
            ]);

            foreach ($itens as $dadosItem) {
                $this->criarItem(
                    $nota,
                    $dadosItem
                );
            }

            return $nota->fresh([
                'cliente.pessoa',
                'veiculosCliente',
                'etapa',
                'historicoEtapas',
                'itens.itemable',
            ]);
        });
    }

    private function obterEtapaInicial(): Etapa
    {
        $etapas =
            Etapa::query()
                ->paraNota()
                ->where(
                    'ativo',
                    true
                )
                ->where(
                    'tipo',
                    'inicial'
                )
                ->orderBy('ordem')
                ->get();

        if ($etapas->count() !== 1) {
            throw new InvalidArgumentException(
                'O fluxo de Notas deve possuir exatamente uma etapa inicial ativa.'
            );
        }

        return $etapas->first();
    }

    private function validarClienteVeiculo(
        ?int $clienteId,
        ?int $veiculoClienteId
    ): void {
        if (! $veiculoClienteId) {
            return;
        }

        if (! $clienteId) {
            throw ValidationException::withMessages([
                'cliente_id' => 'Selecione o cliente responsável pelo veículo.',
            ]);
        }

        $vinculoExiste = VeiculosCliente::query()
            ->whereKey($veiculoClienteId)
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

        if (! $vinculoExiste) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' => 'O cliente informado não está vinculado ao veículo selecionado.',
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

            $produtoId = (int) (
                $item['itemable_id']
                ?? 0
            );

            if (! $produtoId) {
                continue;
            }

            if (isset($produtos[$produtoId])) {
                throw ValidationException::withMessages([
                    "itens.{$index}.itemable_id" => 'O mesmo produto não pode ser adicionado mais de uma vez à Nota.',
                ]);
            }

            $produtos[$produtoId] = true;
        }
    }

    private function validarECalcularItens(
        array $itens,
        ?int $clienteId,
        ?int $veiculoClienteId
    ): array {
        $subtotalGeral = 0;
        $descontoGeral = 0;

        foreach ($itens as $index => $dadosItem) {
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

            if ($tipo === Produto::class) {
                $produtoExiste = Produto::query()
                    ->whereKey($itemId)
                    ->exists();

                if (! $produtoExiste) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.itemable_id" => 'O produto informado não existe.',
                    ]);
                }
            }

            if ($tipo === OrdemServico::class) {
                $this->validarOrdemServico(
                    $itemId,
                    $index,
                    $clienteId,
                    $veiculoClienteId
                );
            }

            $subtotalItem =
                $quantidade
                * $valorUnitario;

            if ($desconto > $subtotalItem) {
                throw ValidationException::withMessages([
                    "itens.{$index}.desconto" => 'O desconto do item não pode ser maior que o valor do item.',
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
                ->find($ordemServicoId);

        if (! $ordemServico) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" => 'A Ordem de Serviço informada não existe.',
            ]);
        }

        $vinculoExistente =
            NotasItem::query()
                ->where(
                    'itemable_type',
                    OrdemServico::class
                )
                ->where(
                    'itemable_id',
                    $ordemServicoId
                )
                ->first();

        if ($vinculoExistente) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" => "A Ordem de Serviço #{$ordemServicoId} já está vinculada à Nota #{$vinculoExistente->nota_id}.",
            ]);
        }

        if (
            $clienteId
            && $ordemServico->cliente_id
            && (int) $ordemServico->cliente_id
                !== $clienteId
        ) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" => 'A Ordem de Serviço pertence a outro cliente.',
            ]);
        }

        if (
            $veiculoClienteId
            && $ordemServico->veiculo_cliente_id
            && (int) $ordemServico->veiculo_cliente_id
                !== $veiculoClienteId
        ) {
            throw ValidationException::withMessages([
                "itens.{$index}.itemable_id" => 'A Ordem de Serviço pertence a outro veículo.',
            ]);
        }
    }

    private function criarItem(
        Nota $nota,
        array $dadosItem
    ): NotasItem {
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

        $dadosItemNota = [
            'nota_id' => $nota->id,

            'itemable_type' => $dadosItem['itemable_type'],

            'itemable_id' => $dadosItem['itemable_id'],

            'descricao' => $dadosItem['descricao'],

            'quantidade' => $quantidade,

            'valor_unitario' => $valorUnitario,

            'desconto' => $desconto,

            'valor_total' => $valorTotal,

            'garantia_dias' => null,

            'garantia_inicio' => null,

            'garantia_fim' => null,
        ];

        if (
            ! empty($dadosItem['garantia_dias'])
            && (int) $dadosItem['garantia_dias'] > 0
        ) {
            $garantiaDias =
                (int) $dadosItem['garantia_dias'];

            $dadosItemNota['garantia_dias'] =
                $garantiaDias;

            $dadosItemNota['garantia_inicio'] =
                now()->format('Y-m-d');

            $dadosItemNota['garantia_fim'] =
                now()
                    ->addDays($garantiaDias)
                    ->format('Y-m-d');
        }

        return NotasItem::create(
            $dadosItemNota
        );
    }
}
