<?php

namespace App\Http\Controllers;

use App\Actions\Anexo\CriarAnexo;
use App\Actions\Anexo\ExcluirAnexo;
use App\Http\Requests\Anexo\StoreAnexoRequest;
use App\Models\Anexo;
use App\Models\Compra;
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

    public function download(Anexo $anexo): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($anexo->arquivo), 404);

        return Storage::disk('public')->download(
            $anexo->arquivo,
            $anexo->nome_original
        );
    }

    public function destroy(
        Anexo $anexo,
        ExcluirAnexo $excluirAnexo
    ): RedirectResponse {
        $compra = $anexo->anexavel;

        if ($compra instanceof Compra && in_array($compra->status, ['aprovada', 'cancelada'], true)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Anexos de uma compra aprovada ou cancelada não podem ser excluídos.');
        }

        $excluirAnexo->execute($anexo);

        if ($compra instanceof Compra) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Anexo excluído com sucesso!');
        }

        return redirect()
            ->route('compras.index')
            ->with('success', 'Anexo excluído com sucesso!');
    }
}
