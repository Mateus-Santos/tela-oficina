<?php

namespace App\Http\Controllers;

use App\Actions\Anexo\CriarAnexo;
use App\Actions\Anexo\ExcluirAnexo;
use App\Http\Requests\Anexo\StoreAnexoRequest;
use App\Models\Anexo;
use App\Models\AnexoVinculo;
use App\Models\Compra;
use App\Models\ContaPagar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    public function storeCompra(
        StoreAnexoRequest $request,
        Compra $compra,
        CriarAnexo $criarAnexo
    ): RedirectResponse {
        if (in_array($compra->status, ['aprovada', 'cancelada'], true)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Não é possível adicionar anexos a uma compra aprovada ou cancelada.'
                );
        }

        $criarAnexo->execute(
            $compra,
            $request->file('arquivo'),
            $request->validated('tipo'),
            $request->validated('observacoes')
        );

        return redirect()
            ->route('compras.show', $compra)
            ->with('success', 'Anexo enviado com sucesso!');
    }

    public function storeContaPagar(
        StoreAnexoRequest $request,
        ContaPagar $conta,
        CriarAnexo $criarAnexo
    ): RedirectResponse {
        if (in_array($conta->status, ['paga', 'cancelada'], true)) {
            return redirect()
                ->route('contas-pagar.show', $conta)
                ->with(
                    'error',
                    'Não é possível adicionar anexos a uma conta paga ou cancelada.'
                );
        }

        $criarAnexo->execute(
            $conta,
            $request->file('arquivo'),
            $request->validated('tipo'),
            $request->validated('observacoes')
        );

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Anexo enviado com sucesso!');
    }

    public function download(Anexo $anexo): StreamedResponse
    {
        abort_unless(
            Storage::disk('public')->exists($anexo->arquivo),
            404
        );

        return Storage::disk('public')->download(
            $anexo->arquivo,
            $anexo->nome_original
        );
    }

    public function destroy(
        AnexoVinculo $vinculo,
        ExcluirAnexo $excluirAnexo
    ): RedirectResponse {
        $anexavel = $vinculo->vinculavel;

        if ($anexavel instanceof Compra) {
            if (in_array($anexavel->status, ['aprovada', 'cancelada'], true)) {
                return redirect()
                    ->route('compras.show', $anexavel)
                    ->with(
                        'error',
                        'Anexos de uma compra aprovada ou cancelada não podem ser excluídos.'
                    );
            }

            $excluirAnexo->execute($vinculo);

            return redirect()
                ->route('compras.show', $anexavel)
                ->with('success', 'Anexo excluído com sucesso!');
        }

        if ($anexavel instanceof ContaPagar) {
            if (in_array($anexavel->status, ['paga', 'cancelada'], true)) {
                return redirect()
                    ->route('contas-pagar.show', $anexavel)
                    ->with(
                        'error',
                        'Anexos de uma conta a pagar paga ou cancelada não podem ser excluídos.'
                    );
            }

            $excluirAnexo->execute($vinculo);

            return redirect()
                ->route('contas-pagar.show', $anexavel)
                ->with('success', 'Anexo excluído com sucesso!');
        }

        abort(404);
    }
}
