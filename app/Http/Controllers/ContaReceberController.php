<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Actions\Financeiro\AtualizarContaReceber;
use App\Actions\Financeiro\CriarContaReceber;
use App\Http\Requests\StoreContaReceberRequest;
use App\Http\Requests\UpdateContaReceberRequest;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Nota;
use App\Models\ParcelaContaReceber;
use App\Models\Recebimento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContaReceberController extends Controller
{
    public function index(): View
    {
        $query = ParcelaContaReceber::query()
            ->with([
                'contaReceber' => fn ($query) => $query
                    ->with([
                        'cliente.pessoa',
                        'nota.cliente.pessoa',
                        'categoriaFinanceira',
                    ])
                    ->withCount('parcelas')
                    ->withExists('recebimentos'),
            ])
            ->withSum(
                'recebimentosAtivos as valor_recebido',
                'valor'
            );

        $valorRecebidoSubquery = function ($query) {
            $query
                ->from(
                    (new Recebimento())->getTable()
                )
                ->selectRaw(
                    'COALESCE(SUM(valor), 0)'
                )
                ->whereColumn(
                    'parcela_conta_receber_id',
                    'parcelas_contas_receber.id'
                )
                ->whereNull('estornado_em');
        };

        $aplicarPendente = function ($query) use (
            $valorRecebidoSubquery
        ) {
            $query->where(
                'valor',
                '>',
                $valorRecebidoSubquery
            );
        };

        $aplicarQuitada = function ($query) use (
            $valorRecebidoSubquery
        ) {
            $query->where(
                'valor',
                '<=',
                $valorRecebidoSubquery
            );
        };

        if (request()->filled('cliente')) {
            $cliente = trim(
                request('cliente')
            );

            $query->whereHas(
                'contaReceber',
                function ($query) use ($cliente) {
                    $query->where(
                        function ($query) use ($cliente) {
                            $query
                                ->whereHas(
                                    'cliente.pessoa',
                                    fn ($query) => $query->where(
                                        'nome',
                                        'like',
                                        '%' . $cliente . '%'
                                    )
                                )
                                ->orWhereHas(
                                    'nota.cliente.pessoa',
                                    fn ($query) => $query->where(
                                        'nome',
                                        'like',
                                        '%' . $cliente . '%'
                                    )
                                );
                        }
                    );
                }
            );
        }

        if (request()->filled('nota_id')) {
            $notaId = request('nota_id');

            $query->whereHas(
                'contaReceber',
                fn ($query) => $query->where(
                    'nota_id',
                    $notaId
                )
            );
        }

        $status = request('status');

        switch ($status) {
            case 'aberta':
                $query->whereHas(
                    'contaReceber',
                    fn ($query) => $query->where(
                        'status',
                        'aberta'
                    )
                );

                $aplicarPendente($query);
                break;

            case 'parcial':
                $query->whereHas(
                    'contaReceber',
                    fn ($query) => $query->where(
                        'status',
                        'parcial'
                    )
                );

                $aplicarPendente($query);
                break;

            case 'quitada':
                $query->whereHas(
                    'contaReceber',
                    fn ($query) => $query->where(
                        'status',
                        '!=',
                        'cancelada'
                    )
                );

                $aplicarQuitada($query);
                break;

            case 'vencida':
                $query->whereHas(
                    'contaReceber',
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
                    'contaReceber',
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
                    'contaReceber',
                    fn ($query) => $query->where(
                        'status',
                        '!=',
                        'cancelada'
                    )
                );

                $aplicarPendente($query);
                break;
        }

        if (request()->filled('data_inicio')) {
            $query->whereDate(
                'data_vencimento',
                '>=',
                request('data_inicio')
            );
        }

        if (request()->filled('data_fim')) {
            $query->whereDate(
                'data_vencimento',
                '<=',
                request('data_fim')
            );
        }

        $saldoSql = 'GREATEST(0, ROUND('
            . 'resumo_parcelas.valor - '
            . 'COALESCE(resumo_parcelas.valor_recebido, 0), 2))';

        $hoje = today()->toDateString();
        $naoCancelada = "COALESCE(conta_resumo.status, '') <> 'cancelada'";

        $totais = DB::query()
            ->fromSub((clone $query)->toBase(), 'resumo_parcelas')
            ->join(
                'contas_receber as conta_resumo',
                'conta_resumo.id',
                '=',
                'resumo_parcelas.conta_receber_id'
            )
            ->selectRaw(
                'COALESCE(SUM(resumo_parcelas.valor), 0) AS valor_total'
            )
            ->selectRaw(
                'COUNT(DISTINCT resumo_parcelas.conta_receber_id) AS total'
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN $naoCancelada AND $saldoSql > 0 "
                . 'THEN resumo_parcelas.conta_receber_id END) AS em_aberto'
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN $naoCancelada "
                . "AND $saldoSql > 0 "
                . "AND resumo_parcelas.data_vencimento < ? "
                . "THEN $saldoSql ELSE 0 END), 0) AS total_vencido",
                [$hoje]
            )
            ->selectRaw(
                "SUM(CASE WHEN $naoCancelada "
                . "AND $saldoSql > 0 "
                . "AND resumo_parcelas.data_vencimento < ? "
                . 'THEN 1 ELSE 0 END) AS vencidas',
                [$hoje]
            )
            ->first();

        $resumo = [
            'valor_total' => (float) $totais->valor_total,
            'total' => (int) $totais->total,
            'em_aberto' => (int) $totais->em_aberto,
            'total_vencido' => (float) $totais->total_vencido,
            'vencidas' => (int) $totais->vencidas,
        ];

        $parcelasReceber = $query
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

    public function create(Request $request)
    {
        $categorias = CategoriaFinanceira::query()
            ->where('tipo', 'entrada')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $notaSelecionada = null;

        if ($request->filled('nota_id')) {
            $notaSelecionada = Nota::query()
                ->with([
                    'cliente.pessoa',
                    'contaReceber',
                ])
                ->whereKey($request->input('nota_id'))
                ->where('status', '!=', 'Cancelado')
                ->first();

            if (!$notaSelecionada) {
                return redirect()
                    ->route('contas-receber.create')
                    ->withErrors([
                        'nota_id' => 'A Nota informada não existe ou está cancelada.',
                    ]);
            }

            if ($notaSelecionada->contaReceber) {
                return redirect()
                    ->route(
                        'contas-receber.show',
                        $notaSelecionada->contaReceber
                    )
                    ->with(
                        'error',
                        "A Nota #{$notaSelecionada->id} já possui uma Conta a Receber."
                    );
            }
        }

        return view(
            'financeiro.contas_receber.create',
            compact(
                'categorias',
                'notaSelecionada'
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
            'anexosVinculos.anexo',
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
