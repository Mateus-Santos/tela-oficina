@extends('layouts.layout')

@section('content')

@php
    $clienteInicialId = old(
        'cliente_id',
        $notaSelecionada?->cliente_id
    );

    $notaInicialId = old(
        'nota_id',
        $notaSelecionada?->id
    );

    $valorInicial = old(
        'valor_original',
        $notaSelecionada
            ? number_format(
                (float) $notaSelecionada->total,
                2,
                '.',
                ''
            )
            : ''
    );

    $descricaoInicial = old(
        'descricao',
        $notaSelecionada
            ? "Conta a receber da Nota #{$notaSelecionada->id}."
            : ''
    );
@endphp

<section
    class="container cadastro"
    id="cadastro-conta-receber"
    data-clientes-endpoint="{{ route('api.contas-receber.clientes.buscar') }}"
    data-notas-endpoint="{{ route('api.contas-receber.notas.buscar') }}"
>
    <h1>
        <i class="bi bi-cash-stack"></i>
        CADASTRO DE CONTA A RECEBER
    </h1>

    @if ($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form
        action="{{ route('contas-receber.store') }}"
        method="POST"
        id="form-conta-receber"
    >
        @csrf

        {{-- =========================================================
             CLIENTE E NOTA
        ========================================================== --}}

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-vcard"></i>
                    Cliente e Nota
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    {{-- BUSCA CLIENTE --}}
                    <div class="col-md-6">
                        <label
                            for="cliente_busca"
                            class="form-label"
                        >
                            <i class="bi bi-person-search"></i>
                            Cliente
                        </label>

                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input
                                    type="search"
                                    class="form-control"
                                    id="cliente_busca"
                                    placeholder="Digite o nome do cliente..."
                                    autocomplete="off"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="btn-limpar-cliente"
                                    title="Limpar cliente"
                                >
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <div
                                id="cliente-resultados"
                                class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1050;"
                            ></div>
                        </div>

                        <div
                            id="cliente-status"
                            class="small text-muted mt-1"
                        >
                            @if($notaSelecionada?->cliente_id)
                                Cliente carregado através da Nota.
                            @elseif($notaSelecionada)
                                Esta Nota é uma venda de balcão.
                            @else
                                Digite para pesquisar.
                            @endif
                        </div>

                        <div
                            id="cliente-selecionado"
                            class="mt-2 {{ $notaSelecionada?->cliente_id ? '' : 'd-none' }}"
                        >
                            @if($notaSelecionada?->cliente_id)
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="bi bi-person-check"></i>

                                    <strong>
                                        Cliente selecionado:
                                    </strong>

                                    {{ $notaSelecionada->cliente?->pessoa?->nome ?? 'Cliente sem nome' }}
                                </div>
                            @endif
                        </div>

                        @if($notaSelecionada && !$notaSelecionada->cliente_id)
                            <div class="alert alert-light border mt-2 mb-0">
                                <i class="bi bi-shop me-1"></i>

                                <strong>Venda de balcão</strong>

                                <div class="small text-muted">
                                    A Nota #{{ $notaSelecionada->id }}
                                    não possui cliente vinculado.
                                </div>
                            </div>
                        @endif

                        <input
                            type="hidden"
                            name="cliente_id"
                            id="cliente_id"
                            value="{{ $clienteInicialId }}"
                        >
                    </div>

                    {{-- BUSCA NOTA --}}
                    <div class="col-md-6">
                        <label
                            for="nota_busca"
                            class="form-label"
                        >
                            <i class="bi bi-receipt"></i>
                            Nota
                        </label>

                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input
                                    type="search"
                                    class="form-control"
                                    id="nota_busca"
                                    placeholder="Digite o número da nota ou selecione um cliente..."
                                    autocomplete="off"
                                    value="{{ $notaSelecionada
                                        ? '#' . str_pad(
                                            (string) $notaSelecionada->id,
                                            6,
                                            '0',
                                            STR_PAD_LEFT
                                        )
                                        : ''
                                    }}"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="btn-limpar-nota"
                                    title="Limpar nota"
                                >
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <div
                                id="nota-resultados"
                                class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1050;"
                            ></div>
                        </div>

                        <div
                            id="nota-status"
                            class="small text-muted mt-1"
                        >
                            @if($notaSelecionada)
                                Nota carregada automaticamente.
                            @else
                                Busque uma nota ou selecione primeiro um cliente.
                            @endif
                        </div>

                        <div
                            id="nota-selecionada"
                            class="mt-2 {{ $notaSelecionada ? '' : 'd-none' }}"
                        >
                            @if($notaSelecionada)
                                <div class="alert alert-primary py-2 mb-0">
                                    <div
                                        class="d-flex justify-content-between align-items-center flex-wrap gap-2"
                                    >
                                        <div>
                                            <i class="bi bi-receipt"></i>

                                            <strong>
                                                Nota #{{ str_pad(
                                                    (string) $notaSelecionada->id,
                                                    6,
                                                    '0',
                                                    STR_PAD_LEFT
                                                ) }}
                                            </strong>

                                            -

                                            {{ $notaSelecionada->cliente?->pessoa?->nome
                                                ?? 'Venda de balcão'
                                            }}
                                        </div>

                                        <strong>
                                            R$ {{ number_format(
                                                (float) $notaSelecionada->total,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </strong>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <input
                            type="hidden"
                            name="nota_id"
                            id="nota_id"
                            value="{{ $notaInicialId }}"
                        >
                    </div>

                </div>

                <div class="alert alert-light border mt-3 mb-0">
                    <i class="bi bi-info-circle"></i>

                    A Nota é opcional. Ao selecionar uma Nota,
                    o cliente e o valor original serão preenchidos
                    automaticamente.

                    Notas abertas ou finalizadas podem ser vinculadas,
                    desde que não estejam canceladas e ainda não possuam
                    uma Conta a Receber.
                </div>
            </div>
        </div>

        {{-- =========================================================
             DADOS DA CONTA
        ========================================================== --}}

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-card-text"></i>
                    Dados da Conta
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label
                            for="categoria_financeira_id"
                            class="form-label"
                        >
                            Categoria Financeira *
                        </label>

                        <select
                            class="form-select"
                            id="categoria_financeira_id"
                            name="categoria_financeira_id"
                            required
                        >
                            <option value="">
                                Selecione uma categoria
                            </option>

                            @foreach ($categorias as $categoria)
                                <option
                                    value="{{ $categoria->id }}"
                                    {{ old('categoria_financeira_id') == $categoria->id ? 'selected' : '' }}
                                >
                                    {{ $categoria->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label
                            for="descricao"
                            class="form-label"
                        >
                            Descrição *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="descricao"
                            name="descricao"
                            value="{{ $descricaoInicial }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            for="valor_original"
                            class="form-label"
                        >
                            Valor Original (R$) *
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control"
                            id="valor_original"
                            name="valor_original"
                            value="{{ $valorInicial }}"
                            {{ $notaSelecionada ? 'readonly' : '' }}
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            for="desconto"
                            class="form-label"
                        >
                            Desconto (R$)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="desconto"
                            name="desconto"
                            value="{{ old('desconto', 0) }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            for="juros"
                            class="form-label"
                        >
                            Juros (R$)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="juros"
                            name="juros"
                            value="{{ old('juros', 0) }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            for="multa"
                            class="form-label"
                        >
                            Multa (R$)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="multa"
                            name="multa"
                            value="{{ old('multa', 0) }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            for="data_emissao"
                            class="form-label"
                        >
                            Data de Emissão
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_emissao"
                            name="data_emissao"
                            value="{{ old(
                                'data_emissao',
                                now()->format('Y-m-d')
                            ) }}"
                        >
                    </div>

                    <div class="col-12">
                        <label
                            for="observacoes"
                            class="form-label"
                        >
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="observacoes"
                            name="observacoes"
                            rows="4"
                        >{{ old('observacoes') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================================================
             PARCELAMENTO
        ========================================================== --}}

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar2-range"></i>
                    Parcelamento
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label
                            for="parcelas_quantidade"
                            class="form-label"
                        >
                            Quantidade de parcelas *
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="parcelas_quantidade"
                            name="parcelas_quantidade"
                            min="1"
                            max="120"
                            step="1"
                            value="{{ old('parcelas_quantidade', 1) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label
                            for="primeira_data_vencimento"
                            class="form-label"
                        >
                            Primeiro vencimento *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="primeira_data_vencimento"
                            name="primeira_data_vencimento"
                            value="{{ old(
                                'primeira_data_vencimento',
                                now()->addDays(30)->format('Y-m-d')
                            ) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label
                            for="intervalo_parcelas"
                            class="form-label"
                        >
                            Intervalo entre parcelas
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                class="form-control"
                                id="intervalo_parcelas"
                                name="intervalo_parcelas"
                                min="1"
                                step="1"
                                value="{{ old(
                                    'intervalo_parcelas',
                                    30
                                ) }}"
                            >

                            <span class="input-group-text">
                                dias
                            </span>
                        </div>
                    </div>

                </div>

                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"
                >
                    <div>
                        <strong>
                            Prévia das parcelas
                        </strong>

                        <div class="text-muted small">
                            Confira os valores e vencimentos antes
                            de cadastrar.
                        </div>
                    </div>

                    <div class="fs-5">
                        Total:

                        <strong id="parcelas-total">
                            R$ 0,00
                        </strong>
                    </div>
                </div>

                <div id="parcelas-preview"></div>

                <div id="parcelas-hidden"></div>
            </div>
        </div>

        {{-- =========================================================
             AÇÕES
        ========================================================== --}}

        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4"
        >
            @if($notaSelecionada)
                <a
                    href="{{ route('notas.show', $notaSelecionada->id) }}"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Voltar para Nota
                </a>
            @else
                <a
                    href="{{ route('contas-receber.index') }}"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Voltar
                </a>
            @endif

            <button
                type="submit"
                class="btn btn-success"
                id="btn-cadastrar-conta"
            >
                <i class="bi bi-check-circle"></i>
                Cadastrar Conta
            </button>
        </div>

    </form>
</section>

@vite('resources/js/conta-receber.js')

@endsection
