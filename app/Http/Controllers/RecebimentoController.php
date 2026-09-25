<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\EstornarRecebimento;
use App\Actions\Financeiro\RegistrarRecebimento;
use App\Http\Requests\EstornarRecebimentoRequest;
use App\Http\Requests\StoreRecebimentoRequest;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\ParcelaContaReceber;
use App\Models\Recebimento;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class RecebimentoController extends Controller
{
    public function create(ContaReceber $contaReceber, ParcelaContaReceber $parcela)
    {
        if ($parcela->conta_receber_id !== $contaReceber->id) {
            abort(404);
        }

        if ($contaReceber->status === 'cancelada') {
            return redirect()->route('contas-receber.show', $contaReceber)->with('error', 'Não é possível receber uma conta cancelada.');
        }

        if ($contaReceber->status === 'quitada') {
            return redirect()->route('contas-receber.show', $contaReceber)->with('error', 'Esta conta já está quitada.');
        }

        $contaReceber->load(['cliente.pessoa', 'nota.cliente.pessoa']);
        $valorRecebido = (float) $parcela->recebimentosAtivos()->sum('valor');
        $saldo = max(0, round((float) $parcela->valor - $valorRecebido, 2));

        if ($saldo <= 0) {
            return redirect()->route('contas-receber.show', $contaReceber)->with('error', 'Esta parcela já está quitada.');
        }

        $formasPagamento = FormaPagamento::query()->where('ativo', true)->orderBy('nome')->get();

        return view('financeiro.recebimentos.create', compact('contaReceber', 'parcela', 'formasPagamento', 'valorRecebido', 'saldo'));
    }

    public function store(StoreRecebimentoRequest $request, RegistrarRecebimento $registrarRecebimento): RedirectResponse
    {
        try {
            $recebimento = $registrarRecebimento->execute($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['recebimento' => $e->getMessage()]);
        }

        return redirect()->route('contas-receber.show', $recebimento->contaReceber)->with('success', 'Recebimento registrado com sucesso.');
    }

    public function estornar(
        EstornarRecebimentoRequest $request,
        ContaReceber $contaReceber,
        Recebimento $recebimento,
        EstornarRecebimento $estornarRecebimento
    ): RedirectResponse {
        try {
            $estornarRecebimento->execute($contaReceber, $recebimento, $request->validated()['motivo']);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('contas-receber.show', $contaReceber)->with('error', $e->getMessage());
        }

        return redirect()->route('contas-receber.show', $contaReceber)->with('success', 'Recebimento estornado com sucesso.');
    }
}
