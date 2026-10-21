@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="ESTOQUE"
        icon="bi-boxes"
        create-route="produtos.create"
        create-text="Novo Produto"
        create-icon="bi-plus-lg"
    />

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-box-seam fs-2"></i>

                    <div>
                        <div class="text-muted small">
                            Produtos
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $totalProdutos }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle fs-2 text-success"></i>

                    <div>
                        <div class="text-muted small">
                            Estoque normal
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $produtosEstoqueNormal }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle fs-2 text-warning"></i>

                    <div>
                        <div class="text-muted small">
                            Estoque baixo
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $produtosBaixoEstoque }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-x-circle fs-2 text-danger"></i>

                    <div>
                        <div class="text-muted small">
                            Sem estoque
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $produtosZerados }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <x-filtros-container
        action="{{ route('estoque.index') }}"
        id="filtros-estoque"
        :collapsible="true"
        :expanded="request()->hasAny([
            'codigo',
            'codigo_fabricante',
            'codigo_barras',
            'marca',
            'status',
            'imagem'
        ])"
    >

        <x-slot:primary>

            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-5">

                    <label for="nome" class="form-label">
                        Produto
                    </label>

                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        class="filtros-container__input"
                        placeholder="Nome do produto"
                        value="{{ request('nome') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-3">

                    <label for="situacao" class="form-label">
                        Situação do estoque
                    </label>

                    <select
                        name="situacao"
                        id="situacao"
                        class="filtros-container__input"
                    >

                        <option value="">
                            Todas
                        </option>

                        <option
                            value="normal"
                            @selected(request('situacao') === 'normal')
                        >
                            Estoque normal
                        </option>

                        <option
                            value="baixo"
                            @selected(request('situacao') === 'baixo')
                        >
                            Estoque baixo
                        </option>

                        <option
                            value="zerado"
                            @selected(request('situacao') === 'zerado')
                        >
                            Sem estoque
                        </option>

                    </select>

                </div>

                <div class="col-12 col-md-3">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('estoque.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                            Limpar
                        </a>

                    </div>

                </div>

            </div>

        </x-slot:primary>

        <x-slot:advanced>

            <div class="row g-3">

                <div class="col-12 col-md-3">

                    <label
                        for="codigo"
                        class="form-label"
                    >
                        Código do produto
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="codigo"
                        class="filtros-container__input"
                        placeholder="Ex.: PRD-00001235"
                        value="{{ request('codigo') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-3">

                    <label
                        for="codigo_fabricante"
                        class="form-label"
                    >
                        Código do fabricante
                    </label>

                    <input
                        type="text"
                        name="codigo_fabricante"
                        id="codigo_fabricante"
                        class="filtros-container__input"
                        placeholder="Código do fabricante"
                        value="{{ request('codigo_fabricante') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-3">

                    <label
                        for="codigo_barras"
                        class="form-label"
                    >
                        Código de barras
                    </label>

                    <input
                        type="text"
                        name="codigo_barras"
                        id="codigo_barras"
                        class="filtros-container__input"
                        placeholder="Código de barras"
                        value="{{ request('codigo_barras') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-4">

                    <label
                        for="marca"
                        class="form-label"
                    >
                        Marca
                    </label>

                    <input
                        type="text"
                        name="marca"
                        id="marca"
                        class="filtros-container__input"
                        placeholder="Nome da marca"
                        value="{{ request('marca') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-6">

                    <label
                        for="status"
                        class="form-label"
                    >
                        Status do produto
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="filtros-container__input"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="1"
                            @selected(request('status') === '1')
                        >
                            Ativos
                        </option>

                        <option
                            value="0"
                            @selected(request('status') === '0')
                        >
                            Inativos
                        </option>

                    </select>

                </div>

                <div class="col-12 col-md-6">

                    <label
                        for="imagem"
                        class="form-label"
                    >
                        Imagem
                    </label>

                    <select
                        name="imagem"
                        id="imagem"
                        class="filtros-container__input"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="com_imagem"
                            @selected(request('imagem') === 'com_imagem')
                        >
                            Com imagem
                        </option>

                        <option
                            value="sem_imagem"
                            @selected(request('imagem') === 'sem_imagem')
                        >
                            Sem imagem
                        </option>

                    </select>

                </div>

            </div>

        </x-slot:advanced>

    </x-filtros-container>

    @if($produtos->isEmpty())

        @php
            $temFiltros = request()->hasAny([
                'nome',
                'codigo_fabricante',
                'codigo_barras',
                'situacao',
                'marca',
                'status',
                'imagem',
            ]);
        @endphp

        <div class="alert alert-{{ $temFiltros ? 'warning' : 'info' }}">

            <i class="bi {{ $temFiltros
                ? 'bi-exclamation-triangle'
                : 'bi-info-circle' }}">
            </i>

            {{ $temFiltros
                ? 'Nenhum produto encontrado com os filtros informados.'
                : 'Nenhum produto cadastrado.' }}

        </div>

    @else

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>
                        <th style="width: 72px;">
                            Foto
                        </th>

                        <th>
                            Produto
                        </th>

                        <th>
                            Códigos
                        </th>

                        <th class="text-center">
                            Estoque
                        </th>

                        <th class="text-center">
                            Mínimo
                        </th>

                        <th class="text-center">
                            Situação
                        </th>

                        <th class="text-center">
                            Cadastro
                        </th>

                        <th class="text-end">
                            Ações
                        </th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($produtos as $produto)

                        @php
                            $imagemProduto =
                                $produto
                                    ->imagens
                                    ->first()
                                    ?->caminho;

                            if ((float) $produto->quantidade <= 0) {
                                $situacao = 'zerado';
                            } elseif (
                                (float) $produto->quantidade
                                <= (float) $produto->estoque_minimo
                            ) {
                                $situacao = 'baixo';
                            } else {
                                $situacao = 'normal';
                            }
                        @endphp

                        <tr>

                            <td>

                                @if($imagemProduto)

                                    <img
                                        src="{{ asset('storage/'.$imagemProduto) }}"
                                        alt="{{ $produto->nome }}"
                                        class="rounded border"
                                        style="
                                            width: 58px;
                                            height: 58px;
                                            object-fit: contain;
                                            background: #fff;
                                        "
                                    >

                                @else

                                    <div
                                        class="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                        style="
                                            width: 58px;
                                            height: 58px;
                                        "
                                        title="Produto sem imagem"
                                    >
                                        <i class="bi bi-image fs-4"></i>
                                    </div>

                                @endif

                            </td>

                            <td>

                                <div class="fw-semibold">
                                    {{ $produto->nome }}
                                </div>

                                <div class="small text-muted">

                                    @if($produto->marcaRelacionada?->nome)

                                        <i class="bi bi-tag me-1"></i>

                                        {{ $produto->marcaRelacionada->nome }}

                                    @else

                                        Marca não informada

                                    @endif

                                </div>

                            </td>

                            <td>

                                <div class="small">

                                    <span class="text-muted">
                                        Produto:
                                    </span>

                                    <strong class="font-monospace">
                                        {{ $produto->codigo ?: '—' }}
                                    </strong>

                                </div>

                                <div class="small mt-1">

                                    <span class="text-muted">
                                        Fabricante:
                                    </span>

                                    <strong>
                                        {{ $produto->codigo_fabricante ?: '—' }}
                                    </strong>

                                </div>

                                <div class="small mt-1">

                                    <span class="text-muted">
                                        Barras:
                                    </span>

                                    <span>
                                        {{ $produto->codigo_barras ?: '—' }}
                                    </span>

                                </div>

                            </td>

                            <td class="text-center">

                                <div class="fw-bold fs-6">
                                    {{ number_format(
                                        (float) $produto->quantidade,
                                        3,
                                        ',',
                                        '.'
                                    ) }}
                                </div>

                            </td>

                            <td class="text-center">

                                {{ number_format(
                                    (float) $produto->estoque_minimo,
                                    3,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                            <td class="text-center">

                                @if($situacao === 'zerado')

                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Sem estoque
                                    </span>

                                @elseif($situacao === 'baixo')

                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Estoque baixo
                                    </span>

                                @else

                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Normal
                                    </span>

                                @endif

                            </td>

                            <td class="text-center">

                                @if($produto->status)

                                    <span class="badge bg-success">
                                        Ativo
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Inativo
                                    </span>

                                @endif

                            </td>

                            <td class="text-end">

                                <div class="d-flex justify-content-end gap-2">

                                    <a
                                        href="{{ route('produtos.show', $produto->id) }}"
                                        class="btn btn-sm btn-info"
                                        title="Visualizar produto"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('produtos.edit', $produto->id) }}"
                                        class="btn btn-sm btn-primary"
                                        title="Editar produto"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a
                                        href="{{ route('estoque.saida', $produto) }}"
                                        class="btn btn-sm btn-danger"
                                        title="Registrar saída"
                                    >
                                        <i class="bi bi-box-arrow-right"></i>
                                    </a>

                                    <a
                                        href="{{ route('estoque.ajuste', $produto) }}"
                                        class="btn btn-sm btn-warning"
                                        title="Ajustar estoque"
                                    >
                                        <i class="bi bi-sliders"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if($produtos->hasPages())

            <div class="d-flex justify-content-center mt-4">
                {{ $produtos->links() }}
            </div>

        @endif

    @endif

</div>

@endsection
