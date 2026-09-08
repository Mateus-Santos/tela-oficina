<?php

namespace App\Http\Controllers;

use App\Actions\Anexo\CriarAnexo;
use App\Actions\Anexo\ExcluirAnexo;
use App\Http\Requests\Anexo\StoreAnexoRequest;
use App\Models\Anexo;
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
        Anexo $anexo,
        ExcluirAnexo $excluirAnexo
    ): RedirectResponse {
        $anexavel = $anexo->anexavel;

        if ($anexavel instanceof Compra) {
            if (in_array($anexavel->status, ['aprovada', 'cancelada'], true)) {
                return redirect()
                    ->route('compras.show', $anexavel)
                    ->with('error', 'Anexos de uma compra aprovada ou cancelada não podem ser excluídos.');
            }

            $excluirAnexo->execute($anexo);

            return redirect()
                ->route('compras.show', $anexavel)
                ->with('success', 'Anexo excluído com sucesso!');
        }

        if ($anexavel instanceof ContaPagar) {
            if (in_array($anexavel->status, ['paga', 'cancelada'], true)) {
                return redirect()
                    ->route('contas-pagar.show', $anexavel)
                    ->with('error', 'Anexos de uma conta a pagar paga ou cancelada não podem ser excluídos.');
            }

            $excluirAnexo->execute($anexo);

            return redirect()
                ->route('contas-pagar.show', $anexavel)
                ->with('success', 'Anexo excluído com sucesso!');
        }

        $excluirAnexo->execute($anexo);

        return redirect()
            ->route('compras.index')
            ->with('success', 'Anexo excluído com sucesso!');
    }
}
