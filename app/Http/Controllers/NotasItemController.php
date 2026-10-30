<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\SincronizarContaReceberComNota;
use App\Actions\Notas\AtualizarNota;
use App\Actions\Notas\CriarNota;
use App\Http\Requests\Notas\StoreNotaRequest;
use App\Http\Requests\Notas\UpdateNotaRequest;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Services\Nota\CalculadorTotaisNota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function destroy(
        string $id,
        CalculadorTotaisNota $calculadorTotaisNota,
        SincronizarContaReceberComNota $sincronizarContaReceberComNota
    ) {
        $item = NotasItem::findOrFail($id);

        $nota = Nota::findOrFail(
            $item->nota_id
        );

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

        DB::transaction(function () use (
            $id,
            $nota,
            $calculadorTotaisNota,
            $sincronizarContaReceberComNota
        ) {
            $notaTransacional = Nota::query()
                ->lockForUpdate()
                ->findOrFail($nota->id);

            if ($notaTransacional->status !== 'Aberto') {
                throw ValidationException::withMessages([
                    'nota' => 'A Nota deixou de estar aberta. A exclusão foi cancelada.',
                ]);
            }

            $itemTransacional = NotasItem::query()
                ->where(
                    'nota_id',
                    $notaTransacional->id
                )
                ->lockForUpdate()
                ->findOrFail($id);

            $itemTransacional->delete();

            $notaTransacional->load('itens');

            $totais = $calculadorTotaisNota
                ->calcularItens(
                    $notaTransacional->itens
                );

            $notaTransacional->update([
                'subtotal' => $totais['subtotal'],
                'desconto' => $totais['desconto'],
                'total' => $totais['total'],
            ]);

            /*
             * Se houver Conta a Receber, sincroniza
             * os valores e as parcelas.
             *
             * Qualquer falha financeira causa rollback
             * de toda a exclusão.
             */
            $sincronizarContaReceberComNota
                ->execute(
                    $notaTransacional
                );
        });

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
