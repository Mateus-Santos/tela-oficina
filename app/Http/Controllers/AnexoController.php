<?php

namespace App\Http\Controllers;

use App\Actions\Anexo\AtualizarAnexo;
use App\Actions\Anexo\CriarAnexo;
use App\Actions\Anexo\ExcluirAnexo;
use App\Http\Requests\Anexo\StoreAnexoRequest;
use App\Http\Requests\Anexo\UpdateAnexoRequest;
use App\Models\Anexo;
use App\Models\AnexoVinculo;
use App\Models\Compra;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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
            ->route(
                'compras.show',
                $compra
            )
            ->with(
                'success',
                'Anexo enviado com sucesso!'
            );
    }

    public function storeContaPagar(
        StoreAnexoRequest $request,
        ContaPagar $conta,
        CriarAnexo $criarAnexo
    ): RedirectResponse {
        if (
            in_array(
                $conta->status,
                [
                    'paga',
                    'cancelada',
                ],
                true
            )
        ) {
            return redirect()
                ->route(
                    'contas-pagar.show',
                    $conta
                )
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
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Anexo enviado com sucesso!'
            );
    }

    public function storeContaReceber(
        StoreAnexoRequest $request,
        ContaReceber $contaReceber,
        CriarAnexo $criarAnexo
    ): RedirectResponse {
        $criarAnexo->execute(
            $contaReceber,
            $request->file('arquivo'),
            $request->validated('tipo'),
            $request->validated('observacoes')
        );

        return redirect()
            ->route(
                'contas-receber.show',
                $contaReceber
            )
            ->with(
                'success',
                'Anexo enviado com sucesso!'
            );
    }

    public function update(
        UpdateAnexoRequest $request,
        AnexoVinculo $vinculo,
        AtualizarAnexo $atualizarAnexo
    ): RedirectResponse {
        $anexavel = $vinculo->vinculavel;

        $this->validarAnexavel($anexavel);

        if (
            $anexavel instanceof ContaPagar
            && in_array(
                $anexavel->status,
                [
                    'paga',
                    'cancelada',
                ],
                true
            )
        ) {
            return $this
                ->redirecionarParaAnexavel($anexavel)
                ->with(
                    'error',
                    'Anexos de uma conta a pagar paga ou cancelada não podem ser alterados.'
                );
        }

        $atualizarAnexo->execute(
            $vinculo,
            $request->validated('tipo'),
            $request->validated('observacoes'),
            $request->file('arquivo')
        );

        return $this
            ->redirecionarParaAnexavel($anexavel)
            ->with(
                'success',
                'Anexo atualizado com sucesso!'
            );
    }

    public function show(
        Anexo $anexo
    ): View {
        abort_unless(
            Storage::disk('private')->exists(
                $anexo->arquivo
            ),
            404
        );

        $anexo->load([
            'vinculos.vinculavel',
        ]);

        return view(
            'anexo.show',
            compact('anexo')
        );
    }

    public function preview(
        Anexo $anexo
    ): StreamedResponse {
        abort_unless(
            Storage::disk('private')->exists(
                $anexo->arquivo
            ),
            404
        );

        return Storage::disk('private')
            ->response(
                $anexo->arquivo,
                $anexo->nome_original,
                [
                    'Content-Type' => $anexo->mime_type,

                    'Content-Disposition' => 'inline; filename="'
                        . $anexo->nome_original
                        . '"',

                    'X-Content-Type-Options' => 'nosniff',

                    'Cache-Control' => 'private, no-store, no-cache, must-revalidate',

                    'Pragma' => 'no-cache',
                ]
            );
    }

    public function download(
        Anexo $anexo
    ): StreamedResponse {
        abort_unless(
            Storage::disk('private')->exists(
                $anexo->arquivo
            ),
            404
        );

        return Storage::disk('private')
            ->download(
                $anexo->arquivo,
                $anexo->nome_original,
                [
                    'X-Content-Type-Options' => 'nosniff',

                    'Cache-Control' => 'private, no-store, no-cache, must-revalidate',

                    'Pragma' => 'no-cache',
                ]
            );
    }

    public function destroy(
        AnexoVinculo $vinculo,
        ExcluirAnexo $excluirAnexo
    ): RedirectResponse {
        $anexavel = $vinculo->vinculavel;

        $this->validarAnexavel($anexavel);

        if (
            $anexavel instanceof ContaPagar
            && in_array(
                $anexavel->status,
                [
                    'paga',
                    'cancelada',
                ],
                true
            )
        ) {
            return $this
                ->redirecionarParaAnexavel($anexavel)
                ->with(
                    'error',
                    'Anexos de uma conta a pagar paga ou cancelada não podem ser excluídos.'
                );
        }

        $excluirAnexo->execute(
            $vinculo
        );

        return $this
            ->redirecionarParaAnexavel($anexavel)
            ->with(
                'success',
                'Anexo excluído com sucesso!'
            );
    }

    private function validarAnexavel(
        ?Model $anexavel
    ): void {
        abort_unless(
            $anexavel instanceof Compra
            || $anexavel instanceof ContaPagar
            || $anexavel instanceof ContaReceber,
            404
        );
    }

    private function redirecionarParaAnexavel(
        Model $anexavel
    ): RedirectResponse {
        if ($anexavel instanceof Compra) {
            return redirect()
                ->route(
                    'compras.show',
                    $anexavel
                );
        }

        if ($anexavel instanceof ContaPagar) {
            return redirect()
                ->route(
                    'contas-pagar.show',
                    $anexavel
                );
        }

        if ($anexavel instanceof ContaReceber) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $anexavel
                );
        }

        abort(404);
    }
}
