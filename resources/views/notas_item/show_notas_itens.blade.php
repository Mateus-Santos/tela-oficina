@extends('layouts.layout')

@section('content')

@php

    $subtotal =
        (float) ($nota->subtotal ?? 0);

    $descontoTotal =
        (float) ($nota->desconto ?? 0);

    $totalNota =
        (float) ($nota->total ?? 0);

    $podeFinalizarNota =
        $nota->status === 'Aberto'
        && auth()->user()
        && auth()->user()->permitions != 2;

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
        && auth()->user()
        && auth()->user()->permitions != 2;

    $mensagemCancelamento =
        $nota->status === 'Aberto'
            ? 'Deseja cancelar esta nota? Ela será cancelada e não poderá mais ser editada.'
            : 'Deseja cancelar esta nota? Os produtos baixados serão devolvidos ao estoque e esta operação não poderá ser desfeita.';

    $errosFinalizacao =
        $errors->has('finalizacao')
        || $errors->has('categoria_financeira_id')
        || $errors->has('parcelas')
        || $errors->has('parcelas.*');

    /*
     * =====================================================
     * FINANCEIRO
     * =====================================================
     */

    $contaReceber =
        $nota->contaReceber;

    $valorConta =
        $contaReceber
            ? (float) $contaReceber->valor_original
            : 0;

    $valorRecebido =
        $contaReceber
            ? (float) $contaReceber
                ->recebimentosAtivos()
                ->sum('valor')
            : 0;

    $valorDevidoConta =
        $contaReceber
            ? (
                (float) $contaReceber->valor_original
                - (float) $contaReceber->desconto
                + (float) $contaReceber->juros
                + (float) $contaReceber->multa
            )
            : 0;

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
        $contaReceber
            ? (float) $contaReceber
                ->parcelas()
                ->sum('valor')
            : 0;

    $financeiroSincronizado =
        !$contaReceber
        || (
            (int) round($valorConta * 100)
                === (int) round($totalNota * 100)
            && (int) round($totalParcelas * 100)
                === (int) round($valorConta * 100)
        );

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
                    !== \App\Models\Produto::class
                || !$item->itemable
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

            if ($estoqueAtual <= 0) {

                $quantidadeSemEstoque++;

                $problemasEstoque[] = [
                    'tipo' => 'sem_estoque',
                    'descricao' => $item->descricao,
                    'codigo' => $codigoProduto,
                    'solicitado' => $quantidadeSolicitada,
                    'atual' => $estoqueAtual,
                    'minimo' => $estoqueMinimo,
                ];

                continue;
            }

            if (
                $quantidadeSolicitada
                > $estoqueAtual
            ) {

                $quantidadeInsuficiente++;

                $problemasEstoque[] = [
                    'tipo' => 'insuficiente',
                    'descricao' => $item->descricao,
                    'codigo' => $codigoProduto,
                    'solicitado' => $quantidadeSolicitada,
                    'atual' => $estoqueAtual,
                    'minimo' => $estoqueMinimo,
                ];

                continue;
            }

            if (
                $estoqueAtual
                <= $estoqueMinimo
            ) {

                $quantidadeEstoqueBaixo++;

                $problemasEstoque[] = [
                    'tipo' => 'baixo',
                    'descricao' => $item->descricao,
                    'codigo' => $codigoProduto,
                    'solicitado' => $quantidadeSolicitada,
                    'atual' => $estoqueAtual,
                    'minimo' => $estoqueMinimo,
                ];
            }
        }
    }

    $possuiProblemaBloqueanteEstoque =
        $quantidadeSemEstoque > 0
        || $quantidadeInsuficiente > 0;

@endphp


