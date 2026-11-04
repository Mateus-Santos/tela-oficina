<?php

namespace App\Http\Controllers;

use App\Actions\FinanceiroSocios\CriarContaReceberSocio;
use App\Actions\FinanceiroSocios\EstornarRecebimentoSocio;
use App\Actions\FinanceiroSocios\RegistrarRecebimentoSocio;
use App\Models\ContaReceberSocio;
use App\Models\FormaPagamento;
use App\Models\RecebimentoContaReceberSocio;
use App\Models\Socio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class FinanceiroSociosReceberController extends Controller
{
    public function index(Socio $socio): View
    {
        $socio->load(['recebiveis' => fn ($query) => $query->latest(), 'recebiveis.recebimentosAtivos']);

        return view('financeiro.socios.receber-index', compact('socio'));
    }

    public function store(Request $request, Socio $socio, CriarContaReceberSocio $action): RedirectResponse
    {
        $dados = $request->validate([
            'devedor' => ['required', 'string', 'min:2', 'max:180'],
            'descricao' => ['required', 'string', 'max:255'],
            'origem' => ['required', 'in:dinheiro,pix,cartao,transferencia,outros'],
            'identificacao_cartao' => ['nullable', 'string', 'max:120'],
            'valor' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'data_operacao' => ['required', 'date_format:Y-m-d'],
            'primeiro_vencimento' => ['required', 'date_format:Y-m-d', 'after_or_equal:data_operacao'],
            'quantidade_parcelas' => ['required', 'integer', 'min:1', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $conta = $action->execute($socio, $dados);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['valor' => $e->getMessage()]);
        }

        return redirect()->route('socios-financeiro.receber.show', [$socio, $conta])
            ->with('success', 'Conta pessoal a receber cadastrada com parcelas.');
    }

    public function show(Socio $socio, ContaReceberSocio $recebivel): View
    {
        $this->validarPertencimento($socio, $recebivel);
        $recebivel->load(['parcelas.recebimentosAtivos', 'recebimentos.parcela', 'recebimentos.formaPagamento']);
        $formasPagamento = FormaPagamento::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('financeiro.socios.receber-show', compact('socio', 'recebivel', 'formasPagamento'));
    }

    public function registrar(
        Request $request,
        Socio $socio,
        ContaReceberSocio $recebivel,
        RegistrarRecebimentoSocio $action
    ): RedirectResponse {
        $this->validarPertencimento($socio, $recebivel);
        $dados = $request->validate([
            'parcela_id' => ['required', 'integer', 'exists:parcelas_contas_receber_socios,id'],
            'valor' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'data_recebimento' => ['required', 'date_format:Y-m-d'],
            'forma_pagamento_id' => [
                'required',
                'integer',
                Rule::exists('formas_pagamento', 'id')->where('ativo', true),
            ],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $action->execute($recebivel, $dados);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['valor' => $e->getMessage()]);
        }

        return redirect()->route('socios-financeiro.receber.show', [$socio, $recebivel])
            ->with('success', 'Recebimento pessoal registrado.');
    }

    public function estornar(
        Request $request,
        Socio $socio,
        ContaReceberSocio $recebivel,
        RecebimentoContaReceberSocio $recebimento,
        EstornarRecebimentoSocio $action
    ): RedirectResponse {
        $this->validarPertencimento($socio, $recebivel);
        abort_unless((int) $recebimento->conta_receber_socio_id === (int) $recebivel->id, 404);
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $action->execute($recebivel, $recebimento, $dados['motivo']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('socios-financeiro.receber.show', [$socio, $recebivel])
            ->with('success', 'Recebimento estornado com histórico preservado.');
    }

    private function validarPertencimento(Socio $socio, ContaReceberSocio $conta): void
    {
        abort_unless((int) $conta->socio_id === (int) $socio->id, 404);
    }
}
