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

      <div class="container cadastro">
        <h1 class="mt-4">Cadastro Histórico de Serviço</h1>
        <div class="row mb-2 mt-2">

          <div class="col-md-3">
            <label class="form-label" for="status">Status:*</label>
              <select class="form-control" id="status" name="status" required>
                <option value="Em aberto" selected>Orçamento</option>
                <option value="Ativo">Ativo</option>
                <option value="Aguardando resposta">Aguardando Resposta</option>
                <option value="Concluido">Concluído</option>
                <option value="Cancelado">Cancelado</option>
              </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="data_abertura">Data de Abertura:*</label>
            <input type="date" class="form-control" id="data_abertura" name="data_abertura" required>
          </div>

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
          </div>

            <div class="col-md-6">
              <label class="form-label" for="descricao">Descrição:*</label>
              <input type="text" class="form-control" id="descricao" name="descricao" maxlength="250" required disabled>
            </div>
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-primary">Salvar!</button>
        </div>
      </div>
    </form>

  </div>
</main>
@endsection

@section('scripts')
    @vite(['resources/js/cadOrdemservico.js'])    
@endsection
