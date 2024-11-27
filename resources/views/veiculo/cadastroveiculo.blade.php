@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<main class="veiculos">
  <div class="campos">

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="" method="POST">
      @csrf
        <h1 class="mb-2">CADASTRO DE VEÍCULOS</h1>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="placa">Placa:*</label>
            <input type="text" class="form-control" id="placa" name="placa" value="" placeholder="Digite a placa do veículo" maxlength="8" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ano">Ano:*</label>
            <input type="number" class="form-control" id="ano" name="ano" value="" placeholder="Ano do veículo (ex.: 2022)" min="1900" max="{{ date('Y') }}" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="marca">Marca:*</label>
            <input type="text" class="form-control" id="marca" name="marca" value="" placeholder="Digite a marca do veículo" maxlength="50" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cor">Cor:*</label>
            <input type="text" class="form-control" id="cor" name="cor" value="" placeholder="Digite a cor do veículo" maxlength="30" required>
          </div>
        </div>

        <div class="col text-center mt-4">
          <button type="submit" class="btn btn-primary">Salvar Veículo</button>
        </div>
    </form>

  </div>
</main>
@endsection
