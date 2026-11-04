<?php

namespace App\Http\Controllers;

use App\Actions\FinanceiroSocios\CriarContaPagarSocio;
use App\Actions\FinanceiroSocios\EstornarPagamentoSocio;
use App\Actions\FinanceiroSocios\EstornarRetiradaSocio;
use App\Actions\FinanceiroSocios\RegistrarPagamentoSocio;
use App\Actions\FinanceiroSocios\RegistrarRetiradaSocio;
use App\Models\ContaPagarSocio;
use App\Models\FormaPagamento;
use App\Models\PagamentoContaPagarSocio;
use App\Models\RetiradaSocio;
use App\Models\Socio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class FinanceiroSociosController extends Controller
{
    private const CATEGORIAS = [
        'Moradia', 'Alimentacao', 'Transporte', 'Cartao de credito',
        'Financiamento', 'Emprestimo', 'Saude', 'Educacao', 'Lazer', 'Outros',
    ];

    public function index(): View
    {
        $socios = Socio::query()
            ->with(['contas.pagamentosAtivos', 'retiradasAtivas'])
            ->orderBy('nome')->get();

        return view('financeiro.socios.index', compact('socios'));
    }

    public function storeSocio(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:2', 'max:150'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        $socio = Socio::create($dados);

        return redirect()->route('socios-financeiro.show', $socio)
            ->with('success', 'Socio cadastrado com sucesso.');
    }

    public function show(Socio $socio): View
    {
        $socio->load([
            'contas' => fn ($query) => $query->latest(),
            'contas.pagamentosAtivos',
            'retiradas' => fn ($query) => $query->latest('data_retirada')->latest('id'),
        ]);

        return view('financeiro.socios.show', [
            'socio' => $socio,
            'categorias' => self::CATEGORIAS,
        ]);
    }

    public function storeConta(Request $request, Socio $socio, CriarContaPagarSocio $action): RedirectResponse
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'credor' => ['nullable', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'in:' . implode(',', self::CATEGORIAS)],
            'valor' => ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'data_emissao' => ['required', 'date'],
            'primeiro_vencimento' => ['required', 'date', 'after_or_equal:data_emissao'],
            'quantidade_parcelas' => ['required', 'integer', 'min:1', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $conta = $action->execute($socio, $dados);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['valor' => $e->getMessage()]);
        }

        return redirect()->route('socios-financeiro.contas.show', [$socio, $conta])
            ->with('success', 'Divida pessoal e parcelas cadastradas.');
    }

    public function showConta(Socio $socio, ContaPagarSocio $conta): View
    {
        abort_unless((int) $conta->socio_id === (int) $socio->id, 404);
        $conta->load(['parcelas.pagamentosAtivos', 'pagamentos.retirada']);
        $formas = FormaPagamento::query()->where('ativo', true)->orderBy('nome')->get();

        return view('financeiro.socios.conta', compact('socio', 'conta', 'formas'));
    }

    public function storePagamento(
        Request $request,
        Socio $socio,
        ContaPagarSocio $conta,
        RegistrarPagamentoSocio $action
    ): RedirectResponse {
        abort_unless((int) $conta->socio_id === (int) $socio->id, 404);
        $dados = $request->validate([
            'parcela_id' => ['required', 'integer', 'exists:parcelas_contas_pagar_socios,id'],
            'valor' => ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'data_pagamento' => ['required', 'date'],
            'origem' => ['required', 'in:pessoal,empresa'],
            'forma_pagamento_id' => ['nullable', 'integer', 'exists:formas_pagamento,id'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $action->execute($conta, $dados);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['valor' => $e->getMessage()]);
        }

        return back()->with('success', 'Pagamento registrado com sucesso.');
    }

    public function estornarPagamento(
        Request $request,
        Socio $socio,
        ContaPagarSocio $conta,
        PagamentoContaPagarSocio $pagamento,
        EstornarPagamentoSocio $action
    ): RedirectResponse {
        abort_unless((int) $conta->socio_id === (int) $socio->id, 404);
        abort_unless((int) $pagamento->conta_pagar_socio_id === (int) $conta->id, 404);
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $action->execute($conta, $pagamento, $dados['motivo']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['motivo' => $e->getMessage()]);
        }

        return back()->with('success', 'Pagamento estornado. Retirada vinculada revertida, se existente.');
    }

    public function storeRetirada(Request $request, Socio $socio, RegistrarRetiradaSocio $action): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:retirada_livre,remuneracao'],
            'valor' => ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'data_retirada' => ['required', 'date'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $action->execute($socio, $dados);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['valor' => $e->getMessage()]);
        }

        return back()->with('success', 'Saida pessoal registrada no historico do socio.');
    }

    public function estornarRetirada(
        Request $request,
        Socio $socio,
        RetiradaSocio $retirada,
        EstornarRetiradaSocio $action
    ): RedirectResponse {
        abort_unless((int) $retirada->socio_id === (int) $socio->id, 404);
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $action->execute($socio, $retirada, $dados['motivo']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['motivo' => $e->getMessage()]);
        }

        return back()->with('success', 'Retirada estornada com historico preservado.');
    }
}
