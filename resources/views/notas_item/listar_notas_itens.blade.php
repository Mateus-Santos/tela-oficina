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
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-5">

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

            <div class="col-12 col-md-5">

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
                    <option value="">
                        Status (Ativos por padrão)
                    </option>

                    <option
                        value="Aberto"
                        @selected(request('status') === 'Aberto')
                    >
                        Aberto
                    </option>

                    <option
                        value="Andamento"
                        @selected(request('status') === 'Andamento')
                    >
                        Em Andamento
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

            <div class="col-12 col-md-2">

                <div class="filtros-container__actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        title="Filtrar notas"
                    >
                        <i class="bi bi-search"></i>
                        Filtrar
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

    </x-filtros-container>

    @if ($notas->isEmpty())

        <div
            class="alert alert-{{ request()->hasAny(['cliente', 'status']) ? 'warning' : 'info' }}"
        >
            <i
                class="bi {{ request()->hasAny(['cliente', 'status']) ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"
            ></i>

            {{
                request()->hasAny(['cliente', 'status'])
                    ? 'Nenhuma nota encontrada com os filtros informados.'
                    : 'Nenhuma nota cadastrada.'
            }}
        </div>

    @endif

    @if ($notas->isNotEmpty())

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead>

                    <tr>

                        <th scope="col">
                            ID
                        </th>

                        <th scope="col">
                            STATUS
                        </th>

                        <th scope="col">
                            CLIENTE
                        </th>

                        <th scope="col">
                            VEÍCULO
                        </th>

                        <th scope="col">
                            PLACA
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                        >
                            VALOR
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            ESTOQUE
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            IMPRIMIR
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            VER
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            EXCLUIR
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($notas as $nota)

                        @php
                            /*
                             * =====================================================
                             * ANÁLISE DE ESTOQUE DA NOTA
                             * =====================================================
                             *
                             * Só analisamos Notas abertas.
                             *
                             * Após a finalização, o estoque já foi movimentado,
                             * portanto o saldo atual não representa a
                             * disponibilidade existente no momento da venda.
                             */

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

                                    $produto = $item->itemable;

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

                                    /*
                                     * Sem nenhuma unidade disponível.
                                     */
                                    if ($estoqueAtual <= 0) {

                                        $quantidadeSemEstoque++;

                                        continue;
                                    }

                                    /*
                                     * Existe estoque, mas não é suficiente
                                     * para atender esta Nota.
                                     */
                                    if (
                                        $quantidadeSolicitada
                                        > $estoqueAtual
                                    ) {

                                        $quantidadeInsuficiente++;

                                        continue;
                                    }

                                    /*
                                     * Há estoque suficiente para a Nota,
                                     * porém o Produto está no estoque mínimo.
                                     */
                                    if (
                                        $estoqueAtual
                                        <= $estoqueMinimo
                                    ) {

                                        $quantidadeEstoqueBaixo++;
                                    }
                                }

                            } else {

                                /*
                                 * Para Notas não abertas precisamos apenas
                                 * descobrir se existia Produto, para manter
                                 * a informação estrutural disponível caso
                                 * seja necessária posteriormente.
                                 */
                                $possuiProduto =
                                    $nota->itens->contains(
                                        function ($item) {
                                            return
                                                $item->itemable_type
                                                === \App\Models\Produto::class;
                                        }
                                    );
                            }
                        @endphp

                        <tr>

                            {{-- ID --}}
                            <td>

                                <strong>
                                    #{{ $nota->id }}
                                </strong>

                            </td>

                            {{-- STATUS --}}
                            <td>

                                @livewire(
                                    'status-nota-selector',
                                    ['nota' => $nota],
                                    key('status-nota-' . $nota->id)
                                )

                            </td>

                            {{-- CLIENTE --}}
                            <td>

                                {{
                                    $nota->cliente?->pessoa?->nome
                                    ?? 'Cliente Geral / Balcão'
                                }}

                            </td>

                            {{-- VEÍCULO --}}
                            <td>

                                @if($nota->veiculosCliente?->veiculo)

                                    {{
                                        $nota
                                            ->veiculosCliente
                                            ->veiculo
                                            ->nome
                                    }}

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
                                        N/A
                                    </span>

                                @endif

                            </td>

                            {{-- PLACA --}}
                            <td>

                                @if($nota->veiculosCliente?->placa)

                                    <span class="badge bg-light text-dark border">

                                        <i class="bi bi-car-front"></i>

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

                            </td>

                            {{-- VALOR --}}
                            <td class="text-end">

                                <strong class="text-nowrap">

                                    R$
                                    {{
                                        number_format(
                                            (float) $nota->total,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}

                                </strong>

                            </td>

                            {{-- ESTOQUE --}}
                            <td class="text-center">

                                @if($nota->status !== 'Aberto')

                                    <span
                                        class="badge bg-secondary"
                                        title="O estoque já não precisa ser analisado para esta Nota."
                                    >
                                        <i class="bi bi-dash-circle"></i>
                                        N/A
                                    </span>

                                @elseif(!$possuiProduto)

                                    <span
                                        class="badge bg-light text-dark border"
                                        title="Esta Nota não possui produtos."
                                    >
                                        <i class="bi bi-dash"></i>
                                        Sem produtos
                                    </span>

                                @elseif($quantidadeSemEstoque > 0)

                                    <span
                                        class="badge bg-danger"
                                        title="Existe produto sem estoque nesta Nota."
                                    >
                                        <i class="bi bi-exclamation-octagon"></i>
                                        Sem estoque

                                        @if($quantidadeSemEstoque > 1)

                                            ({{ $quantidadeSemEstoque }})

                                        @endif
                                    </span>

                                @elseif($quantidadeInsuficiente > 0)

                                    <span
                                        class="badge bg-danger"
                                        title="A quantidade disponível de um ou mais produtos é menor que a quantidade informada na Nota."
                                    >
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Insuficiente

                                        @if($quantidadeInsuficiente > 1)

                                            ({{ $quantidadeInsuficiente }})

                                        @endif
                                    </span>

                                @elseif($quantidadeEstoqueBaixo > 0)

                                    <span
                                        class="badge bg-warning text-dark"
                                        title="Existe produto no estoque mínimo nesta Nota."
                                    >
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Estoque baixo

                                        @if($quantidadeEstoqueBaixo > 1)

                                            ({{ $quantidadeEstoqueBaixo }})

                                        @endif
                                    </span>

                                @else

                                    <span
                                        class="badge bg-success"
                                        title="Os produtos desta Nota possuem estoque suficiente."
                                    >
                                        <i class="bi bi-check-circle"></i>
                                        OK
                                    </span>

                                @endif

                            </td>

                            {{-- PDF --}}
                            <td class="text-center">

                                <a
                                    href="{{ route('notas.pdf', $nota->id) }}"
                                    target="_blank"
                                    class="btn btn-danger"
                                    title="Imprimir nota"
                                >
                                    <i class="bi bi-printer"></i>
                                    PDF
                                </a>

                            </td>

                            {{-- VER --}}
                            <td class="text-center">

                                <a
                                    href="{{ route('notas.show', $nota->id) }}"
                                    class="btn btn-success"
                                    title="Visualizar nota"
                                >
                                    <i class="bi bi-list-task"></i>
                                </a>

                            </td>

                            {{-- EXCLUIR --}}
                            <td class="text-center">

                                @if($nota->status === 'Aberto')

                                    <form
                                        action="{{ route('notas.destroy', $nota->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Deseja realmente excluir esta nota?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger"
                                            title="Excluir nota"
                                        >
                                            <i class="bi bi-trash3"></i>
                                        </button>

                                    </form>

                                @else

                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        title="Somente Notas abertas podem ser excluídas"
                                        disabled
                                    >
                                        <i class="bi bi-lock"></i>
                                    </button>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if($notas->hasPages())

            <div class="d-flex justify-content-center mt-4">

                {{ $notas->links() }}

            </div>

        @endif

    @endif

</div>

@endsection
