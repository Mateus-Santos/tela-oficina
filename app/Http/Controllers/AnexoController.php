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
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\ContaPagar;
use App\Models\ContaPagarSocio;
use App\Models\ContaReceber;
use App\Models\Socio;
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

    public function storeNota(
        StoreAnexoRequest $request, Nota $nota, CriarAnexo $criarAnexo
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($nota->status !== 'Aberto') {
            return redirect()->route('notas.show', $nota)
                ->with('error', 'Anexos bloqueados para Nota encerrada.');
        }
        $criarAnexo->execute($nota, $request->file('arquivo'),
            $request->validated('tipo'), $request->validated('observacoes'));
        return redirect()->route('notas.show', $nota)
            ->with('success', 'Anexo salvo com sucesso.');
    }

    public function storeOrdemServico(
        StoreAnexoRequest $request, OrdemServico $ordemservico, CriarAnexo $criarAnexo
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($ordemservico->status !== 'aberta') {
            return redirect()->route('ordemservicos.anexos', $ordemservico)
                ->with('error', 'Anexos bloqueados para OS encerrada.');
        }
        $criarAnexo->execute($ordemservico, $request->file('arquivo'),
            $request->validated('tipo'), $request->validated('observacoes'));
        return redirect()->route('ordemservicos.anexos', $ordemservico)
            ->with('success', 'Anexo salvo com sucesso.');
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

    public function storeContaPagarSocio(
        StoreAnexoRequest $request,
        Socio $socio,
        ContaPagarSocio $conta,
        CriarAnexo $criarAnexo
    ): RedirectResponse {
        abort_unless((int) $conta->socio_id === (int) $socio->id, 404);
        $destino = route('socios-financeiro.contas.show', [$socio, $conta]);
        if (in_array($conta->status, ['paga', 'cancelada'], true)) {
            return redirect($destino)->with('error', 'Conta encerrada: anexos bloqueados.');
        }
        $criarAnexo->execute(
            $conta,
            $request->file('arquivo'),
            $request->validated('tipo'),
            $request->validated('observacoes')
        );
        return redirect($destino)->with('success', 'Anexo enviado com sucesso!');
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
        if ($anexavel instanceof Nota && $anexavel->status !== 'Aberto') {
            return $this->redirecionarParaAnexavel($anexavel)
                ->with('error', 'Nota encerrada: anexos nao podem ser alterados.');
        }
        if ($anexavel instanceof OrdemServico && $anexavel->status !== 'aberta') {
            return $this->redirecionarParaAnexavel($anexavel)
                ->with('error', 'OS encerrada: anexos nao podem ser alterados.');
        }

        if (
            ($anexavel instanceof ContaPagar || $anexavel instanceof ContaPagarSocio)
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

    public function preview(Anexo $anexo): StreamedResponse
    {
        $disco = Storage::disk('private');
        abort_unless($disco->exists($anexo->arquivo), 404);

        $headers = [
            'Content-Type' => $anexo->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        $inline = in_array(strtolower((string) $anexo->mime_type), [
            'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
        ], true);

        return $inline
            ? $disco->response($anexo->arquivo, $anexo->nome_original, $headers)
            : $disco->download($anexo->arquivo, $anexo->nome_original, $headers);
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
        if ($anexavel instanceof Nota && $anexavel->status !== 'Aberto') {
            return $this->redirecionarParaAnexavel($anexavel)
                ->with('error', 'Nota encerrada: anexos nao podem ser alterados.');
        }
        if ($anexavel instanceof OrdemServico && $anexavel->status !== 'aberta') {
            return $this->redirecionarParaAnexavel($anexavel)
                ->with('error', 'OS encerrada: anexos nao podem ser alterados.');
        }

        if (
            ($anexavel instanceof ContaPagar || $anexavel instanceof ContaPagarSocio)
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
            || $anexavel instanceof ContaPagarSocio
            || $anexavel instanceof ContaReceber
            || $anexavel instanceof Nota
            || $anexavel instanceof OrdemServico,
            404
        );
    }

    private function redirecionarParaAnexavel(
        Model $anexavel
    ): RedirectResponse {
        if ($anexavel instanceof Nota) {
            return redirect()->route('notas.show', $anexavel);
        }
        if ($anexavel instanceof OrdemServico) {
            return redirect()->route('ordemservicos.anexos', $anexavel);
        }
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

        if ($anexavel instanceof ContaPagarSocio) {
            return redirect()->route(
                'socios-financeiro.contas.show',
                [$anexavel->socio_id, $anexavel]
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
