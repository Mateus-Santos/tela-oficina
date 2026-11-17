<?php

namespace App\Http\Controllers;

use App\Actions\Compra\AprovarCompra;
use App\Actions\Compra\AtualizarCompra;
use App\Actions\Compra\CancelarCompra;
use App\Actions\Compra\ConferirCompra;
use App\Actions\Compra\CriarCompra;
use App\Actions\Compra\EstornarCompra;
use App\Actions\Compra\GerarContaPagarCompra;
use App\Actions\Compra\IniciarConferenciaCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Http\Requests\Compra\AprovarCompraRequest;
use App\Http\Requests\Compra\ConferirCompraRequest;
use App\Http\Requests\Compra\GerarContaPagarCompraRequest;
use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\Fornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::query()
            ->with('fornecedor')
            ->when($request->filled('numero_nf'), function ($query) use ($request) {
                $query->where('numero_nf', 'like', '%' . $request->numero_nf . '%');
            })
            ->when($request->filled('fornecedor_id'), function ($query) use ($request) {
                $query->where('fornecedor_id', $request->fornecedor_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when(!$request->filled('status'), function ($query) {
                $query->where('status', '!=', Compra::STATUS_CANCELADA);
            })
            ->when($request->filled('data_inicio'), function ($query) use ($request) {
                $query->whereDate('data_entrada', '>=', $request->data_inicio);
            })
            ->when($request->filled('data_fim'), function ($query) use ($request) {
                $query->whereDate('data_entrada', '<=', $request->data_fim);
            });

        $resumo = [
            'total' => (clone $query)->count(),
            'valor_total' => (clone $query)->sum('valor_total'),
            'aprovadas' => (clone $query)->where('status', Compra::STATUS_APROVADA)->count(),
            'pendentes' => (clone $query)
                ->whereIn('status', [
                    Compra::STATUS_PENDENTE,
                    Compra::STATUS_CONFERINDO,
                ])
                ->count(),
        ];

        /*
         * U02 - indicadores para a listagem, sem N+1.
         * A pivô compra_conta_pagar é a referência atual; o vínculo legado
         * contaPagar é mantido para detectar dados anteriores à migração.
         * Entradas e estornos são contados por ITEM, não por movimento,
         * evitando contagem duplicada caso haja histórico adicional.
         */
        $compras = $query
            ->withCount([
                'itens as u02_total_itens',
                'itens as u02_itens_com_entrada' => fn ($q) => $q
                    ->whereHas('movimentacoesEstoque', fn ($mov) => $mov
                        ->where('tipo', 'entrada')),
                'itens as u02_itens_com_estorno' => fn ($q) => $q
                    ->whereHas('movimentacoesEstoque', fn ($mov) => $mov
                        ->where('tipo', 'saida')
                        ->where('observacoes', 'like', 'Estorno da entrada da Compra%')),
            ])
            ->withExists([
                'contasPagar as u02_pivo_conta_ativa' => fn ($q) => $q
                    ->where('contas_pagar.status', '!=', 'cancelada'),
                'contaPagar as u02_legado_conta_ativa' => fn ($q) => $q
                    ->where('status', '!=', 'cancelada'),
                'contasPagar as u02_pivo_conta_cancelada' => fn ($q) => $q
                    ->where('contas_pagar.status', 'cancelada'),
                'contaPagar as u02_legado_conta_cancelada' => fn ($q) => $q
                    ->where('status', 'cancelada'),
            ])
            ->latest('data_entrada')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        /*
         * Contrato para o agente Front-end: somente atributos transitórios.
         * Não altera o banco, não consulta relacionamentos em cada linha,
         * não confunde uma conta vinculada com uma conta quitada.
         */
        $compras->getCollection()->each(function (Compra $compra): void {
            $total = (int) $compra->u02_total_itens;
            $entradas = (int) $compra->u02_itens_com_entrada;
            $estornos = (int) $compra->u02_itens_com_estorno;

            $estoque = match (true) {
                $total === 0 => 'sem_itens',
                $compra->estaCancelada() && $entradas > 0 && $estornos >= $entradas => 'estornado',
                $compra->estaCancelada() && $entradas > 0 => 'verificar_estorno',
                $compra->estaCancelada() => 'sem_entrada',
                $entradas === 0 => 'pendente',
                $entradas < $total => 'parcial',
                default => 'lancado',
            };

            $financeiro = match (true) {
                (bool) $compra->u02_pivo_conta_ativa
                    || (bool) $compra->u02_legado_conta_ativa => 'vinculada',
                (bool) $compra->u02_pivo_conta_cancelada
                    || (bool) $compra->u02_legado_conta_cancelada => 'vinculo_cancelado',
                default => 'nao_vinculada',
            };

            $compra->setAttribute('integracao_estoque_status', $estoque);
            $compra->setAttribute('integracao_financeiro_status', $financeiro);
        });

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view('compra.index', compact('compras', 'fornecedores', 'resumo'));
    }

    public function create()
    {
        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view('compra.create', compact('fornecedores'));
    }

    public function store(
        StoreCompraRequest $request,
        CriarCompra $criarCompra
    ) {
        $dados = $request->validated();
        $anexos = $dados['anexos'] ?? [];
        unset($dados['anexos']);

        $compra = $criarCompra->execute($dados, $anexos);

        return redirect()
            ->route('compras.show', $compra)
            ->with('success', 'Compra cadastrada com sucesso!');
    }

    public function show(Compra $compra)
    {
        $compra->load([
            'fornecedor',
            'itens.produto',
            'itens.movimentacoesEstoque',
            'anexosVinculos.anexo',
            'contasPagar',
        ]);

        return view('compra.show', compact('compra'));
    }

    public function iniciarConferencia(
        Compra $compra,
        IniciarConferenciaCompra $iniciarConferenciaCompra
    ): RedirectResponse {
        try {
            $iniciarConferenciaCompra->execute($compra);

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Conferência da compra iniciada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function conferir(
        ConferirCompraRequest $request,
        Compra $compra,
        ConferirCompra $conferirCompra
    ): RedirectResponse {
        try {
            $conferirCompra->execute($compra, $request->validated());

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Conferência da compra salva com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function aprovar(
        AprovarCompraRequest $request,
        Compra $compra,
        AprovarCompra $aprovarCompra,
        GerarContaPagarCompra $gerarContaPagarCompra
    ): RedirectResponse {
        try {
            DB::transaction(function () use (
                $request,
                $compra,
                $aprovarCompra,
                $gerarContaPagarCompra
            ) {
                $dados = $request->validated();

                $aprovarCompra->execute($compra);

                if ((bool) $dados['gerar_conta']) {
                    $gerarContaPagarCompra->execute($compra, $dados['parcelas']);
                }
            });

            $mensagem = $request->boolean('gerar_conta')
                ? 'Compra aprovada e conta a pagar gerada com sucesso!'
                : 'Compra aprovada com sucesso!';

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', $mensagem);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function gerarConta(
        GerarContaPagarCompraRequest $request,
        Compra $compra,
        GerarContaPagarCompra $gerarContaPagarCompra
    ): RedirectResponse {
        try {
            $gerarContaPagarCompra->execute(
                $compra,
                $request->validated()['parcelas']
            );

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Conta a pagar gerada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function cancelar(
        Compra $compra,
        CancelarCompra $cancelarCompra
    ): RedirectResponse {
        try {
            $cancelarCompra->execute($compra);

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Compra cancelada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function estornar(
        Compra $compra,
        EstornarCompra $estornarCompra
    ): RedirectResponse {
        try {
            $estornarCompra->execute($compra);

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Compra estornada e cancelada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    public function registrarEstoque(
        Compra $compra,
        RegistrarEntradaCompra $registrarEntradaCompra
    ): RedirectResponse {
        try {
            $registrarEntradaCompra->execute($compra);

            return redirect()
                ->route('compras.show', $compra)
                ->with('success', 'Entrada da compra registrada no estoque com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    private function estoqueLancado(Compra $compra): bool
    {
        return $compra->itens()
            ->whereHas('movimentacoesEstoque', function ($query) {
                $query->where('tipo', 'entrada');
            })
            ->exists();
    }

    public function edit(Compra $compra)
    {
        if (!$compra->estaPendente()) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Somente compras pendentes podem ser editadas.');
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Uma compra que já possui entrada no estoque não pode ser editada.');
        }

        $compra->load([
            'fornecedor',
            'itens.produto',
        ]);

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view('compra.edit', compact('compra', 'fornecedores'));
    }

    public function update(
        UpdateCompraRequest $request,
        Compra $compra,
        AtualizarCompra $atualizarCompra
    ) {
        if (!$compra->estaPendente()) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Somente compras pendentes podem ser alteradas.');
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Uma compra que já possui entrada no estoque não pode ser alterada.');
        }

        $compraAtualizada = $atualizarCompra->execute(
            $compra,
            $request->validated()
        );

        return redirect()
            ->route('compras.show', $compraAtualizada)
            ->with('success', 'Compra atualizada com sucesso!');
    }

    public function destroy(Compra $compra)
    {
        if (!$compra->estaPendente()) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Somente compras pendentes podem ser excluídas.');
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', 'Uma compra que já possui entrada no estoque não pode ser excluída.');
        }

        $compra->delete();

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra excluída com sucesso!');
    }
}
