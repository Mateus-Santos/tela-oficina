@extends('layouts.layout')

@section('content')
<main class="cadastro profile">
  <div class="container edit-profile ">

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="/perfil/{{auth()->user()->id}}/update" method="POST">
      @csrf
      @method('PUT')

      <div class="campos">
        <h1 class="mb-2">EDITAR CONTA</h1>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="name">Nome Completo:*</label>
            <input type="text" class="form-control" id="name" name="name" value="{{auth()->user()->name}}" placeholder="Digite seu nome completo" maxlength="100" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" name="email" value="{{auth()->user()->email}}" placeholder="email@dominio.com" maxlength="150" required>
          </div>
        </div>

        <div class="edit-profile">
          <div class="input-info">
            <div>
              <label class="form-label" for="telefone_1">Telefone Principal:*</label>
              <input type="text" class="form-control" id="telefone_1" name="telefone_1" value="{{auth()->user()->telefone_1}}" required>
            </div>
            <div>
              <label class="form-label" for="telefone_2">Telefone Secundário:</label>
              <input type="text" class="form-control" id="telefone_2" name="telefone_2" value="{{auth()->user()->telefone_2}}">
            </div>
            <div>
              <label class="form-label" for="cpf">CPF:*</label>
              <input type="text" class="form-control" id="cpf" name="cpf" value="{{auth()->user()->cpf}}" required>
            </div>
            <div>
              <label class="form-label" for="rg">RG:*</label>
              <input type="text" class="form-control" id="rg" name="rg" value="{{auth()->user()->rg}}" required>
            </div>
            <div>
              <label class="form-label" for="data_nascimento">Data de Nascimento:*</label>
              <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="{{auth()->user()->data_nascimento}}" required>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col">
            <input type="checkbox" class="form-check-input" id="confirmation" required>
            <label for="confirmation" class="form-check-label">
              Confirmo que as informações fornecidas são verdadeiras.
            </label>
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>

  </div>
</main>
@endsection
