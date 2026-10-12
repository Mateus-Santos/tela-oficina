@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR NOTAS"
        icon="bi-receipt"
        create-route="notasitem.create"
        create-text="Nova Nota"
        create-icon="bi-plus-lg"
    />

    <x-filtros-container
        action="{{ route('notas.index') }}"
        id="filtros-notas"
        :collapsible="true"
        :expanded="request()->hasAny([
            'veiculo',
            'placa',
            'status',
            'etapa_id'
        ])"
    >

        <x-slot:primary>

            <div class="row g-3 align-items-end">

                <div class="col-12 col-lg-5">

                    <label
                        for="cliente"
                        class="form-label"
                    >
                        <i class="bi bi-person"></i>
                        Cliente
                    </label>

                    <input
                        type="text"
                        name="cliente"
                        id="cliente"
                        class="filtros-container__input"
                        placeholder="Nome do cliente"
                        value="{{ request('cliente') }}"
                    >

                </div>

                <div class="col-12 col-md-4 col-lg-3">

                    <label
                        for="status"
                        class="form-label"
                    >
                        <i class="bi bi-clipboard-check"></i>
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="filtros-container__select"
                    >

                        <option
                            value=""
                            @selected(!request()->filled('status'))
                        >
                            Em aberto
                        </option>

                        <option
                            value="Todos"
                            @selected(request('status') === 'Todos')
                        >
                            Todos
                        </option>

                        <option
                            value="Aberto"
                            @selected(request('status') === 'Aberto')
                        >
                            Aberto
                        </option>

                        <option
                            value="Finalizado"
                            @selected(request('status') === 'Finalizado')
                        >
                            Finalizado
                        </option>

                        <option
                            value="Cancelado"
                            @selected(request('status') === 'Cancelado')
                        >
                            Cancelado
                        </option>

                    </select>

                </div>

                <div class="col-12 col-md-4 col-lg-3">

                    <label
                        for="etapa_id"
                        class="form-label"
                    >
                        <i class="bi bi-signpost-split"></i>
                        Etapa
                    </label>

                    <select
                        name="etapa_id"
                        id="etapa_id"
                        class="filtros-container__select"
                    >

                        <option value="">
                            Todas as etapas
                        </option>

                        @foreach($etapas as $etapa)

                            <option
                                value="{{ $etapa->id }}"
                                @selected(
                                    (int) request('etapa_id')
                                    === $etapa->id
                                )
                            >
                                {{ $etapa->nome }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-12 col-md-4 col-lg-1">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            title="Filtrar notas"
                        >
                            <i class="bi bi-search"></i>
                        </button>

                        <a
                            href="{{ route('notas.index') }}"
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

                    <label
                        for="veiculo"
                        class="form-label"
                    >
                        <i class="bi bi-car-front"></i>
                        Veículo
                    </label>

                    <input
                        type="text"
                        name="veiculo"
                        id="veiculo"
                        class="filtros-container__input"
                        placeholder="Ex.: Punto, Onix, Gol..."
                        value="{{ request('veiculo') }}"
                    >

                </div>

                <div class="col-12 col-md-6">

                    <label
                        for="placa"
                        class="form-label"
                    >
                        <i class="bi bi-credit-card-2-front"></i>
                        Placa
                    </label>

                    <input
                        type="text"
                        name="placa"
                        id="placa"
                        class="filtros-container__input text-uppercase"
                        placeholder="Ex.: ABC1D23"
                        value="{{ request('placa') }}"
                    >

                </div>

            </div>

        </x-slot:advanced>

    </x-filtros-container>

    @php

        $possuiFiltros = request()->hasAny([
            'cliente',
            'veiculo',
            'placa',
            'status',
            'etapa_id',
        ]);

    @endphp

    @if($notas->isEmpty())

        <div
            class="alert alert-{{
                $possuiFiltros
                    ? 'warning'
                    : 'info'
            }}"
        >

            <i
                class="bi {{
                    $possuiFiltros
                        ? 'bi-exclamation-triangle'
                        : 'bi-info-circle'
                }}"
            ></i>

            {{
                $possuiFiltros
                    ? 'Nenhuma nota encontrada com os filtros informados.'
                    : 'Nenhuma Nota em aberto no momento.'
            }}

        </div>

    @endif

    @foreach($notas as $nota)

        @php

            $quantidadeSemEstoque = 0;
            $quantidadeInsuficiente = 0;
            $quantidadeEstoqueBaixo = 0;
            $possuiProduto = false;

            if ($nota->status === 'Aberto') {

                foreach ($nota->itens as $item) {

                    if (
                        $item->itemable_type
                            !== \App\Models\Produto::class
                        || !$item->itemable
                    ) {
                        continue;
                    }

                    $possuiProduto = true;

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
                        (float) $item->quantidade;

                    if ($estoqueAtual <= 0) {
                        $quantidadeSemEstoque++;

                        continue;
                    }

                    if (
                        $quantidadeSolicitada
                        > $estoqueAtual
                    ) {
                        $quantidadeInsuficiente++;

                        continue;
                    }

                    if (
                        $estoqueAtual
                        <= $estoqueMinimo
                    ) {
                        $quantidadeEstoqueBaixo++;
                    }
                }

            } else {

                $possuiProduto =
                    $nota->itens->contains(
                        fn ($item) =>
                            $item->itemable_type
                            === \App\Models\Produto::class
                    );
            }

        @endphp

        <div class="card shadow-sm mb-3">

            <div class="card-body">

                <div
                    class="d-flex flex-column flex-lg-row justify-content-between gap-3"
                >

                    <div>

                        <div
                            class="d-flex flex-wrap align-items-center gap-2"
                        >

                            <h5 class="mb-0">

                                <i class="bi bi-receipt me-1"></i>

                                Nota #{{ $nota->id }}

                            </h5>

                            @if($nota->status === 'Aberto')

                                <span class="badge bg-primary">
                                    Aberto
                                </span>

                            @elseif(
                                in_array(
                                    $nota->status,
                                    [
                                        'Finalizado',
                                        'Concluido',
                                    ],
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

                        <div class="text-muted mt-1">

                            <i class="bi bi-person"></i>

                            {{
                                $nota
                                    ->cliente
                                    ?->pessoa
                                    ?->nome
                                ?? 'Cliente Geral / Balcão'
                            }}

                        </div>

                    </div>

                    <div class="text-lg-end">

                        <div class="small text-muted">
                            Valor
                        </div>

                        <div class="fs-5 fw-bold">

                            R$
                            {{
                                number_format(
                                    (float) $nota->total,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}

                        </div>

                    </div>

                </div>

                <hr>

                <div class="row g-3 align-items-start">

                    <div class="col-12 col-md-6 col-xl-3">

                        <div class="small text-muted mb-1">

                            <i class="bi bi-car-front"></i>
                            Veículo

                        </div>

                        @if(
                            $nota
                                ->veiculosCliente
                                ?->veiculo
                        )

                            <strong>

                                {{
                                    $nota
                                        ->veiculosCliente
                                        ->veiculo
                                        ->nome
                                }}

                            </strong>

                            @if(
                                $nota
                                    ->veiculosCliente
                                    ->veiculo
                                    ->montadora
                            )

                                <div class="small text-muted">

                                    {{
                                        $nota
                                            ->veiculosCliente
                                            ->veiculo
                                            ->montadora
                                            ->nome
                                    }}

                                </div>

                            @endif

                        @else

                            <span class="text-muted">
                                Não informado
                            </span>

                        @endif

                    </div>

                    <div class="col-6 col-md-3 col-xl-2">

                        <div class="small text-muted mb-1">
                            Placa
                        </div>

                        @if(
                            $nota
                                ->veiculosCliente
                                ?->placa
                        )

                            <span
                                class="badge bg-light text-dark border fs-6"
                            >
                                {{
                                    $nota
                                        ->veiculosCliente
                                        ->placa
                                }}
                            </span>

                        @else

                            <span class="text-muted">
                                N/A
                            </span>

                        @endif

                    </div>

                    <div class="col-6 col-md-3 col-xl-2">

                        <div class="small text-muted mb-1">
                            Status
                        </div>

                        @livewire(
                            'status-nota-selector',
                            [
                                'nota' => $nota,
                            ],
                            key(
                                'status-nota-'
                                . $nota->id
                            )
                        )

                    </div>

                    <div class="col-12 col-md-6 col-xl-3">

                        <div class="small text-muted mb-1">

                            <i class="bi bi-signpost-split"></i>
                            Etapa atual

                        </div>

                        @livewire(
                            'etapa-nota-selector',
                            [
                                'nota' => $nota,
                            ],
                            key(
                                'etapa-nota-'
                                . $nota->id
                            )
                        )

                    </div>

                    <div class="col-12 col-md-6 col-xl-2">

                        <div class="small text-muted mb-1">
                            Estoque
                        </div>

                        @if($nota->status !== 'Aberto')

                            <span class="badge bg-secondary">

                                <i class="bi bi-dash-circle"></i>
                                N/A

                            </span>

                        @elseif(!$possuiProduto)

                            <span
                                class="badge bg-light text-dark border"
                            >
                                <i class="bi bi-dash"></i>
                                Sem produtos
                            </span>

                        @elseif($quantidadeSemEstoque > 0)

                            <span class="badge bg-danger">

                                <i class="bi bi-exclamation-octagon"></i>
                                Sem estoque

                                @if($quantidadeSemEstoque > 1)
                                    ({{ $quantidadeSemEstoque }})
                                @endif

                            </span>

                        @elseif($quantidadeInsuficiente > 0)

                            <span class="badge bg-danger">

                                <i class="bi bi-exclamation-triangle"></i>
                                Insuficiente

                                @if($quantidadeInsuficiente > 1)
                                    ({{ $quantidadeInsuficiente }})
                                @endif

                            </span>

                        @elseif($quantidadeEstoqueBaixo > 0)

                            <span class="badge bg-warning text-dark">

                                <i class="bi bi-exclamation-triangle"></i>
                                Estoque baixo

                                @if($quantidadeEstoqueBaixo > 1)
                                    ({{ $quantidadeEstoqueBaixo }})
                                @endif

                            </span>

                        @else

                            <span class="badge bg-success">

                                <i class="bi bi-check-circle"></i>
                                OK

                            </span>

                        @endif

                    </div>

                </div>

                <div
                    class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-3 border-top"
                >

                    <div class="btn-group">

                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm dropdown-toggle"
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
                                    <i class="bi bi-eye me-2"></i>
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
                                    <i class="bi bi-download me-2"></i>
                                    Baixar
                                </a>

                            </li>

                        </ul>

                    </div>

                    @if(
                        auth()->user()
                        && auth()->user()->permitions != 2
                    )

                        <div class="dropdown">

                            <button
                                type="button"
                                class="btn btn-outline-dark btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                            >
                                <i class="bi bi-lock"></i>
                                PDF Interno
                            </button>

                            <div
                                class="dropdown-menu dropdown-menu-end p-3"
                                style="min-width: 290px;"
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
                                        for="senha-pdf-interno-{{ $nota->id }}"
                                        class="form-label fw-semibold"
                                    >
                                        <i class="bi bi-key"></i>
                                        Senha do PDF
                                    </label>

                                    <input
                                        type="password"
                                        name="senha"
                                        id="senha-pdf-interno-{{ $nota->id }}"
                                        class="form-control mb-2"
                                        minlength="4"
                                        maxlength="64"
                                        autocomplete="new-password"
                                        required
                                    >

                                    <small
                                        class="text-muted d-block mb-3"
                                    >
                                        A senha será exigida para abrir o arquivo.
                                    </small>

                                    <button
                                        type="submit"
                                        class="btn btn-dark btn-sm w-100"
                                    >
                                        <i class="bi bi-download"></i>
                                        Baixar protegido
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endif

                    <a
                        href="{{ route(
                            'notas.show',
                            $nota->id
                        ) }}"
                        class="btn btn-success btn-sm"
                    >
                        <i class="bi bi-eye"></i>
                        Visualizar
                    </a>

                    @if($nota->status === 'Aberto')

                        <form
                            action="{{ route(
                                'notas.destroy',
                                $nota->id
                            ) }}"
                            method="POST"
                            onsubmit="return confirm('Deseja realmente excluir esta Nota?');"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-outline-danger btn-sm"
                                title="Excluir Nota"
                            >
                                <i class="bi bi-trash3"></i>
                            </button>

                        </form>

                    @else

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            disabled
                        >
                            <i class="bi bi-lock"></i>
                        </button>

                    @endif

                </div>

            </div>

        </div>

    @endforeach

    @if($notas->hasPages())

        <div class="d-flex justify-content-center mt-4">

            {{ $notas->links() }}

        </div>

    @endif

</div>

@endsection
