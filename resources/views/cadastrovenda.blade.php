@extends('layout')

@section('content')

  <section class="container cadastro">
  
  <h1>CADASTRO DE CLIENTE</h1>

  <form action="{{ route('clientes.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">
        <div class="row">

          <div class="col-md-4">
            <label class="form-label" for="id_user">Cliente:</label>
            <select id="id_user" class="form-control" name="id_user" required>
            <option selected>Escolher...</option>
            @foreach($clientes as $cliente)
            <option value="{{$cliente->id_cliente}}">{{ $cliente->pessoa->nome }}</option>
            @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label" for="id_pessoa">Colaborador:</label>
            <select id="id_pessoa" class="form-control" name="id_pessoa" required>
            <option selected>Escolher...</option>
            @foreach($colaboradores as $colaborador)
            <option value="{{$colaborador->id_colaborador}}">{{ $colaborador->pessoa->nome }}</option>
            @endforeach
            </select>
          </div>

        </div>

      <div class="row mb-3">
        <div class="col">
          <input class="form-check-input" type="checkbox" id="confirmacao">
          <label for="confirmacao" name="cidade" required>
            Confirmo que toda as informações adicionadas são verdadeiras.
          </label>
        </div>
      </div>

      <div class="col text-center">
          <button type="submit" class="btn btn-success">Cadastrar</button>
      </div>

  </div>
</form>
</section>
@endsection