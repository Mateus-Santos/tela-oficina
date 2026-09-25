<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\AtualizarContaReceber;
use App\Actions\Financeiro\CriarContaReceber;
use App\Http\Requests\StoreContaReceberRequest;
use App\Http\Requests\UpdateContaReceberRequest;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Nota;
use App\Models\ParcelaContaReceber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContaReceberController extends Controller
{
    public function index(Request $request)
    {
        $hoje = now()->toDateString();

        $aplicarFiltrosConta = function (Builder $query) use ($request, $hoje) {
            if ($request->filled('cliente')) {
                $cliente = $request->input('cliente');

                $query->where(function (Builder $query) use ($cliente) {
                    $query
                        ->whereHas('cliente.pessoa', function (Builder $query) use ($cliente) {
                            $query->where('nome', 'like', "%{$cliente}%");
                        })
                        ->orWhereHas('nota.cliente.pessoa', function (Builder $query) use ($cliente) {
                            $query->where('nome', 'like', "%{$cliente}%");
                        });
                });
            }

            if ($request->filled('nota_id')) {
                $query->where('nota_id', $request->input('nota_id'));
            }

            if ($request->filled('status')) {
                $status = $request->input('status');

                if ($status === 'vencida') {
                    $query
                        ->whereIn('status', ['aberta', 'parcial'])
                        ->whereHas('parcelas', function (Builder $query) use ($hoje) {
                            $query
                                ->whereDate('data_vencimento', '<', $hoje)
                                ->whereRaw(
                                    'parcelas_contas_receber.valor > COALESCE((
                                        SELECT SUM(recebimentos.valor)
                                        FROM recebimentos
                                        WHERE recebimentos.parcela_conta_receber_id = parcelas_contas_receber.id
                                        AND recebimentos.estornado_em IS NULL
                                    ), 0)'
                                );
                        });
                } else {
                    $query->where('status', $status);
                }
            } else {
                $query->where('status', '!=', 'cancelada');
            }

            if ($request->filled('data_inicio')) {
                $query->whereHas('parcelas', function (Builder $query) use ($request) {
                    $query->whereDate(
                        'data_vencimento',
                        '>=',
                        $request->input('data_inicio')
                    );
                });
            }

            if ($request->filled('data_fim')) {
                $query->whereHas('parcelas', function (Builder $query) use ($request) {
                    $query->whereDate(
                        'data_vencimento',
                        '<=',
                        $request->input('data_fim')
                    );
                });
            }
        };

        $queryContas = ContaReceber::query();
        $aplicarFiltrosConta($queryContas);

        $queryParcelas = ParcelaContaReceber::query()
            ->with([
                'contaReceber' => function ($query) {
                    $query
                        ->with([
                            'cliente.pessoa',
                            'nota.cliente.pessoa',
                            'categoriaFinanceira',
                        ])
                        ->withExists('recebimentos')
                        ->withCount('parcelas');
                },
            ])
            ->withSum([
                'recebimentosAtivos as valor_recebido',
            ], 'valor')
            ->whereHas('contaReceber', function (Builder $query) use ($aplicarFiltrosConta) {
                $aplicarFiltrosConta($query);
            });

        if ($request->filled('data_inicio')) {
            $queryParcelas->whereDate(
                'data_vencimento',
                '>=',
                $request->input('data_inicio')
            );
        }

        if ($request->filled('data_fim')) {
            $queryParcelas->whereDate(
                'data_vencimento',
                '<=',
                $request->input('data_fim')
            );
        }

        if ($request->input('status') === 'vencida') {
            $queryParcelas
                ->whereDate('data_vencimento', '<', $hoje)
                ->whereRaw(
                    'parcelas_contas_receber.valor > COALESCE((
                        SELECT SUM(recebimentos.valor)
                        FROM recebimentos
                        WHERE recebimentos.parcela_conta_receber_id = parcelas_contas_receber.id
                        AND recebimentos.estornado_em IS NULL
                    ), 0)'
                );
        }

        $queryParcelasVencidas = clone $queryParcelas;

        $queryParcelasVencidas
            ->whereDate(
                'parcelas_contas_receber.data_vencimento',
                '<',
                $hoje
            )
            ->whereHas('contaReceber', function (Builder $query) {
                $query->where('status', '!=', 'cancelada');
            })
            ->whereRaw(
                'parcelas_contas_receber.valor > COALESCE((
                    SELECT SUM(recebimentos.valor)
                    FROM recebimentos
                    WHERE recebimentos.parcela_conta_receber_id = parcelas_contas_receber.id
                    AND recebimentos.estornado_em IS NULL
                ), 0)'
            );

        $resumo = [
            'total' => (clone $queryContas)->count(),

            'valor_total' => (clone $queryContas)->sum(
                DB::raw(
                    'COALESCE(valor_original, 0)
                    - COALESCE(desconto, 0)
                    + COALESCE(juros, 0)
                    + COALESCE(multa, 0)'
                )
            ),

            'em_aberto' => (clone $queryContas)
                ->whereIn('status', ['aberta', 'parcial'])
                ->count(),

            'vencidas' => (clone $queryParcelasVencidas)->count(),

            'total_vencido' => (clone $queryParcelasVencidas)->sum(
                DB::raw(
                    'GREATEST(
                        parcelas_contas_receber.valor
                        - COALESCE(
                            (
                                SELECT SUM(recebimentos.valor)
                                FROM recebimentos
                                WHERE recebimentos.parcela_conta_receber_id = parcelas_contas_receber.id
                                AND recebimentos.estornado_em IS NULL
                            ),
                            0
                        ),
                        0
                    )'
                )
            ),
        ];

        $parcelasReceber = $queryParcelas
            ->orderBy('data_vencimento')
            ->orderBy('conta_receber_id')
            ->orderBy('numero')
            ->paginate(15)
            ->withQueryString();

        return view(
            'financeiro.contas_receber.index',
            compact(
                'parcelasReceber',
                'resumo'
            )
        );
    }

    public function create()
    {
        $clientes = Cliente::query()
            ->with('pessoa')
            ->whereHas('pessoa')
            ->get()
            ->sortBy('pessoa.nome');

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->orderByDesc('id')
            ->get();

        $categorias = CategoriaFinanceira::query()
            ->where('tipo', 'entrada')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'financeiro.contas_receber.create',
            compact(
                'clientes',
                'notas',
                'categorias'
            )
        );
    }

    public function store(
        StoreContaReceberRequest $request,
        CriarContaReceber $criarContaReceber
    ) {
        $criarContaReceber->execute(
            $request->validated()
        );

        return redirect()
            ->route('contas-receber.index')
            ->with(
                'success',
                'Conta a receber criada com sucesso.'
            );
    }

    public function show(ContaReceber $contaReceber)
    {
        $contaReceber->load([
            'cliente.pessoa',
            'nota.cliente.pessoa',
            'categoriaFinanceira',
            'parcelas.recebimentosAtivos',
            'recebimentos.parcela',
            'recebimentos.formaPagamento',
            'recebimentos.usuario',
        ]);

        $valorDevido = (float) $contaReceber->valor_original
            - (float) $contaReceber->desconto
            + (float) $contaReceber->juros
            + (float) $contaReceber->multa;

        $valorRecebido = (float) $contaReceber->recebimentos->whereNull('estornado_em')->sum('valor');
        $saldo = max(0, round($valorDevido - $valorRecebido, 2));

        $vencida = $contaReceber->status !== 'cancelada'
            && $contaReceber->parcelas->contains(fn ($parcela) => !$parcela->estaQuitada() && $parcela->data_vencimento->isBefore(today()));

        return view('financeiro.contas_receber.show', compact('contaReceber', 'valorDevido', 'valorRecebido', 'saldo', 'vencida'));
    }

    public function edit(ContaReceber $contaReceber)
    {
        if ($contaReceber->recebimentos()->exists()) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Contas que possuem recebimentos não podem ser editadas.'
                );
        }

        $clientes = Cliente::query()
            ->with('pessoa')
            ->whereHas('pessoa')
            ->get()
            ->sortBy('pessoa.nome');

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->orderByDesc('id')
            ->get();

        $categorias = CategoriaFinanceira::query()
            ->where('tipo', 'entrada')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'financeiro.contas_receber.edit',
            compact(
                'contaReceber',
                'clientes',
                'notas',
                'categorias'
            )
        );
    }

    public function update(
        UpdateContaReceberRequest $request,
        ContaReceber $contaReceber,
        AtualizarContaReceber $atualizarContaReceber
    ) {
        $atualizarContaReceber->execute(
            $contaReceber,
            $request->validated()
        );

        return redirect()
            ->route(
                'contas-receber.show',
                $contaReceber
            )
            ->with(
                'success',
                'Conta a receber atualizada com sucesso.'
            );
    }

    public function destroy(ContaReceber $contaReceber)
    {
        if ($contaReceber->recebimentos()->exists()) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Contas que possuem recebimentos não podem ser excluídas.'
                );
        }

        $contaReceber->delete();

        return redirect()
            ->route('contas-receber.index')
            ->with(
                'success',
                'Conta a receber excluída com sucesso.'
            );
    }
}
