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

    
<section class="container cadastro">
    <form action="{{ route('veiculos.store') }}" method="POST">
      @csrf
      <div class="campos">
        <h1 class="mb-2">CADASTRO DE VEÍCULOS</h1>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="placa">Placa:*</label>
            <input type="text" class="form-control" id="placa" value="{{$veiculo->placa}}" name="placa" placeholder="Digite a placa do veículo" maxlength="8" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ano">Ano:*</label>
            <input type="number" class="form-control" id="ano" value="{{$veiculo->ano}}" name="ano" placeholder="Ano do veículo (ex.: 2022)" min="1900" max="{{ date('Y') }}" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="marca">Marca:*</label>
            <input type="text" class="form-control" value="{{$veiculo->marca}}" id="marca" name="marca" placeholder="Digite a marca do veículo" maxlength="50" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cor">Cor:*</label>
            <input type="text" class="form-control" value="{{$veiculo->cor}}" id="cor" name="cor" placeholder="Digite a cor do veículo" maxlength="30" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="id_user">Usuário:*</label>
            <select class="form-control" id="id_user" name="id_user" required>
            <option selected>Escolher...</option>
            @foreach($users as $user)
            <option value="{{$user->id}}">{{$user->name}}</option>
            @endforeach
            </select>
          </div>
        </div>

        <div class="col text-center mt-4">
          <button type="submit" class="btn btn-primary">Salvar Veículo</button>
        </div>
      </div>
    </form>
  </section>
  </div>
</main>
@endsection