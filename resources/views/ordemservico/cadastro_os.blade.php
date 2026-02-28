@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<main class="manutencao">
  <div class="container edit-profile">

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ route('ordemservicos.store') }}" method="POST">
      @csrf

      <div class="campos">
        <h1 class="mt-4">CADASTRAR ORDEM SERVIÇO</h1>
        
        <div class="row">
          <div class="col-md-4">
              <label class="form-label">Cliente:</label>
              <input type="text" class="form-control" id="cliente_nome" readonly>
          </div>
          <div class="col-md-4">
            <div class="col-md-4">
                <label class="form-label">Placa do Veículo:*</label>
                <input type="text" class="form-control" id="placa_input" required>
                <input type="hidden" name="veiculo_cliente_id" id="veiculo_cliente_id">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="setor_servico_id">Setor Serviço:*</label>
              <select class="form-control" id="setor_servico_id" name="setor_servico_id" required>
                <option selected>Escolha o veículo...</option>
                @foreach($setorservicos as $setorservico)
                <option value="{{$setorservico->id}}">{{$setorservico->setor}}</option>
                @endforeach
              </select>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4">
            <label class="form-label" for="setor_servico_id">Setor Serviço:*</label>
              <select class="form-control" id="setor_servico_id" name="setor_servico_id" required>
                <option selected>Escolha o veículo...</option>
                @foreach($setorservicos as $setorservico)
                <option value="{{$setorservico->id}}">{{$setorservico->setor}}</option>
                @endforeach
              </select>
          </div>
        </div>

        <div class="row mb-3 mt-4">
          <div class="col-md-6">
            <label class="form-label" for="descricao">Descrição:*</label>
            <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Descrição do diagnostico" maxlength="250" required disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="valor">Valor (R$):*</label>
            <input type="text" class="form-control" id="valor" name="valor" value="" placeholder="Valor da peça" required disabled>
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-success">Cadastrar OS</button>
        </div>
      </div>
    </form>

  </div>
</main>
@endsection

@section('scripts')
@vite(['resources/js/cadOrdemservico.js'])
@endsection