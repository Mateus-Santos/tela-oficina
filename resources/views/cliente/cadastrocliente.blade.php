@extends('layouts.layout')

@section('content')

  <section class="container cadastro">
  
  <h1>CADASTRO DE CLIENTE</h1>

  <form action="{{ route('clientes.store') }}" method="post" class="row g-3">
  @csrf
    <div class="campos">
        <div class="row md-3">

            <div class="col-md-3">
                <label class="form-label" for="id">Pessoa:</label>
                <select id="id" class="form-control" name="id" required>
                <option selected>Escolher...</option>
                @foreach($users as $user)
                <option value="{{$user->id}}">{{ $user->name }}</option>
                @endforeach
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                <input class="form-check-input" type="checkbox" id="confirmacao">
                <label for="confirmacao" name="confirmacao" required>
                Confirmo que toda as informações adicionadas são verdadeiras.
                </label>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col text-center">
                <button type="submit" class="btn btn-success">Cadastrar</button>
            </div>
        </div>
        
    </div>
</form>
</section>
@endsection