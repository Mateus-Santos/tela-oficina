@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <h1>DETALHES DA NOTA #{{ $nota->id }}</h1>

    {{-- ============================================================
         INFORMAÇÕES GERAIS DA NOTA
    ============================================================ --}}

    <table class="table">

        <thead>
            <tr>
                <th scope="col">ID Nota</th>
                <th scope="col">Status</th>
                <th scope="col">Data Criação</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo / Placa</th>
                <th scope="col">PDF</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{{ $nota->id }}</td>

                <td>{{ $nota->status }}</td>

                <td>
                    {{ $nota->created_at
                        ? $nota->created_at->format('d/m/Y H:i')
                        : 'N/A'
                    }}
                </td>

                <td>
                    {{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}
                </td>

                <td>
                    {{ $nota->veiculosCliente?->placa ?? 'N/A' }}
                </td>

                <td>
                    <a
                        href="{{ route('notas.pdf', $nota->id) }}"
                        target="_blank"
                        class="btn btn-danger"
                    >
                        <i class="bi bi-printer"></i>
                        PDF
                    </a>
                </td>
            </tr>
        </tbody>

    </table>

    <hr class="my-4">


    {{-- ============================================================
         SEÇÃO DE ITENS DA NOTA
    ============================================================ --}}

    @if($itens->isEmpty())

        <div class="alert alert-info d-flex justify-content-between align-items-center">

            <span>
                Esta nota ainda não possui itens cadastrados.
            </span>

            @if(auth()->user() && auth()->user()->permitions != 2)

                <a
                    class="btn btn-success"
                    href="{{ route('notasitem.edit', $nota->id) }}"
                >
                    <i class="bi bi-plus-circle"></i>
                    Adicionar Item
                </a>

            @endif

        </div>

    @else

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h2>ITENS DA NOTA</h2>

            @if(auth()->user() && auth()->user()->permitions != 2)

                <a
                    class="btn btn-success"
                    href="{{ route('notasitem.edit', $nota->id) }}"
                >
                    <i class="bi bi-plus-circle"></i>
                    Adicionar Item
                </a>

            @endif

        </div>


        {{-- ========================================================
             TABELA DE ITENS
        ======================================================== --}}

        <table class="table">

            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Descrição / Nome</th>
                    <th scope="col">Qtd.</th>
                    <th scope="col">Preço Unit.</th>
                    <th scope="col">Desconto</th>
                    <th scope="col">Subtotal</th>

                    @if(auth()->user() && auth()->user()->permitions != 2)
                        <th scope="col">Ações</th>
                    @endif

                </tr>
            </thead>


            <tbody>

                @foreach($itens as $item)

                    @php
                        $valorUnitario = (float) ($item->valor_unitario ?? 0);
                        $desconto = (float) ($item->desconto ?? 0);
                        $quantidade = (int) ($item->quantidade ?? 0);

                        $subtotalItem = max(
                            0,
                            ($quantidade * $valorUnitario) - $desconto
                        );
                    @endphp

                    <tr>

                        {{-- ID --}}
                        <th scope="row">
                            {{ $item->id }}
                        </th>


                        {{-- TIPO --}}
                        <td>

                            @if(str_contains($item->itemable_type, 'Produto'))

                                <span class="badge bg-primary">
                                    Produto
                                </span>

                            @else

                                <span class="badge bg-info text-dark">
                                    O.S.
                                </span>

                            @endif

                        </td>


                        {{-- DESCRIÇÃO --}}
                        <td>
                            {{ $item->descricao
                                ?? $item->itemable?->nome
                                ?? $item->itemable?->descricao
                                ?? 'Item sem nome'
                            }}
                        </td>


                        {{-- QUANTIDADE --}}
                        <td>
                            {{ $quantidade }}
                        </td>


                        {{-- VALOR UNITÁRIO --}}
                        <td>
                            R$ {{ number_format(
                                $valorUnitario,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>


                        {{-- DESCONTO --}}
                        <td>
                            R$ {{ number_format(
                                $desconto,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>


                        {{-- SUBTOTAL --}}
                        <td>
                            R$ {{ number_format(
                                $subtotalItem,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>


                        {{-- AÇÕES --}}
                        @if(auth()->user() && auth()->user()->permitions != 2)

                            <td>

                                <form
                                    action="{{ route('notasitem.destroy', $item->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Deseja realmente remover este item da nota?');"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        title="Remover item"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>

                                </form>

                            </td>

                        @endif

                    </tr>

                @endforeach

            </tbody>

        </table>


        {{-- ========================================================
             RESUMO FINANCEIRO
        ======================================================== --}}

        <div class="d-flex justify-content-end align-items-center gap-2 mt-3">

            <strong>
                Valor Total da Nota:
            </strong>

            <input
                type="text"
                class="form-control w-auto text-end fw-bold"
                value="R$ {{ number_format($valorTotal, 2, ',', '.') }}"
                readonly
                disabled
            >

        </div>

    @endif

</div>

@endsection
