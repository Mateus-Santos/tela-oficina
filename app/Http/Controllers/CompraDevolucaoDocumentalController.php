<?php

namespace App\Http\Controllers;

use App\Actions\Compra\RegistrarDevolucaoDocumentalCompra;
use App\Http\Requests\Compra\RegistrarDevolucaoDocumentalRequest;
use App\Models\Compra;
use App\Services\Compra\SaldoDevolucaoCompra;
use App\Services\Compra\ValorDevidoCompra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

class CompraDevolucaoDocumentalController extends Controller
{
    public function create(
        Request $request,
        Compra $compra,
        SaldoDevolucaoCompra $saldos,
        ValorDevidoCompra $valorDevido
    ): View|RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);
        if (!$compra->estaAprovada()) {
            return redirect()->route('compras.show', $compra)
                ->with('error', 'A devolucao documental exige compra aprovada.');
        }
        $compra->load(['fornecedor', 'itens.produto']);
        return view('compra.devolucoes.documental', [
            'compra' => $compra,
            'saldos' => $saldos->consultar($compra),
            'valorLiquido' => $valorDevido->valorLiquidoCentavos($compra) / 100,
            'operacaoUuid' => (string) Str::uuid(),
        ]);
    }

    public function store(
        RegistrarDevolucaoDocumentalRequest $request,
        Compra $compra,
        RegistrarDevolucaoDocumentalCompra $registrar
    ): RedirectResponse {
        try {
            $devolucao = $registrar->execute(
                $compra, $request->validated(), (int) $request->user()->id
            );
            $semConta = !$compra->contasPagar()->exists()
                && !$compra->contaPagar()->exists();
            $mensagem = $devolucao->situacao_financeira !== 'aplicada'
                ? 'Devolucao documentada. Abatimento PENDENTE de conciliacao; nenhuma parcela foi modificada.'
                : ($semConta
                    ? 'Devolucao documentada: valor liquido da compra atualizado. Sem conta vinculada: nenhuma conta a pagar antiga foi alterada. Ajuste e vincule a conta desejada.'
                    : 'Devolucao registrada: valor da compra, conta vinculada e parcelas sincronizados com seguranca.');
            return redirect()->route('compras.show', $compra)
                ->with('success', $mensagem);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
