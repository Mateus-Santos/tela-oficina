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

    <form action="{{ route('manutencoes.store') }}" method="POST">
      @csrf

      <div class="campos">
        <h1 class="mt-4">CADASTRAR MANUTENÇÃO</h1>
        <div class="row mb-3 mt-4">
          <div class="col-md-6">
            <label class="form-label" for="setor">Setor Defeituoso:*</label>
            <input type="text" class="form-control" id="setor" name="setor" placeholder="Informe o setor que está com problemas" maxlength="150" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="descricao">Descrição:*</label>
            <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Descrição do diagnostico" maxlength="250" required disabled>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="nivel">Nível:*</label>
            <select class="form-control" id="nivel" name="nivel" required>
              <option value="Crítico" selected>Crítico</option>
              <option value="Ativo">Alto</option>
              <option value="Baixo">Baixo</option>
              <option value="Alerta">Alerta</option>
            </select>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="valor">Valor (R$):*</label>
            <input type="text" class="form-control" id="valor" name="valor" value="" placeholder="Valor da peça" required disabled>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4">
            <label class="form-label" for="id_contrato_servico">Contrato:*</label>
              <select class="form-control" id="id_contrato_servico" name="id_contrato_servico" required>
                <option selected>Escolha o veículo...</option>
                @foreach($contratos as $contrato)
                <option value="{{$contrato->id}}">{{$contrato->descricao}}</option>
                @endforeach
              </select>
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