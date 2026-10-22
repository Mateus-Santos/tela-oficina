<?php

namespace App\Http\Controllers;

use App\Actions\ContasPagar\AtualizarContaPagar;
use App\Actions\ContasPagar\AtualizarParcelasContaPagar;
use App\Actions\ContasPagar\CancelarContaPagar;
use App\Actions\ContasPagar\CriarContaPagar;
use App\Actions\ContasPagar\EstornarPagamentoContaPagar;
use App\Actions\ContasPagar\RegistrarPagamentoContaPagar;
use App\Http\Requests\ContasPagar\AtualizarParcelasContaPagarRequest;
use App\Http\Requests\ContasPagar\CancelarContaPagarRequest;
use App\Http\Requests\ContasPagar\EstornarPagamentoContaPagarRequest;
use App\Http\Requests\ContasPagar\RegistrarPagamentoContaPagarRequest;
use App\Http\Requests\ContasPagar\StoreContaPagarRequest;
use App\Http\Requests\ContasPagar\UpdateContaPagarRequest;
use App\Models\CategoriaFinanceira;
use App\Models\ContaPagar;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\Nota;
use App\Models\PagamentoContaPagar;
use App\Models\ParcelaContaPagar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ContaPagarController extends Controller
{
    public function index(): View
    {
        $query = ParcelaContaPagar::query()
            ->with([
                'contaPagar' => fn ($query) => $query
                    ->with([
                        'fornecedor',
                        'categoriaFinanceira',
                        'formaPagamento',
                    ])
                    ->withCount('parcelas'),
            ])
            ->withSum(
                'pagamentosAtivos as valor_pago',
                'valor'
            );

        $valorPagoSubquery = function ($query) {
            $query
                ->from(
                    (new PagamentoContaPagar())
                        ->getTable()
                )
                ->selectRaw(
                    'COALESCE(SUM(valor), 0)'
                )
                ->whereColumn(
                    'parcela_conta_pagar_id',
                    'parcelas_contas_pagar.id'
                )
                ->whereNull('estornado_em');
        };

        $aplicarPendente = function ($query) use (
            $valorPagoSubquery
        ) {
            $query->where(
                'valor',
                '>',
                $valorPagoSubquery
            );
        };

        $aplicarPaga = function ($query) use (
            $valorPagoSubquery
        ) {
            $query->where(
                'valor',
                '<=',
                $valorPagoSubquery
            );
        };

        if (request()->filled('descricao')) {
            $descricao = trim(
                request('descricao')
            );

            $query->whereHas(
                'contaPagar',
                fn ($query) => $query->where(
                    'descricao',
                    'like',
                    '%' . $descricao . '%'
                )
            );
        }

        if (request()->filled('fornecedor_id')) {
            $fornecedorId = request(
                'fornecedor_id'
            );

            $query->whereHas(
                'contaPagar',
                fn ($query) => $query->where(
                    'fornecedor_id',
                    $fornecedorId
                )
            );
        }

        $status = request('status');

        switch ($status) {
            case 'aberta':
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        'aberta'
                    )
                );

                $aplicarPendente($query);
                break;

            case 'parcialmente_paga':
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        'parcialmente_paga'
                    )
                );

                $aplicarPendente($query);
                break;

            case 'paga':
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        '!=',
                        'cancelada'
                    )
                );

                $aplicarPaga($query);
                break;

            case 'vencida':
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        '!=',
                        'cancelada'
                    )
                );

                $aplicarPendente($query);

                $query->whereDate(
                    'data_vencimento',
                    '<',
                    today()
                );
                break;

            case 'cancelada':
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        'cancelada'
                    )
                );
                break;

            case 'todas':
                break;

            default:
                $query->whereHas(
                    'contaPagar',
                    fn ($query) => $query->where(
                        'status',
                        '!=',
                        'cancelada'
                    )
                );

                $aplicarPendente($query);
                break;
        }

        if (
            request()->filled(
                'data_emissao_inicio'
            )
        ) {
            $data = request(
                'data_emissao_inicio'
            );

            $query->whereHas(
                'contaPagar',
                fn ($query) => $query->whereDate(
                    'data_emissao',
                    '>=',
                    $data
                )
            );
        }

        if (
            request()->filled(
                'data_emissao_fim'
            )
        ) {
            $data = request(
                'data_emissao_fim'
            );

            $query->whereHas(
                'contaPagar',
                fn ($query) => $query->whereDate(
                    'data_emissao',
                    '<=',
                    $data
                )
            );
        }

        if (
            request()->filled(
                'data_vencimento_inicio'
            )
        ) {
            $query->whereDate(
                'data_vencimento',
                '>=',
                request(
                    'data_vencimento_inicio'
                )
            );
        }

        if (
            request()->filled(
                'data_vencimento_fim'
            )
        ) {
            $query->whereDate(
                'data_vencimento',
                '<=',
                request(
                    'data_vencimento_fim'
                )
            );
        }

        $parcelasResumo = (clone $query)
            ->get();

        $total = (float) $parcelasResumo
            ->sum(
                fn ($parcela) =>
                    (float) $parcela->valor
            );

        $totalPago = (float) $parcelasResumo
            ->sum(
                fn ($parcela) =>
                    (float) (
                        $parcela->valor_pago
                        ?? 0
                    )
            );

        $totalEmAberto = (float)
            $parcelasResumo->sum(
                fn ($parcela) => max(
                    0,
                    round(
                        (float) $parcela->valor
                        - (float) (
                            $parcela->valor_pago
                            ?? 0
                        ),
                        2
                    )
                )
            );

        $totalVencido = (float)
            $parcelasResumo
                ->filter(
                    function ($parcela) {
                        $conta =
                            $parcela->contaPagar;

                        $saldo = max(
                            0,
                            round(
                                (float)
                                    $parcela->valor
                                - (float) (
                                    $parcela
                                        ->valor_pago
                                    ?? 0
                                ),
                                2
                            )
                        );

                        return
                            $conta->status
                                !== 'cancelada'
                            && $saldo > 0
                            && $parcela
                                ->data_vencimento
                                ->isBefore(
                                    today()
                                );
                    }
                )
                ->sum(
                    function ($parcela) {
                        return max(
                            0,
                            round(
                                (float)
                                    $parcela->valor
                                - (float) (
                                    $parcela
                                        ->valor_pago
                                    ?? 0
                                ),
                                2
                            )
                        );
                    }
                );

        $resumo = [
            'total' => $total,
            'total_pago' => $totalPago,
            'total_em_aberto' => $totalEmAberto,
            'total_vencido' => $totalVencido,
        ];

        $parcelasPagar = $query
            ->orderBy('data_vencimento')
            ->orderBy('conta_pagar_id')
            ->orderBy('numero')
            ->paginate(15)
            ->withQueryString();

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view(
            'contas_pagar.index',
            compact(
                'parcelasPagar',
                'fornecedores',
                'resumo'
            )
        );
    }

    public function create(): View
    {
        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->where('status', '!=', 'Cancelado')
            ->latest()
            ->get();

        $categoriasFinanceiras = CategoriaFinanceira::query()
            ->where('tipo', 'saida')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $formasPagamento = FormaPagamento::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'contas_pagar.create',
            compact(
                'fornecedores',
                'notas',
                'categoriasFinanceiras',
                'formasPagamento'
            )
        );
    }

    public function store(
        StoreContaPagarRequest $request,
        CriarContaPagar $action
    ): RedirectResponse {
        $conta = $action->execute(
            $request->validated()
        );

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Conta a pagar criada com sucesso.'
            );
    }

    public function show(ContaPagar $conta): View
    {
        $conta->load([
            'compra' => fn ($query) => $query->with([
                'anexosVinculos' => fn ($query) => $query
                    ->with('anexo')
                    ->latest(),
            ]),
            'fornecedor',
            'nota',
            'categoriaFinanceira',
            'formaPagamento',
            'parcelas',
            'anexosVinculos' => fn ($query) => $query
                ->with('anexo')
                ->latest(),
            'pagamentos' => fn ($query) => $query
                ->with([
                    'formaPagamento',
                    'parcela',
                ])
                ->latest('data_pagamento'),
        ]);

        $valorPago = (float) $conta
            ->pagamentosAtivos()
            ->sum('valor');

        $conta->setAttribute(
            'valor_pago',
            $valorPago
        );

        $formasPagamento = FormaPagamento::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'contas_pagar.show',
            compact(
                'conta',
                'formasPagamento'
            )
        );
    }

    public function edit(ContaPagar $conta): View
    {
        $valorPago = (float) $conta
            ->pagamentosAtivos()
            ->sum('valor');

        $conta->setAttribute(
            'valor_pago',
            $valorPago
        );

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->where(function ($query) use ($conta) {
                $query->where('status', '!=', 'Cancelado')
                    ->orWhere('id', $conta->nota_id);
            })
            ->latest()
            ->get();

        $categoriasFinanceiras = CategoriaFinanceira::query()
            ->where('tipo', 'saida')
            ->where(function ($query) use ($conta) {
                $query->where('ativo', true)
                    ->orWhere(
                        'id',
                        $conta->categoria_financeira_id
                    );
            })
            ->orderBy('nome')
            ->get();

        $formasPagamento = FormaPagamento::query()
            ->where(function ($query) use ($conta) {
                $query->where('ativo', true)
                    ->orWhere(
                        'id',
                        $conta->forma_pagamento_id
                    );
            })
            ->orderBy('nome')
            ->get();

        return view(
            'contas_pagar.edit',
            compact(
                'conta',
                'fornecedores',
                'notas',
                'categoriasFinanceiras',
                'formasPagamento'
            )
        );
    }

    public function update(
        UpdateContaPagarRequest $request,
        ContaPagar $conta,
        AtualizarContaPagar $action
    ): RedirectResponse {
        try {
            $action->execute(
                $conta,
                $request->validated()
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'conta' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Conta a pagar atualizada com sucesso.'
            );
    }

    public function atualizarParcelas(
        AtualizarParcelasContaPagarRequest $request,
        ContaPagar $conta,
        AtualizarParcelasContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $request->validated('parcelas')
        );

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Parcelas atualizadas com sucesso.'
            );
    }

    public function registrarPagamento(
        RegistrarPagamentoContaPagarRequest $request,
        ContaPagar $conta,
        RegistrarPagamentoContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $request->validated()
        );

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Pagamento registrado com sucesso.'
            );
    }

    public function estornarPagamento(
        EstornarPagamentoContaPagarRequest $request,
        ContaPagar $conta,
        PagamentoContaPagar $pagamento,
        EstornarPagamentoContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $pagamento,
            $request->validated('motivo')
        );

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Pagamento estornado com sucesso.'
            );
    }

    public function cancelar(
        CancelarContaPagarRequest $request,
        ContaPagar $conta,
        CancelarContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $request->validated('motivo')
        );

        return redirect()
            ->route(
                'contas-pagar.show',
                $conta
            )
            ->with(
                'success',
                'Conta a pagar cancelada com sucesso.'
            );
    }
}
