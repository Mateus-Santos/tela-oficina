<?php

namespace App\Http\Controllers;

use App\Actions\Compra\RegistrarDevolucaoCompra;
use App\Http\Requests\Compra\RegistrarDevolucaoCompraRequest;
use App\Models\Compra;
use App\Models\CompraDevolucaoItem;
use App\Models\MovimentacaoEstoque;
use App\Support\Estoque\Quantidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

class CompraDevolucaoController extends Controller
{
    public function create(Request $request, Compra $compra): View|RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if (!$compra->estaAprovada()) {
            return redirect()->route('compras.show', $compra)
                ->with('error', 'Somente compras aprovadas admitem devolucao.');
        }
        if ($compra->contaPagar()->where('status', '!=', 'cancelada')->exists()
            || $compra->contasPagar()->where('contas_pagar.status', '!=', 'cancelada')->exists()) {
            return redirect()->route('compras.show', $compra)
                ->with('error', 'Existe conta a pagar ativa. Aguarde a conciliacao financeira D03.');
        }

        $compra->load(['fornecedor', 'itens.produto', 'itens.movimentacoesEstoque']);
        $disponiveis = [];
        foreach ($compra->itens as $item) {
            $base = MovimentacaoEstoque::query()
                ->where('origem_type', $item->getMorphClass())
                ->where('origem_id', $item->id);
            $entrada = Quantidade::milessimos((string) (clone $base)
                ->where('tipo', 'entrada')->sum('quantidade'));
            $devolvida = Quantidade::milessimos((string) CompraDevolucaoItem::query()
                ->where('compra_item_id', $item->id)
                ->whereHas('devolucao', fn ($q) => $q->where('status', 'efetivada'))
                ->sum('quantidade'));
            $estornoLegado = (clone $base)->where('tipo', 'saida')->exists();
            $disponiveis[$item->id] = $estornoLegado ? '0.000'
                : Quantidade::formatar(max(0, $entrada - $devolvida));
        }

        return view('compra.devolucoes.create', [
            'compra' => $compra,
            'disponiveis' => $disponiveis,
            'operacaoUuid' => (string) Str::uuid(),
        ]);
    }

    public function store(
        RegistrarDevolucaoCompraRequest $request,
        Compra $compra,
        RegistrarDevolucaoCompra $registrar
    ): RedirectResponse {
        try {
            $devolucao = $registrar->execute($compra, $request->validated(), $request->user()->id);
            return redirect()->route('compras.show', $compra)
                ->with('success', "Devolucao operacional #{$devolucao->id} registrada; estoque atualizado. Financeiro nao alterado.");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