<div class="container cadastro">

    {{-- =========================================================
         CABEÇALHO
    ========================================================== --}}

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>

            <h1 class="mb-1">

                <i class="bi bi-receipt"></i>
                NOTA #{{ $nota->id }}

            </h1>

            <div class="text-muted">
                Detalhes da nota / ordem de serviço
            </div>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            @if($podeFinalizarNota)

                <a
                    href="{{ route('notasitem.edit', $nota->id) }}"
                    class="btn btn-primary"
                >

                    <i class="bi bi-pencil-square"></i>
                    Editar nota

                </a>

            @endif

            @if($nota->contaReceber)

                <a
                    href="{{ route(
                        'contas-receber.show',
                        $nota->contaReceber
                    ) }}"
                    class="btn btn-success"
                >

                    <i class="bi bi-cash-coin"></i>
                    Conta a Receber
                    #{{ $nota->contaReceber->id }}

                </a>

            @elseif(
                $nota->status !== 'Cancelado'
                && auth()->user()
                && auth()->user()->permitions != 2
            )

                <a
                    href="{{ route(
                        'contas-receber.create',
                        [
                            'nota_id' => $nota->id,
                        ]
                    ) }}"
                    class="btn btn-outline-success"
                >

                    <i class="bi bi-cash-stack"></i>
                    Gerar Conta a Receber

                </a>

            @endif

            <a
                href="{{ route('notas.pdf', $nota->id) }}"
                target="_blank"
                class="btn btn-danger"
            >

                <i class="bi bi-file-earmark-pdf"></i>
                PDF

            </a>

        </div>

    </div>


    {{-- =========================================================
         MENSAGENS
    ========================================================== --}}

    @if(session('success'))

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>
            {{ session('success') }}

        </div>

    @endif

    @if($errors->has('finalizacao'))

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('finalizacao') }}

        </div>

    @endif

    @if($errors->has('cancelamento'))

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('cancelamento') }}

        </div>

    @endif

    @if($errors->has('nota'))

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('nota') }}

        </div>

    @endif


    {{-- =========================================================
         RESUMO DA NOTA
    ========================================================== --}}

    <div class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">

                <i class="bi bi-calculator"></i>
                Resumo da nota

            </h5>

            @if($nota->status === 'Aberto')

                <span class="badge bg-warning text-dark">
                    Aberto
                </span>

            @elseif(
                in_array(
                    $nota->status,
                    ['Finalizado', 'Concluido'],
                    true
                )
            )

                <span class="badge bg-success">
                    Finalizado
                </span>

            @elseif($nota->status === 'Cancelado')

                <span class="badge bg-danger">
                    Cancelado
                </span>

            @else

                <span class="badge bg-secondary">
                    {{ $nota->status }}
                </span>

            @endif

        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-12 col-md-4">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Subtotal
                        </small>

                        <strong class="fs-5">

                            R$
                            {{
                                number_format(
                                    $subtotal,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-4">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Descontos
                        </small>

                        <strong class="fs-5">

                            R$
                            {{
                                number_format(
                                    $descontoTotal,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-4">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Total da nota
                        </small>

                        <strong class="fs-4">

                            R$
                            {{
                                number_format(
                                    $totalNota,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}

                        </strong>

                    </div>

                </div>

            </div>

            @if($contaReceber)

                <hr>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                    <h6 class="mb-0">

                        <i class="bi bi-cash-coin"></i>
                        Situação financeira

                    </h6>

                    @if($financeiroSincronizado)

                        <span class="badge bg-success">

                            <i class="bi bi-check-circle"></i>
                            Sincronizado

                        </span>

                    @else

                        <span class="badge bg-danger">

                            <i class="bi bi-exclamation-triangle"></i>
                            Requer atenção

                        </span>

                    @endif

                </div>

                @if(!$financeiroSincronizado)

                    <div class="alert alert-danger">

                        <div class="fw-semibold mb-1">

                            <i class="bi bi-exclamation-triangle"></i>
                            Financeiro inconsistente

                        </div>

                        O valor da Conta a Receber ou de suas parcelas
                        não corresponde ao total atual da Nota.

                        Regularize o financeiro antes de finalizar.

                    </div>

                @endif

                <div class="row g-3">

                    <div class="col-12 col-md-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block">
                                Conta a Receber
                            </small>

                            <strong>
                                #{{ $contaReceber->id }}
                            </strong>

                        </div>

                    </div>

                    <div class="col-12 col-md-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block">
                                Valor da conta
                            </small>

                            <strong>

                                R$
                                {{
                                    number_format(
                                        $valorConta,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}

                            </strong>

                        </div>

                    </div>

                    <div class="col-12 col-md-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block">
                                Recebido
                            </small>

                            <strong class="text-success">

                                R$
                                {{
                                    number_format(
                                        $valorRecebido,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}

                            </strong>

                        </div>

                    </div>

                    <div class="col-12 col-md-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block">
                                Saldo
                            </small>

                            <strong>

                                R$
                                {{
                                    number_format(
                                        $saldoConta,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}

                            </strong>

                        </div>

                    </div>

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
         ALERTA DE ESTOQUE
    ========================================================== --}}

    @if($nota->status === 'Aberto')

        <div
            class="alert alert-{{
                $possuiProblemaBloqueanteEstoque
                    ? 'danger'
                    : (
                        $quantidadeEstoqueBaixo > 0
                            ? 'warning'
                            : 'success'
                    )
            }} mb-4"
        >

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                <div>

                    <div class="fw-semibold mb-1">

                        @if($possuiProblemaBloqueanteEstoque)

                            <i class="bi bi-exclamation-octagon"></i>
                            Atenção ao estoque

                        @elseif($quantidadeEstoqueBaixo > 0)

                            <i class="bi bi-exclamation-triangle"></i>
                            Estoque baixo

                        @else

                            <i class="bi bi-check-circle"></i>
                            Estoque disponível

                        @endif

                    </div>

                    @if($possuiProblemaBloqueanteEstoque)

                        Existem produtos que impedem a finalização
                        da Nota no estado atual.

                    @elseif($quantidadeEstoqueBaixo > 0)

                        Todos os produtos possuem quantidade suficiente,
                        porém existem itens no estoque mínimo.

                    @else

                        Os produtos desta Nota possuem estoque suficiente
                        para a quantidade solicitada.

                    @endif

                </div>

                @if(!empty($problemasEstoque))

                    <span class="badge bg-dark">

                        {{
                            count($problemasEstoque)
                        }}
                        alerta(s)

                    </span>

                @endif

            </div>

            @if(!empty($problemasEstoque))

                <div class="table-responsive mt-3">

                    <table class="table table-sm table-bordered align-middle mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Produto
                                </th>

                                <th>
                                    Solicitado
                                </th>

                                <th>
                                    Estoque
                                </th>

                                <th>
                                    Mínimo
                                </th>

                                <th>
                                    Situação
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($problemasEstoque as $problema)

                                <tr>

                                    <td>

                                        <strong>
                                            {{ $problema['descricao'] }}
                                        </strong>

                                        @if($problema['codigo'])

                                            <div class="small text-muted">

                                                <i class="bi bi-upc-scan"></i>
                                                {{ $problema['codigo'] }}

                                            </div>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $problema['solicitado'] }}
                                    </td>

                                    <td>
                                        {{ $problema['atual'] }}
                                    </td>

                                    <td>
                                        {{ $problema['minimo'] }}
                                    </td>

                                    <td>

                                        @if(
                                            $problema['tipo']
                                            === 'sem_estoque'
                                        )

                                            <span class="badge bg-danger">
                                                Sem estoque
                                            </span>

                                        @elseif(
                                            $problema['tipo']
                                            === 'insuficiente'
                                        )

                                            <span class="badge bg-danger">
                                                Insuficiente
                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">
                                                Estoque baixo
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    @endif


    {{-- =========================================================
         STATUS E AÇÕES
    ========================================================== --}}

    <div class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">

                <i class="bi bi-info-circle"></i>
                Status da nota

            </h5>

        </div>

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    @if($nota->status === 'Aberto')

                        <strong>
                            Nota aberta
                        </strong>

                        <div class="text-muted">
                            Esta nota pode ser alterada antes da finalização.
                        </div>

                    @elseif(
                        in_array(
                            $nota->status,
                            ['Finalizado', 'Concluido'],
                            true
                        )
                    )

                        <strong>
                            Nota finalizada
                        </strong>

                        <div class="text-muted">
                            Esta nota não pode mais ser editada.
                        </div>

                    @elseif($nota->status === 'Cancelado')

                        <strong>
                            Nota cancelada
                        </strong>

                        <div class="text-muted">
                            Esta nota não pode mais ser alterada.
                        </div>

                    @endif

                </div>

                <div class="d-flex gap-2 flex-wrap">

                    @if($podeFinalizarNota)

                        <button
                            type="button"
                            class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#modalFinalizarNota"
                        >

                            <i class="bi bi-check-circle"></i>
                            Finalizar nota

                        </button>

                    @endif

                    @if($podeCancelarNota)

                        <form
                            action="{{ route(
                                'notas.cancelar',
                                $nota->id
                            ) }}"
                            method="POST"
                            onsubmit="return confirm(@js($mensagemCancelamento));"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >

                                <i class="bi bi-x-circle"></i>
                                Cancelar nota

                            </button>

                        </form>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         INFORMAÇÕES DA NOTA
    ========================================================== --}}

    <div class="card mb-4">

        <div class="card-header">

            <h5 class="mb-0">

                <i class="bi bi-file-earmark-text"></i>
                Informações da nota

            </h5>

        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Número
                        </small>

                        <strong>
                            #{{ $nota->id }}
                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Tipo
                        </small>

                        <strong>
                            {{ $nota->tipo ?? 'N/A' }}
                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Criada em
                        </small>

                        <strong>

                            {{
                                $nota->created_at
                                    ? $nota->created_at->format(
                                        'd/m/Y H:i'
                                    )
                                    : 'N/A'
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Cliente
                        </small>

                        <strong>

                            {{
                                $nota->cliente?->pessoa?->nome
                                ?? 'Cliente Geral / Balcão'
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Placa
                        </small>

                        <strong>

                            {{
                                $nota->veiculosCliente?->placa
                                ?? 'N/A'
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            KM atual
                        </small>

                        <strong>

                            {{
                                $nota->km !== null
                                    ? number_format(
                                        $nota->km,
                                        0,
                                        ',',
                                        '.'
                                    ) . ' km'
                                    : 'N/A'
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Próxima troca de óleo
                        </small>

                        <strong>

                            {{
                                $nota->km_proxima_troca_oleo !== null
                                    ? number_format(
                                        $nota->km_proxima_troca_oleo,
                                        0,
                                        ',',
                                        '.'
                                    ) . ' km'
                                    : 'N/A'
                            }}

                        </strong>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block">
                            Itens
                        </small>

                        <strong>
                            {{ $nota->itens->count() }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         ITENS
    ========================================================== --}}

    <div class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">

                <i class="bi bi-list-ul"></i>
                Itens da nota

            </h5>

            @if($podeFinalizarNota)

                <a
                    href="{{ route(
                        'notasitem.edit',
                        $nota->id
                    ) }}"
                    class="btn btn-primary btn-sm"
                >

                    <i class="bi bi-pencil-square"></i>
                    Editar itens

                </a>

            @endif

        </div>

        <div class="card-body">

            @if($nota->itens->isEmpty())

                <div class="alert alert-info mb-0">

                    <i class="bi bi-info-circle"></i>
                    Esta nota ainda não possui itens cadastrados.

                </div>

            @else

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Descrição
                                </th>

                                <th>
                                    Qtd.
                                </th>

                                <th>
                                    Valor unit.
                                </th>

                                <th>
                                    Desconto
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Garantia
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($nota->itens as $item)

                                @php

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

                                    $quantidade =
                                        (float) (
                                            $item->quantidade
                                            ?? 0
                                        );

                                    $totalItem = max(
                                        0,
                                        (
                                            $quantidade
                                            * $valorUnitario
                                        ) - $desconto
                                    );

                                    $isProduto =
                                        $item->itemable_type
                                        === \App\Models\Produto::class;

                                    $isOrdemServico =
                                        $item->itemable_type
                                        === \App\Models\OrdemServico::class;

                                @endphp

                                <tr>

                                    <td>

                                        @if($isProduto)

                                            <span class="badge bg-primary">

                                                <i class="bi bi-box-seam"></i>
                                                Produto

                                            </span>

                                        @elseif($isOrdemServico)

                                            <span class="badge bg-warning text-dark">

                                                <i class="bi bi-tools"></i>
                                                O.S.

                                            </span>

                                        @else

                                            <span class="badge bg-secondary">

                                                <i class="bi bi-question-circle"></i>
                                                Item

                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if($isProduto)

                                            {{
                                                $item->itemable?->codigo_fabricante
                                                ?: $item->itemable?->codigo_barras
                                                ?: '—'
                                            }}

                                        @elseif($isOrdemServico)

                                            {{
                                                $item->itemable?->id
                                                    ? '#' . $item->itemable->id
                                                    : '—'
                                            }}

                                        @else

                                            —

                                        @endif

                                    </td>

                                    <td>

                                        {{
                                            $item->descricao
                                            ?? $item->itemable?->nome
                                            ?? $item->itemable?->descricao
                                            ?? 'Item sem descrição'
                                        }}

                                    </td>

                                    <td>
                                        {{ $quantidade }}
                                    </td>

                                    <td>

                                        R$
                                        {{
                                            number_format(
                                                $valorUnitario,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </td>

                                    <td>

                                        R$
                                        {{
                                            number_format(
                                                $desconto,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </td>

                                    <td>

                                        <strong>

                                            R$
                                            {{
                                                number_format(
                                                    $totalItem,
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                            }}

                                        </strong>

                                    </td>

                                    <td>

                                        @if(
                                            ($item->garantia_dias ?? 0)
                                            > 0
                                        )

                                            {{ $item->garantia_dias }}
                                            dias

                                        @else

                                            —

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
         RODAPÉ
    ========================================================== --}}

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

        <a
            href="{{ route('notas.index') }}"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>
            Voltar para notas

        </a>

        @if($podeFinalizarNota)

            <a
                href="{{ route(
                    'notasitem.edit',
                    $nota->id
                ) }}"
                class="btn btn-primary"
            >

                <i class="bi bi-pencil-square"></i>
                Editar nota

            </a>

        @endif

    </div>

</div>


{{-- =========================================================
     MODAL DE FINALIZAÇÃO
========================================================= --}}

@if($podeFinalizarNota)

    <div
        class="modal fade"
        id="modalFinalizarNota"
        tabindex="-1"
        aria-labelledby="modalFinalizarNotaLabel"
        aria-hidden="true"
        data-valor-total="{{ number_format(
            $totalNota,
            2,
            '.',
            ''
        ) }}"
        data-reabrir="{{ $errosFinalizacao ? '1' : '0' }}"
    >

        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

            <form
                method="POST"
                action="{{ route(
                    'notas.finalizar',
                    $nota->id
                ) }}"
                id="formFinalizarNota"
                class="modal-content"
            >

                @csrf

                <div class="modal-header">

                    <div>

                        <h1
                            class="modal-title fs-5 mb-1"
                            id="modalFinalizarNotaLabel"
                        >

                            <i class="bi bi-check-circle"></i>
                            Finalizar Nota #{{ $nota->id }}

                        </h1>

                        <div class="text-muted small">

                            {{
                                $contaReceber
                                    ? 'Confira os dados antes de finalizar a Nota.'
                                    : 'Configure a conta a receber antes de finalizar a Nota.'
                            }}

                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>

                </div>

                <div class="modal-body">

                    @if($errors->has('finalizacao'))

                        <div class="alert alert-danger">

                            <i class="bi bi-exclamation-triangle"></i>
                            {{ $errors->first('finalizacao') }}

                        </div>

                    @endif

                    @if(
                        $errors->has('categoria_financeira_id')
                        || $errors->has('parcelas')
                        || $errors->has('parcelas.*')
                    )

                        <div class="alert alert-danger">

                            <div class="fw-semibold mb-1">

                                <i class="bi bi-exclamation-triangle"></i>
                                Corrija os dados financeiros antes de finalizar.

                            </div>

                            <ul class="mb-0 ps-3">

                                @foreach($errors->all() as $erro)

                                    @if(
                                        $erro
                                            !== $errors->first('finalizacao')
                                        && $erro
                                            !== $errors->first('cancelamento')
                                        && $erro
                                            !== $errors->first('nota')
                                    )

                                        <li>
                                            {{ $erro }}
                                        </li>

                                    @endif

                                @endforeach

                            </ul>

                        </div>

                    @endif

                    <div class="alert alert-warning">

                        <div class="fw-semibold mb-1">

                            <i class="bi bi-exclamation-triangle"></i>
                            Atenção

                        </div>

                        Ao finalizar, os produtos serão baixados do estoque
                        e a Nota não poderá mais ser editada.

                        @if(!$contaReceber)

                            A Conta a Receber será criada nesta operação.

                        @endif

                    </div>

                    <div class="row g-3 mb-4">

                        <div class="col-12 col-lg-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-muted d-block mb-1">
                                    Nota
                                </small>

                                <strong>
                                    #{{ $nota->id }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-12 col-lg-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-muted d-block mb-1">
                                    Cliente
                                </small>

                                <strong>

                                    {{
                                        $nota->cliente?->pessoa?->nome
                                        ?? 'Cliente Geral / Balcão'
                                    }}

                                </strong>

                            </div>

                        </div>

                        <div class="col-12 col-lg-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-muted d-block mb-1">
                                    Total
                                </small>

                                <strong class="fs-5">

                                    R$
                                    {{
                                        number_format(
                                            $totalNota,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}

                                </strong>

                            </div>

                        </div>

                    </div>

                    @if($contaReceber)

                        <div class="card mb-0">

                            <div class="card-header">

                                <h2 class="h6 mb-0">

                                    <i class="bi bi-cash-coin"></i>
                                    Conta a Receber existente

                                </h2>

                            </div>

                            <div class="card-body">

                                <div class="row g-3">

                                    <div class="col-md-4">

                                        <small class="text-muted d-block">
                                            Conta
                                        </small>

                                        <strong>
                                            #{{ $contaReceber->id }}
                                        </strong>

                                    </div>

                                    <div class="col-md-4">

                                        <small class="text-muted d-block">
                                            Recebido
                                        </small>

                                        <strong>

                                            R$
                                            {{
                                                number_format(
                                                    $valorRecebido,
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                            }}

                                        </strong>

                                    </div>

                                    <div class="col-md-4">

                                        <small class="text-muted d-block">
                                            Saldo
                                        </small>

                                        <strong>

                                            R$
                                            {{
                                                number_format(
                                                    $saldoConta,
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                            }}

                                        </strong>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @else

                        <div class="card mb-4">

                            <div class="card-header">

                                <h2 class="h6 mb-0">

                                    <i class="bi bi-tag"></i>
                                    Classificação financeira

                                </h2>

                            </div>

                            <div class="card-body">

                                <label
                                    for="categoria_financeira_id"
                                    class="form-label"
                                >

                                    Categoria financeira de entrada

                                </label>

                                <select
                                    name="categoria_financeira_id"
                                    id="categoria_financeira_id"
                                    class="form-select @error('categoria_financeira_id') is-invalid @enderror"
                                    required
                                >

                                    <option value="">
                                        Selecione a categoria...
                                    </option>

                                    @foreach(
                                        $categoriasFinanceiras
                                        as $categoria
                                    )

                                        <option
                                            value="{{ $categoria->id }}"
                                            @selected(
                                                (string) old(
                                                    'categoria_financeira_id'
                                                )
                                                === (string) $categoria->id
                                            )
                                        >

                                            {{ $categoria->nome }}

                                        </option>

                                    @endforeach

                                </select>

                                @error('categoria_financeira_id')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>

                        <div class="card mb-4">

                            <div class="card-header">

                                <h2 class="h6 mb-0">

                                    <i class="bi bi-calendar3"></i>
                                    Parcelamento

                                </h2>

                            </div>

                            <div class="card-body">

                                <div class="row g-3">

                                    <div class="col-12 col-md-4">

                                        <label
                                            for="finalizacao_nota_parcelas_quantidade"
                                            class="form-label"
                                        >
                                            Quantidade de parcelas
                                        </label>

                                        <input
                                            type="number"
                                            id="finalizacao_nota_parcelas_quantidade"
                                            class="form-control"
                                            min="1"
                                            max="120"
                                            step="1"
                                            value="1"
                                            inputmode="numeric"
                                        >

                                    </div>

                                    <div class="col-12 col-md-4">

                                        <label
                                            for="finalizacao_nota_primeira_data_vencimento"
                                            class="form-label"
                                        >
                                            Primeiro vencimento
                                        </label>

                                        <input
                                            type="date"
                                            id="finalizacao_nota_primeira_data_vencimento"
                                            class="form-control"
                                            value="{{ now()->format('Y-m-d') }}"
                                        >

                                    </div>

                                    <div class="col-12 col-md-4">

                                        <label
                                            for="finalizacao_nota_intervalo_parcelas"
                                            class="form-label"
                                        >
                                            Intervalo
                                        </label>

                                        <select
                                            id="finalizacao_nota_intervalo_parcelas"
                                            class="form-select"
                                        >

                                            <option value="30">
                                                A cada 30 dias
                                            </option>

                                            <option value="15">
                                                A cada 15 dias
                                            </option>

                                            <option value="7">
                                                A cada 7 dias
                                            </option>

                                            <option value="1">
                                                Diariamente
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="card bg-light border mb-0">

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                                    <h2 class="h6 mb-0">

                                        <i class="bi bi-list-check"></i>
                                        Prévia das parcelas

                                    </h2>

                                    <span class="text-muted small">
                                        Confira os vencimentos antes de finalizar.
                                    </span>

                                </div>

                                <div id="finalizacao-nota-preview-parcelas"></div>

                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">

                                    <span class="fw-semibold">
                                        Total das parcelas
                                    </span>

                                    <strong
                                        id="finalizacao-nota-total"
                                        class="fs-5"
                                    >

                                        R$
                                        {{
                                            number_format(
                                                $totalNota,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </strong>

                                </div>

                            </div>

                        </div>

                        <div id="finalizacao-nota-parcelas-hidden"></div>

                    @endif

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >

                        <i class="bi bi-x-circle"></i>
                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="botaoFinalizarNota"
                    >

                        <i class="bi bi-check-circle"></i>
                        Finalizar nota

                    </button>

                </div>

            </form>

        </div>

    </div>

@endif


@if($podeFinalizarNota)

    @vite('resources/js/nota-show.js')

@endif

@endsection
