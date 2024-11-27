@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<main id="main">
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
        <h1>MANUTENÇÃO DE PEÇAS</h1>

        <div class="row mb-3 mt-4">
          <div class="col-md-6">
            <label class="form-label" for="peca">Peça:*</label>
            <input type="text" class="form-control" id="peca" name="peca" value="" placeholder="Digite o nome da peça" maxlength="150" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="descricao">Descrição:*</label>
            <input type="text" class="form-control" id="descricao" name="descricao" value="" placeholder="Descrição da peça" maxlength="250" required disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="valor">Valor (R$):*</label>
            <input type="text" class="form-control" id="valor" name="valor" value="" placeholder="Valor da peça" required disabled>
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
