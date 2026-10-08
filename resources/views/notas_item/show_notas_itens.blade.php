@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    {{-- =====================================================
        CABEÇALHO
    ====================================================== --}}

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">

                <h1 class="mb-0">
                    <i class="bi bi-receipt"></i>
                    NOTA #{{ $nota->id }}
                </h1>

                <span class="badge {{ $statusBadgeClass }}">
                    {{ $statusLabel }}
                </span>

            </div>

            <div class="text-muted">
                {{ $clienteNome }}

                @if($placa !== 'Não informada')
                    • {{ $placa }}
                @endif
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

            @if($contaReceber)

                <a
                    href="{{ route(
                        'contas-receber.show',
                        $contaReceber
                    ) }}"
                    class="btn btn-success"
                >
                    <i class="bi bi-cash-coin"></i>
                    Conta #{{ $contaReceber->id }}
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

            {{-- =====================================================
                PDF CLIENTE
            ====================================================== --}}

            <div class="btn-group">

                <button
                    type="button"
                    class="btn btn-danger dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-file-earmark-pdf"></i>
                    PDF Cliente
                </button>

                <ul class="dropdown-menu">

                    <li>
                        <a
                            href="{{ route(
                                'notas.pdf',
                                $nota->id
                            ) }}"
                            target="_blank"
                            class="dropdown-item"
                        >
                            <i class="bi bi-eye"></i>
                            Visualizar
                        </a>
                    </li>

                    <li>
                        <a
                            href="{{ route(
                                'notas.pdf.download',
                                $nota->id
                            ) }}"
                            class="dropdown-item"
                        >
                            <i class="bi bi-download"></i>
                            Baixar
                        </a>
                    </li>

                </ul>

            </div>

            {{-- =====================================================
                PDF INTERNO
            ====================================================== --}}

            @if(
                auth()->user()
                && auth()->user()->permitions != 2
            )

                <div class="dropdown">

                    <button
                        type="button"
                        class="btn btn-dark dropdown-toggle"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                    >
                        <i class="bi bi-file-earmark-lock"></i>
                        PDF Interno
                    </button>

                    <div
                        class="dropdown-menu dropdown-menu-end p-3"
                        style="min-width: 300px;"
                    >

                        <form
                            method="POST"
                            action="{{ route(
                                'notas.pdf.interno.download',
                                $nota
                            ) }}"
                        >

                            @csrf

                            <label
                                for="senha-pdf-interno-show"
                                class="form-label fw-semibold"
                            >
                                <i class="bi bi-key"></i>
                                Senha do PDF
                            </label>

                            <input
                                type="password"
                                name="senha"
                                id="senha-pdf-interno-show"
                                class="form-control mb-2"
                                minlength="4"
                                maxlength="64"
                                autocomplete="new-password"
                                required
                            >

                            <div class="form-text mb-3">
                                A senha será exigida para abrir o arquivo.
                            </div>

                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >
                                <i class="bi bi-download"></i>
                                Baixar PDF protegido
                            </button>

                        </form>

                    </div>

                </div>

            @endif

        </div>

    </div>


    {{-- =====================================================
        MENSAGENS
    ====================================================== --}}

    @if(session('success'))

        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>

    @endif

    @foreach(
        ['finalizacao', 'cancelamento', 'nota']
        as $campoErro
    )

        @if($errors->has($campoErro))

            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                {{ $errors->first($campoErro) }}
            </div>

        @endif

    @endforeach


    {{-- =====================================================
        IDENTIFICAÇÃO
    ====================================================== --}}

    <div class="card mb-4">

        <div class="card-header">

            <h5 class="mb-0">
                <i class="bi bi-info-circle"></i>
                Dados da nota
            </h5>

        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-person"></i>
                            Cliente
                        </small>

                        <strong>
                            {{ $clienteNome }}
                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-car-front"></i>
                            Veículo
                        </small>

                        <strong>
                            {{ $veiculoDescricao }}
                        </strong>

                        @if($montadoraNome)

                            <div class="small text-muted mt-1">
                                {{ $montadoraNome }}
                            </div>

                        @endif

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-card-text"></i>
                            Placa
                        </small>

                        <strong>
                            {{ $placa }}
                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-calendar3"></i>
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

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-tag"></i>
                            Tipo
                        </small>

                        <strong>
                            {{ $nota->tipo ?? 'N/A' }}
                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-speedometer2"></i>
                            KM atual
                        </small>

                        <strong>
                            @if($nota->km !== null)

                                {{
                                    number_format(
                                        $nota->km,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                km

                            @else

                                Não informado

                            @endif
                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-arrow-repeat"></i>
                            Próxima troca de óleo
                        </small>

                        <strong>
                            @if(
                                $nota->km_proxima_troca_oleo
                                !== null
                            )

                                {{
                                    number_format(
                                        $nota->km_proxima_troca_oleo,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                km

                            @else

                                Não informada

                            @endif
                        </strong>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-list-ul"></i>
                            Itens
                        </small>

                        <strong>
                            {{ $itensExibicao->count() }}
                        </strong>

                    </div>

                </div>

            </div>

            @if($nota->observacoes)

                <div class="border rounded p-3 mt-3">

                    <small class="text-muted d-block mb-1">
                        <i class="bi bi-chat-left-text"></i>
                        Observações
                    </small>

                    <div>
                        {{ $nota->observacoes }}
                    </div>

                </div>

            @endif

        </div>

    </div>


    {{-- =====================================================
        RESUMO FINANCEIRO
    ====================================================== --}}

    <div class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">
                <i class="bi bi-calculator"></i>
                Resumo financeiro
            </h5>

            @if($contaReceber)

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
                            Total
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
                                Conta
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


    {{-- =====================================================
        ESTOQUE
    ====================================================== --}}

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

                        Existem produtos sem estoque ou com
                        quantidade insuficiente para finalizar a Nota.

                    @elseif($quantidadeEstoqueBaixo > 0)

                        Há estoque suficiente, mas existem produtos
                        no estoque mínimo.

                    @else

                        Todos os produtos possuem estoque suficiente.

                    @endif

                </div>

                @if(count($problemasEstoque))

                    <span class="badge bg-dark">
                        {{ count($problemasEstoque) }}
                        alerta(s)
                    </span>

                @endif

            </div>

            @if(count($problemasEstoque))

                <div class="table-responsive mt-3">

                    <table class="table table-sm table-bordered align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Solicitado</th>
                                <th>Estoque</th>
                                <th>Mínimo</th>
                                <th>Situação</th>
                                <th class="text-center">Ação</th>
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

                                    <td class="text-center">

                                        <a
                                            href="{{ route(
                                                'estoque.ajuste',
                                                $problema['produto_id']
                                            ) }}"
                                            class="btn btn-outline-primary btn-sm"
                                        >
                                            <i class="bi bi-sliders"></i>
                                            Ajustar estoque
                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    @endif


    {{-- =====================================================
        ITENS
    ====================================================== --}}

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

            @if($itensExibicao->isEmpty())

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    Esta nota ainda não possui itens cadastrados.
                </div>

            @else

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Qtd.</th>
                                <th>Valor unit.</th>
                                <th>Desconto</th>
                                <th>Total</th>
                                <th>Garantia</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($itensExibicao as $item)

                                <tr>

                                    <td>
                                        <span class="badge {{ $item['tipo_classe'] }}">
                                            <i class="bi {{ $item['tipo_icone'] }}"></i>
                                            {{ $item['tipo'] }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $item['codigo'] }}
                                    </td>

                                    <td>
                                        {{ $item['descricao'] }}
                                    </td>

                                    <td>
                                        {{ $item['quantidade'] }}
                                    </td>

                                    <td>
                                        R$
                                        {{
                                            number_format(
                                                $item['valor_unitario'],
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
                                                $item['desconto'],
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
                                                    $item['total'],
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                            }}
                                        </strong>
                                    </td>

                                    <td>
                                        @if($item['garantia_dias'] > 0)

                                            {{ $item['garantia_dias'] }}
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


    {{-- =====================================================
        STATUS / AÇÕES
    ====================================================== --}}

    <div class="card mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <strong>
                        @if($nota->status === 'Aberto')
                            Nota aberta
                        @elseif(
                            in_array(
                                $nota->status,
                                ['Finalizado', 'Concluido'],
                                true
                            )
                        )
                            Nota finalizada
                        @elseif($nota->status === 'Cancelado')
                            Nota cancelada
                        @else
                            {{ $nota->status }}
                        @endif
                    </strong>

                    <div class="text-muted">

                        @if($nota->status === 'Aberto')

                            A Nota ainda pode ser alterada antes da finalização.

                        @elseif(
                            in_array(
                                $nota->status,
                                ['Finalizado', 'Concluido'],
                                true
                            )
                        )

                            A Nota não pode mais ser editada.

                        @elseif($nota->status === 'Cancelado')

                            A Nota não pode mais ser alterada.

                        @endif

                    </div>

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
                                $nota
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


@if($podeFinalizarNota)

    @include(
        'notas_item.modal._modal_finalizar_nota'
    )

    @vite('resources/js/nota-show.js')

@endif

@endsection
