@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="container cadastro">
<h1>CADASTRAR O.S ITEM</h1>

<form action="{{ route('ordemservicoitem.store') }}" method="POST">
@csrf

<div class="row mb-3">

    {{-- Placa --}}
    <div class="col-md-4">
        <label class="form-label">Placa veículo:*</label>
        <input type="text"
               class="form-control"
               id="placa_input"
               placeholder="Digite a placa"
               required>

        <input type="hidden"
               name="veiculo_cliente_id"
               id="veiculo_cliente_id">
    </div>

    {{-- Cliente --}}
    <div class="col-md-4">
        <label class="form-label">Cliente</label>
        <input type="text"
               id="cliente_nome"
               class="form-control"
               readonly>
    </div>

    {{-- Ordens de Serviço --}}
    <div class="col-md-4">
        <label class="form-label">Ordens de Serviço*</label>
        <select name="ordem_servico_id"
                id="ordem_servico_select"
                class="form-control"
                required>
            <option value="">Digite a placa primeiro</option>
        </select>
    </div>

</div>

<div class="row mb-3 mt-4">
    <div class="col-md-6">
        <label class="form-label">Descrição*</label>
        <input type="text"
               class="form-control"
               name="descricao"
               maxlength="250"
               required>
    </div>
</div>

<div class="col text-center">
    <button type="submit" class="btn btn-success">
        Cadastrar Nota
    </button>
</div>

</form>
</div>
@endsection

@section('scripts')
@vite(['resources/js/cadOsItem.js'])
@endsection
