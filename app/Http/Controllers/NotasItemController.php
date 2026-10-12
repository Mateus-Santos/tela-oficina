<?php

namespace App\Http\Controllers;

use App\Actions\Notas\AtualizarNota;
use App\Actions\Notas\CriarNota;
use App\Http\Requests\Notas\StoreNotaRequest;
use App\Http\Requests\Notas\UpdateNotaRequest;
use App\Models\Nota;
use App\Models\NotasItem;

class NotasItemController extends Controller
{
    /**
     * Mantém compatibilidade com a rota notasitem.index.
     *
     * A listagem oficial de Notas fica centralizada
     * em NotaController@index.
     */
    public function index()
    {
        return redirect()
            ->route('notas.index');
    }

    /**
     * Exibe o formulário de cadastro.
     */
    public function create()
    {
        return view(
            'notas_item.cadastro_notas_itens'
        );
    }

    /**
     * Cria uma nova Nota.
     */
    public function store(
        StoreNotaRequest $request,
        CriarNota $criarNota
    ) {
        $nota = $criarNota->execute(
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route('notas.index')
            ->with(
                'success',
                'Nota Fiscal criada com '
                .$nota->itens->count()
                .' itens!'
            );
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculosCliente.veiculo.montadora',
            'itens.itemable',
        ])->findOrFail($id);

        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' => 'Notas finalizadas ou canceladas não podem ser editadas.',
                ]);
        }

        return view(
            'notas_item.editar_notas_itens',
            compact('nota')
        );
    }

    /**
     * Atualiza uma Nota existente.
     */
    public function update(
        UpdateNotaRequest $request,
        string $id,
        AtualizarNota $atualizarNota
    ) {
        $nota = Nota::findOrFail($id);

        $nota = $atualizarNota->execute(
            $nota,
            $request->validated()
        );

        return redirect()
            ->route('notas.index')
            ->with(
                'success',
                'Nota Fiscal #'
                .$nota->id
                .' atualizada com sucesso!'
            );
    }

    /**
     * Remove um item da Nota.
     */
    public function destroy(string $id)
    {
        $item = NotasItem::findOrFail($id);

        $nota = Nota::findOrFail(
            $item->nota_id
        );

        /*
         * Itens só podem ser removidos
         * enquanto a Nota estiver aberta.
         */
        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' => 'Não é possível remover itens de uma nota finalizada ou cancelada.',
                ]);
        }

        $item->delete();

        /*
         * Recalcula os valores da Nota.
         */
        $nota->load('itens');

        $subtotalGeral =
            $nota->itens->sum(
                function ($item) {
                    return
                        (float) $item->quantidade
                        * (float) $item->valor_unitario;
                }
            );

        $descontoGeral =
            $nota->itens->sum(
                function ($item) {
                    return (float) $item->desconto;
                }
            );

        $nota->update([
            'subtotal' => $subtotalGeral,

            'desconto' => $descontoGeral,

            'total' => max(
                0,
                $subtotalGeral
                - $descontoGeral
            ),
        ]);

        return redirect()
            ->route(
                'notas.show',
                $nota->id
            )
            ->with(
                'success',
                'Item removido da nota com sucesso!'
            );
    }
}
