@extends('layouts.layout')

@vite(['resources/js/searchAddresses.js', 'resources/js/validateForm.js'])

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

    <form action="" method="POST">
      @csrf
      @method('PATCH')

      <div class="campos">
        <h1 class="mb-2">EDITAR CONTA</h1>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="name">Nome Completo:*</label>
            <input type="text" class="form-control" id="name" name="name" value="" placeholder="Digite seu nome completo" maxlength="100" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" name="email" value="" placeholder="email@dominio.com" maxlength="150" required disabled>
          </div>
        </div>

        <div class="edit-profile">
          <div class="input-info">
            <div>
              <label class="form-label" for="telefone_1">Telefone Principal:*</label>
              <input type="text" class="form-control" id="telefone_1" name="telefone_1" value="" placeholder="" required disabled>
            </div>
            <div>
              <label class="form-label" for="telefone_2">Telefone Secundário:</label>
              <input type="text" class="form-control" id="telefone_2" name="telefone_2" value="" placeholder="" disabled>
            </div>
            <div>
              <label class="form-label" for="cpf">CPF:*</label>
              <input type="text" class="form-control" id="cpf" name="cpf" value="" placeholder="" required disabled>
            </div>
            <div>
              <label class="form-label" for="rg">RG:*</label>
              <input type="text" class="form-control" id="rg" name="rg" value="" placeholder="" required disabled>
            </div>
            <div>
              <label class="form-label" for="data_nascimento">Data de Nascimento:*</label>
              <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="" required disabled>
            </div>
          </div>
        </div>


        <h1 class="mt-5 mb-2">EDITAR ENDEREÇO</h1>

        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label" for="cep">CEP:*</label>
            <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000" maxlength="10" required onblur="searchAddresses()" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="rua">Rua:*</label>
            <input type="text" class="form-control" id="rua" name="rua" placeholder="Digite o nome da rua" maxlength="150" required disabled>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="numero">Número:*</label>
            <input type="text" class="form-control" id="numero" name="numero" placeholder="Número da residência" maxlength="150" required disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="bairro">Bairro:*</label>
            <input type="text" class="form-control" id="bairro" name="bairro" placeholder="Bairro" maxlength="100" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cidade">Cidade:*</label>
            <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Cidade" maxlength="100" required disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="estado">Estado:*</label>
            <input type="text" class="form-control" id="estado" name="estado" placeholder="Estado" maxlength="50" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ponto_referencia">Ponto de Referência:</label>
            <input type="text" class="form-control" id="ponto_referencia" name="ponto_referencia" placeholder="Ponto de referência ou complemento" maxlength="150" disabled>
          </div>
        </div>


        <div class="row mb-3">
          <div class="col">
            <input type="checkbox" class="form-check-input" id="confirmation" required disabled>
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