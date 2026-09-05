@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="MOVIMENTAÇÕES DE ESTOQUE"
        icon="bi-arrow-left-right"
        create-route="estoque.index"
        create-text="Voltar ao Estoque"
        create-icon="bi-boxes"
    />

    <x-filtros-container
        action="{{ route('estoque.movimentacoes.index') }}"
        id="filtros-movimentacoes"
        :collapsible="true"
        :expanded="request()->hasAny(['tipo', 'data_inicio', 'data_fim'])"
    >
        <x-slot:primary>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label for="produto_id" class="form-label">Produto</label>
                    <select name="produto_id" id="produto_id" class="form-select">
                        <option value="">Todos os produtos</option>
                        @foreach($produtos as $produto)
                            <option
                                value="{{ $produto->id }}"
                                @selected((string) request('produto_id') === (string) $produto->id)
                            >
                                {{ $produto->nome }}
                                @if($produto->codigo_fabricante)
                                    - {{ $produto->codigo_fabricante }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <div class="filtros-container__actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('estoque.movimentacoes.index') }}"
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
                <div class="col-12 col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="entrada" @selected(request('tipo') === 'entrada')>
                            Entrada
                        </option>
                        <option value="saida" @selected(request('tipo') === 'saida')>
                            Saída
                        </option>
                        <option value="ajuste" @selected(request('tipo') === 'ajuste')>
                            Ajuste
                        </option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="data_inicio" class="form-label">Data inicial</label>
                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        class="form-control"
                        value="{{ request('data_inicio') }}"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label for="data_fim" class="form-label">Data final</label>
                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        class="form-control"
                        value="{{ request('data_fim') }}"
                    >
                </div>
            </div>
        </x-slot:advanced>
    </x-filtros-container>

    @if($movimentacoes->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            {{ request()->hasAny(['produto_id', 'tipo', 'data_inicio', 'data_fim'])
                ? 'Nenhuma movimentação encontrada com os filtros informados.'
                : 'Nenhuma movimentação de estoque registrada.' }}
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th class="text-center">Quantidade</th>
                        <th class="text-center">Saldo anterior</th>
                        <th class="text-center">Saldo posterior</th>
                        <th class="text-end">Valor unitário</th>
                        <th>Usuário</th>
                        <th>Origem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movimentacoes as $movimentacao)
                        <tr>
                            <td>
                                {{ $movimentacao->created_at->format('d/m/Y') }}
                                <br>
                                <small class="text-muted">
                                    {{ $movimentacao->created_at->format('H:i') }}
                                </small>
                            </td>

                            <td>
                                @if($movimentacao->produto)
                                    <div class="fw-semibold">
                                        {{ $movimentacao->produto->nome }}
                                    </div>

                                    @if($movimentacao->produto->codigo_fabricante)
                                        <small class="text-muted">
                                            Cód. {{ $movimentacao->produto->codigo_fabricante }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">
                                        Produto removido
                                    </span>
                                @endif
                            </td>

                            <td>
                                @switch($movimentacao->tipo)
                                    @case('entrada')
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-down-circle"></i>
                                            Entrada
                                        </span>
                                        @break

                                    @case('saida')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-up-circle"></i>
                                            Saída
                                        </span>
                                        @break

                                    @case('ajuste')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-sliders"></i>
                                            Ajuste
                                        </span>
                                        @break

                                    @default
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($movimentacao->tipo) }}
                                        </span>
                                @endswitch
                            </td>

                            <td class="text-center fw-bold">
                                {{ number_format((float) $movimentacao->quantidade, 3, ',', '.') }}
                            </td>

                            <td class="text-center">
                                {{ number_format((float) $movimentacao->saldo_anterior, 3, ',', '.') }}
                            </td>

                            <td class="text-center fw-bold">
                                {{ number_format((float) $movimentacao->saldo_posterior, 3, ',', '.') }}
                            </td>

                            <td class="text-end">
                                @if($movimentacao->valor_unitario !== null)
                                    R$ {{ number_format((float) $movimentacao->valor_unitario, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                {{ $movimentacao->usuario?->name ?? 'Sistema' }}
                            </td>

                            <td>
                                @if($movimentacao->origem)
                                    {{ class_basename($movimentacao->origem_type) }}
                                    #{{ $movimentacao->origem_id }}
                                @else
                                    <span class="text-muted">Manual</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($movimentacoes->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $movimentacoes->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
