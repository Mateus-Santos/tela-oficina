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

    <form action="{{ route('contratoservico.store') }}" method="POST">
      @csrf

      <div class="container cadastro">
        <h1 class="mt-4">Cadastro Histórico de Serviço</h1>
        <div class="row mb-2 mt-2">

          <div class="col-md-3">
            <label class="form-label" for="status">Status:*</label>
              <select class="form-control" id="status" name="status" required>
                <option value="Em aberto" selected>Em Aberto</option>
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
              <label class="form-label" for="id_veiculo">Veículo:*</label>
                <select class="form-control" id="id_veiculo" name="id_veiculo" required>
                  <option selected>Escolha o veículo...</option>
                  @foreach($veiculos as $veiculo)
                  <option value="{{$veiculo->id_veiculo}}">{{$veiculo->placa}} pertence à {{$veiculo->user->name}}</option>
                  @endforeach
                </select>
            </div>
          </div>

            <div class="col-md-6">
              <label class="form-label" for="descricao">Descrição:*</label>
              <input type="text" class="form-control" id="descricao" name="descricao" maxlength="250" required disabled>
            </div>
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-primary">Salvar Peça</button>
        </div>
      </div>
    </form>

  </div>
</main>
@endsection
