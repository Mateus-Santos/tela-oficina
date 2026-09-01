@extends('layouts.layout')

@section('content')

<section class="container cadastro">


<h1>
    <i class="bi bi-cash-stack"></i>
    EDITAR CONTA A RECEBER
</h1>

{{-- Erros --}}
@if ($errors->any())
    <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Sucesso --}}
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<form
    action="{{ route('contas-receber.update', $contaReceber) }}"
    method="POST"
    class="row g-3"
>
    @csrf
    @method('PUT')

    <div class="campos">

        {{-- Cliente / Nota --}}
        <div class="row mb-3">

            {{-- Cliente --}}
            <div class="col-md-5">
                <label class="form-label" for="cliente_id">
                    Cliente:
                </label>

                <select
                    class="form-control"
                    id="cliente_id"
                    name="cliente_id"
                >
                    <option value="">
                        Selecione um cliente
                    </option>

                    @foreach ($clientes as $cliente)
                        <option
                            value="{{ $cliente->id }}"
                            {{ old('cliente_id', $contaReceber->cliente_id) == $cliente->id ? 'selected' : '' }}
                        >
                            {{ $cliente->pessoa?->nome ?? 'Sem nome' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Nota --}}
            <div class="col-md-4">
                <label class="form-label" for="nota_id">
                    Nota:
                </label>

                <select
                    class="form-control"
                    id="nota_id"
                    name="nota_id"
                >
                    <option value="">
                        Sem nota vinculada
                    </option>

                    @foreach ($notas as $nota)
                        <option
                            value="{{ $nota->id }}"
                            data-cliente-id="{{ $nota->cliente_id }}"
                            {{ old('nota_id', $contaReceber->nota_id) == $nota->id ? 'selected' : '' }}
                        >
                            #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}
                            -
                            {{ $nota->cliente?->pessoa?->nome ?? 'Sem cliente' }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- Categoria / Descrição --}}
        <div class="row mb-3">

            {{-- Categoria --}}
            <div class="col-md-4">
                <label class="form-label" for="categoria_financeira_id">
                    Categoria Financeira:*
                </label>

                <select
                    class="form-control"
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
                            {{ old('categoria_financeira_id', $contaReceber->categoria_financeira_id) == $categoria->id ? 'selected' : '' }}
                        >
                            {{ $categoria->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Descrição --}}
            <div class="col-md-5">
                <label class="form-label" for="descricao">
                    Descrição:*
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="descricao"
                    name="descricao"
                    value="{{ old('descricao', $contaReceber->descricao) }}"
                    maxlength="255"
                    required
                >
            </div>

        </div>

        {{-- Valores --}}
        <div class="row mb-3">

            {{-- Valor original --}}
            <div class="col-md-3">
                <label class="form-label" for="valor_original">
                    Valor Original (R$):*
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="form-control"
                    id="valor_original"
                    name="valor_original"
                    value="{{ old('valor_original', $contaReceber->valor_original) }}"
                    required
                >
            </div>

            {{-- Desconto --}}
            <div class="col-md-2">
                <label class="form-label" for="desconto">
                    Desconto (R$):
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    id="desconto"
                    name="desconto"
                    value="{{ old('desconto', $contaReceber->desconto) }}"
                >
            </div>

            {{-- Juros --}}
            <div class="col-md-2">
                <label class="form-label" for="juros">
                    Juros (R$):
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    id="juros"
                    name="juros"
                    value="{{ old('juros', $contaReceber->juros) }}"
                >
            </div>

            {{-- Multa --}}
            <div class="col-md-2">
                <label class="form-label" for="multa">
                    Multa (R$):
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    id="multa"
                    name="multa"
                    value="{{ old('multa', $contaReceber->multa) }}"
                >
            </div>

        </div>

        {{-- Datas --}}
        <div class="row mb-3">

            {{-- Data de emissão --}}
            <div class="col-md-3">
                <label class="form-label" for="data_emissao">
                    Data de Emissão:
                </label>

                <input
                    type="date"
                    class="form-control"
                    id="data_emissao"
                    name="data_emissao"
                    value="{{ old('data_emissao', optional($contaReceber->data_emissao)->format('Y-m-d')) }}"
                >
            </div>

            {{-- Data de vencimento --}}
            <div class="col-md-3">
                <label class="form-label" for="data_vencimento">
                    Data de Vencimento:*
                </label>

                <input
                    type="date"
                    class="form-control"
                    id="data_vencimento"
                    name="data_vencimento"
                    value="{{ old('data_vencimento', optional($contaReceber->data_vencimento)->format('Y-m-d')) }}"
                    required
                >
            </div>

        </div>

        {{-- Observações --}}
        <div class="row mb-3">

            <div class="col-12">
                <label class="form-label" for="observacoes">
                    Observações:
                </label>

                <textarea
                    class="form-control"
                    id="observacoes"
                    name="observacoes"
                    rows="4"
                >{{ old('observacoes', $contaReceber->observacoes) }}</textarea>
            </div>

        </div>

    </div>

    {{-- Botões --}}
    <div class="col text-center">

        <a
            href="{{ route('contas-receber.show', $contaReceber) }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        <button
            type="submit"
            class="btn btn-success"
        >
            <i class="bi bi-check-circle"></i>
            Salvar Alterações
        </button>

    </div>

</form>


</section>

@endsection
