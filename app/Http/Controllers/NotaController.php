<?php

namespace App\Http\Controllers;

use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\FinalizarNota;
use App\Http\Requests\FinalizarNotaRequest;
use App\Http\Requests\Notas\BaixarPdfInternoNotaRequest;
use App\Models\CategoriaFinanceira;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\Produto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use InvalidArgumentException;

class NotaController extends Controller
{
    public function finalizar(
        FinalizarNotaRequest $request,
        Nota $nota,
        FinalizarNota $finalizarNota
    ) {
        try {
            $finalizarNota->execute(
                $nota,
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'finalizacao' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'notas.show',
                $nota->id
            )
            ->with(
                'success',
                "Nota #{$nota->id} finalizada com sucesso!"
            );
    }

    public function cancelar(
        Nota $nota,
        CancelarNota $cancelarNota
    ) {
        try {
            $cancelarNota->execute($nota);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withErrors([
                    'cancelamento' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'notas.show',
                $nota->id
            )
            ->with(
                'success',
                "Nota #{$nota->id} cancelada com sucesso!"
            );
    }

    public function index(Request $request)
    {
        $filters = $request->all();

        /*
        * Sem status informado, a listagem operacional
        * trabalha somente com Notas abertas.
        */
        $filters['status'] =
            $request->input(
                'status',
                'Aberto'
            );

        $notas = Nota::query()
            ->with([
                'cliente.pessoa',
                'itens.itemable',
                'veiculosCliente.veiculo.montadora',
                'etapa',
            ])
            ->filtro($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $etapas = Etapa::query()
            ->paraNota()
            ->ativas()
            ->get();

        return view(
            'notas_item.listar_notas_itens',
            compact(
                'notas',
                'etapas'
            )
        );
    }

    public function destroy(Nota $nota)
    {
        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' => 'Notas finalizadas ou canceladas não podem ser excluídas.',
                ]);
        }

        $nota->delete();

        return redirect()
            ->route('notas.index')
            ->with(
                'success',
                'Nota removida!'
            );
    }

    public function show(Nota $nota)
    {
        $nota->load([
            'cliente.pessoa',
            'veiculosCliente.veiculo.montadora',
            'itens.itemable',
            'contaReceber.parcelas',
            'contaReceber.recebimentosAtivos',
        ]);

        $usuario = auth()->user();

        $podeGerenciar =
            $usuario
            && $usuario->permitions != 2;

        $podeFinalizarNota =
            $nota->status === 'Aberto'
            && $podeGerenciar;

        $podeCancelarNota =
            in_array(
                $nota->status,
                [
                    'Aberto',
                    'Finalizado',
                    'Concluido',
                ],
                true
            )
            && $podeGerenciar;

        $subtotal =
            (float) ($nota->subtotal ?? 0);

        $descontoTotal =
            (float) ($nota->desconto ?? 0);

        $totalNota =
            (float) ($nota->total ?? 0);

        /*
         * =====================================================
         * CLIENTE / VEÍCULO
         * =====================================================
         */
        $cliente =
            $nota->cliente?->pessoa;

        $veiculoCliente =
            $nota->veiculosCliente;

        $veiculo =
            $veiculoCliente?->veiculo;

        $montadora =
            $veiculo?->montadora;

        $clienteNome =
            $cliente?->nome
            ?? 'Cliente Geral / Balcão';

        $placa =
            $veiculoCliente?->placa
            ?? 'Não informada';

        $veiculoDescricao =
            $veiculo?->nome
            ?? $veiculo?->modelo
            ?? 'Não informado';

        $montadoraNome =
            $montadora?->nome
            ?? null;

        /*
         * =====================================================
         * STATUS
         * =====================================================
         */
        $statusBadgeClass =
            match ($nota->status) {
                'Aberto' => 'bg-warning text-dark',

                'Finalizado',
                'Concluido' => 'bg-success',

                'Cancelado' => 'bg-danger',

                default => 'bg-secondary',
            };

        $statusLabel =
            $nota->status === 'Concluido'
                ? 'Finalizado'
                : $nota->status;

        $mensagemCancelamento =
            $nota->status === 'Aberto'
                ? 'Deseja cancelar esta nota? Ela será cancelada e não poderá mais ser editada.'
                : 'Deseja cancelar esta nota? Os produtos baixados serão devolvidos ao estoque e esta operação não poderá ser desfeita.';

        /*
         * =====================================================
         * FINANCEIRO
         * =====================================================
         */
        $contaReceber =
            $nota->contaReceber;

        $valorConta = 0;
        $valorRecebido = 0;
        $saldoConta = 0;
        $totalParcelas = 0;
        $financeiroSincronizado = true;

        if ($contaReceber) {
            $valorConta =
                (float) $contaReceber->valor_original;

            $valorRecebido =
                (float) $contaReceber
                    ->recebimentosAtivos
                    ->sum('valor');

            $valorDevidoConta =
                (float) $contaReceber->valor_original
                - (float) $contaReceber->desconto
                + (float) $contaReceber->juros
                + (float) $contaReceber->multa;

            $saldoConta =
                max(
                    0,
                    round(
                        $valorDevidoConta
                        - $valorRecebido,
                        2
                    )
                );

            $totalParcelas =
                (float) $contaReceber
                    ->parcelas
                    ->sum('valor');

            $financeiroSincronizado =
                (int) round(
                    $valorConta * 100
                )
                ===
                (int) round(
                    $totalNota * 100
                )
                &&
                (int) round(
                    $totalParcelas * 100
                )
                ===
                (int) round(
                    $valorConta * 100
                );
        }

        /*
         * =====================================================
         * ESTOQUE
         * =====================================================
         */
        $problemasEstoque = [];

        $quantidadeSemEstoque = 0;
        $quantidadeInsuficiente = 0;
        $quantidadeEstoqueBaixo = 0;

        if ($nota->status === 'Aberto') {
            foreach ($nota->itens as $item) {
                if (
                    $item->itemable_type
                        !== Produto::class
                    || ! $item->itemable
                ) {
                    continue;
                }

                $produto =
                    $item->itemable;

                $estoqueAtual =
                    (float) (
                        $produto->quantidade
                        ?? 0
                    );

                $estoqueMinimo =
                    (float) (
                        $produto->estoque_minimo
                        ?? 0
                    );

                $quantidadeSolicitada =
                    (float) (
                        $item->quantidade
                        ?? 0
                    );

                $codigoProduto =
                    $produto->codigo_fabricante
                    ?: $produto->codigo_barras
                    ?: null;

                $tipoProblema = null;

                if ($estoqueAtual <= 0) {
                    $quantidadeSemEstoque++;
                    $tipoProblema =
                        'sem_estoque';
                } elseif (
                    $quantidadeSolicitada
                    > $estoqueAtual
                ) {
                    $quantidadeInsuficiente++;
                    $tipoProblema =
                        'insuficiente';
                } elseif (
                    $estoqueAtual
                    <= $estoqueMinimo
                ) {
                    $quantidadeEstoqueBaixo++;
                    $tipoProblema =
                        'baixo';
                }

                if (! $tipoProblema) {
                    continue;
                }

                $problemasEstoque[] = [
                    'tipo' => $tipoProblema,

                    'produto_id' => $produto->id,

                    'descricao' => $item->descricao,

                    'codigo' => $codigoProduto,

                    'solicitado' => $quantidadeSolicitada,

                    'atual' => $estoqueAtual,

                    'minimo' => $estoqueMinimo,
                ];
            }
        }

        $possuiProblemaBloqueanteEstoque =
            $quantidadeSemEstoque > 0
            || $quantidadeInsuficiente > 0;

        /*
         * =====================================================
         * ITENS PARA EXIBIÇÃO
         * =====================================================
         */
        $itensExibicao =
            $nota->itens->map(
                function ($item) {
                    $quantidade =
                        (float) (
                            $item->quantidade
                            ?? 0
                        );

                    $valorUnitario =
                        (float) (
                            $item->valor_unitario
                            ?? 0
                        );

                    $desconto =
                        (float) (
                            $item->desconto
                            ?? 0
                        );

                    $total =
                        max(
                            0,
                            (
                                $quantidade
                                * $valorUnitario
                            ) - $desconto
                        );

                    $isProduto =
                        $item->itemable_type
                        === Produto::class;

                    $isOrdemServico =
                        $item->itemable_type
                        === OrdemServico::class;

                    if ($isProduto) {
                        $tipo =
                            'Produto';

                        $tipoClasse =
                            'bg-primary';

                        $tipoIcone =
                            'bi-box-seam';

                        $codigo =
                            $item
                                ->itemable
                                ?->codigo_fabricante
                            ?: $item
                                ->itemable
                                ?->codigo_barras
                            ?: '—';
                    } elseif ($isOrdemServico) {
                        $tipo =
                            'O.S.';

                        $tipoClasse =
                            'bg-warning text-dark';

                        $tipoIcone =
                            'bi-tools';

                        $codigo =
                            $item->itemable?->id
                                ? '#'
                                    .$item
                                        ->itemable
                                        ->id
                                : '—';
                    } else {
                        $tipo =
                            'Item';

                        $tipoClasse =
                            'bg-secondary';

                        $tipoIcone =
                            'bi-question-circle';

                        $codigo =
                            '—';
                    }

                    return [
                        'id' => $item->id,

                        'tipo' => $tipo,

                        'tipo_classe' => $tipoClasse,

                        'tipo_icone' => $tipoIcone,

                        'codigo' => $codigo,

                        'descricao' => $item->descricao
                            ?? $item->itemable?->nome
                            ?? $item->itemable?->descricao
                            ?? 'Item sem descrição',

                        'quantidade' => $quantidade,

                        'valor_unitario' => $valorUnitario,

                        'desconto' => $desconto,

                        'total' => $total,

                        'garantia_dias' => (int) (
                            $item->garantia_dias
                            ?? 0
                        ),
                    ];
                }
            );

        /*
         * Só precisamos das categorias se a Nota puder ser
         * finalizada e ainda não possuir Conta a Receber.
         */
        $categoriasFinanceiras =
            $podeFinalizarNota
            && ! $contaReceber
                ? CategoriaFinanceira::query()
                    ->where(
                        'tipo',
                        'entrada'
                    )
                    ->where(
                        'ativo',
                        true
                    )
                    ->orderBy('nome')
                    ->get()
                : collect();

        return view(
            'notas_item.show_notas_itens',
            compact(
                'nota',
                'subtotal',
                'descontoTotal',
                'totalNota',
                'podeFinalizarNota',
                'podeCancelarNota',
                'mensagemCancelamento',
                'clienteNome',
                'placa',
                'veiculoDescricao',
                'montadoraNome',
                'statusBadgeClass',
                'statusLabel',
                'contaReceber',
                'valorConta',
                'valorRecebido',
                'saldoConta',
                'financeiroSincronizado',
                'problemasEstoque',
                'quantidadeEstoqueBaixo',
                'possuiProblemaBloqueanteEstoque',
                'itensExibicao',
                'categoriasFinanceiras'
            )
        );
    }

    private function carregarNotaParaPdf(
        Nota $nota,
        bool $interno = false
    ): Nota {
        $relacoes = [
            'cliente.pessoa',
            'veiculosCliente.veiculo.montadora',
        ];

        if ($interno) {
            $relacoes['itens.itemable'] = function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Produto::class => [
                        'marcaRelacionada',
                        'imagens',
                    ],
                ]);
            };
        } else {
            $relacoes[] = 'itens.itemable';
        }

        return $nota->load($relacoes);
    }

    public function gerarpdf(string $id)
    {
        $nota = Nota::findOrFail($id);

        $this->carregarNotaParaPdf(
            $nota
        );

        $pdf = Pdf::loadView(
            'pdf.nota',
            compact('nota')
        )->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->stream(
            "nota-cliente-{$nota->id}.pdf"
        );
    }

    public function baixarPdf(string $id)
    {
        $nota = Nota::findOrFail($id);

        $this->carregarNotaParaPdf(
            $nota
        );

        $pdf = Pdf::loadView(
            'pdf.nota',
            compact('nota')
        )->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download(
            "nota-cliente-{$nota->id}.pdf"
        );
    }

    public function baixarPdfInterno(
        BaixarPdfInternoNotaRequest $request,
        Nota $nota
    ) {
        $this->carregarNotaParaPdf(
            $nota,
            true
        );

        $pdf = Pdf::loadView(
            'pdf.nota_interna',
            compact('nota')
        )->setPaper(
            'a4',
            'landscape'
        );

        $senha =
            $request->validated()['senha'];

        $pdf->setEncryption(
            $senha
        );

        return $pdf->download(
            "nota-interna-{$nota->id}.pdf"
        );
    }
}
