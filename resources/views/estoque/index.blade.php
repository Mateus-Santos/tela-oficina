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
                        <div class="text-muted small">Produtos</div>
                        <div class="fs-4 fw-bold">{{ $totalProdutos }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle fs-2"></i>
                    <div>
                        <div class="text-muted small">Estoque normal</div>
                        <div class="fs-4 fw-bold">{{ $produtosEstoqueNormal }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                    <div>
                        <div class="text-muted small">Estoque baixo</div>
                        <div class="fs-4 fw-bold">{{ $produtosBaixoEstoque }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-x-circle fs-2"></i>
                    <div>
                        <div class="text-muted small">Sem estoque</div>
                        <div class="fs-4 fw-bold">{{ $produtosZerados }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filtros-container
        action="{{ route('estoque.index') }}"
        id="filtros-estoque"
        :collapsible="true"
        :expanded="request()->hasAny(['codigo_fabricante', 'codigo_barras', 'situacao'])"
    >
        <x-slot:primary>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="nome" class="form-label">Produto</label>
                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        class="filtros-container__input"
                        placeholder="Nome do produto"
                        value="{{ request('nome') }}"
                    >
                </div>

                <div class="col-12 col-md-5">
                    <label for="situacao" class="form-label">Situação do estoque</label>
                    <select name="situacao" id="situacao" class="form-select">
                        <option value="">Todas</option>
                        <option value="normal" @selected(request('situacao') === 'normal')>Estoque normal</option>
                        <option value="baixo" @selected(request('situacao') === 'baixo')>Estoque baixo</option>
                        <option value="zerado" @selected(request('situacao') === 'zerado')>Sem estoque</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <div class="filtros-container__actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('estoque.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </x-slot:primary>

        <x-slot:advanced>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="codigo_fabricante" class="form-label">
                        Código do fabricante
                    </label>

                    <input
                        type="text"
                        name="codigo_fabricante"
                        id="codigo_fabricante"
                        class="filtros-container__input"
                        placeholder="Código do fabricante"
                        value="{{ request('codigo_fabricante') }}"
                    >
                </div>

                <div class="col-12 col-md-6">
                    <label for="codigo_barras" class="form-label">
                        Código de barras
                    </label>

                    <input
                        type="text"
                        name="codigo_barras"
                        id="codigo_barras"
                        class="filtros-container__input"
                        placeholder="Código de barras"
                        value="{{ request('codigo_barras') }}"
                    >
                </div>
            </div>
        </x-slot:advanced>
    </x-filtros-container>

    @if($produtos->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            {{ request()->hasAny(['nome', 'codigo_fabricante', 'codigo_barras', 'situacao'])
                ? 'Nenhum produto encontrado com os filtros informados.'
                : 'Nenhum produto cadastrado.' }}
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Cód. fabricante</th>
                        <th>Cód. barras</th>
                        <th class="text-center">Estoque</th>
                        <th class="text-center">Mínimo</th>
                        <th class="text-center">Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($produtos as $produto)
                        @php
                            if ($produto->quantidade <= 0) {
                                $situacao = 'zerado';
                            } elseif ($produto->quantidade <= $produto->estoque_minimo) {
                                $situacao = 'baixo';
                            } else {
                                $situacao = 'normal';
                            }
                        @endphp

                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $produto->nome }}</div>

                                @if($produto->marca)
                                    <small class="text-muted">{{ $produto->marca }}</small>
                                @endif
                            </td>

                            <td>{{ $produto->codigo_fabricante ?: '-' }}</td>
                            <td>{{ $produto->codigo_barras ?: '-' }}</td>

                            <td class="text-center fw-bold">
                                {{ number_format((float) $produto->quantidade, 3, ',', '.') }}
                            </td>

                            <td class="text-center">
                                {{ number_format((float) $produto->estoque_minimo, 3, ',', '.') }}
                            </td>

                            <td class="text-center">
                                @if($situacao === 'zerado')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i>
                                        Sem estoque
                                    </span>
                                @elseif($situacao === 'baixo')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Estoque baixo
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i>
                                        Normal
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
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
