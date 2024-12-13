@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<section class="container cadastro">
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

    <form action="{{ route('veiculos.store') }}" method="POST">
      @csrf
        <h1>CADASTRO DE VEÍCULOS</h1>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label" for="placa">Placa:*</label>
            <input type="text" class="form-control" id="placa" name="placa" placeholder="Digite a placa do veículo" maxlength="8" required>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="ano">Ano:*</label>
            <input type="number" class="form-control" id="ano" name="ano" placeholder="Ano do veículo (ex.: 2022)" min="1900" max="{{ date('Y') }}" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="marca">Marca:*</label>
            <input type="text" class="form-control" id="marca" name="marca" placeholder="Digite a marca do veículo" maxlength="50" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cor">Cor:*</label>
            <input type="text" class="form-control" id="cor" name="cor" placeholder="Digite a cor do veículo" maxlength="30" required>
          </div>
        </div>

        @if(auth()->user() && auth()->user()->permitions != 2)
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label" for="id_user">Usuário:*</label>
                <select class="form-control" id="id_user" name="id_user" required>
                <option selected>Escolher...</option>
                @foreach($users as $user)
                  @if($user->permitions == 2)
                  <option value="{{$user->id}}">{{$user->name}}</option>
                  @endif
                @endforeach
                </select>
              </div>
            </div>
        @else
            <div class="row mb">
                <div class="col-md-2">
                    <label class="form-label" for="id_user">Usuário:*</label>
                    <input class="form-control" type="text" name="id_user" id="id_user" value="{{auth()->user()->id}}" readonly>
                </div>
            </div>
        @endif

        <div class="col text-center mt-4">
          <button type="submit" class="btn btn-primary">Salvar Veículo</button>
        </div>
    </form>
  </section>
  </div>
</section>
@endsection
