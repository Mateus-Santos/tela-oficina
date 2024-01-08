@extends('layout')

@section('content')

<footer>
  <div class="container cadastro">

  <h1>CADASTRO DE PESSOA FÍSICA</h1>
  <form>
      <div class="campos">
      <div class="form-group">
        <label for="nomecompleto">Nome Completo:</label>
        <input type="text" class="form-control" id="nomecompleto" placeholder="Digite seu Nome Completo.">
      </div>

      <div class="form-row">

        <div class="form-group col-md-6">
          <label for="cpf">CPF:</label>
          <input type="text" class="form-control" id="cpf" placeholder="Digite seu CPF.">
        </div>

        <div class="form-group col-md-6">
          <label for="rg">RG:</label>
          <input type="text" class="form-control" id="rg" placeholder="Digite o seu RG.">
        </div>

      </div>

      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" class="form-control" id="email" placeholder="Digite o seu Email.">
      </div>

      <div class="form-row">
      <div class="form-group col-md-4">
        <label for="cep">CEP:</label>
        <input type="text" class="form-control" id="cep">
      </div>

      <div class="form-group col-md-6">
        <label for="cidade">Cidade:</label>
        <input type="text" class="form-control" id="cidade">
      </div>

      <div class="form-group col-md-2">
        <label for="estado">Estado:</label>
        <select id="estado" class="form-control">
          <option selected>Escolher...</option>
          <option>BA</option>
        </select>
      </div>

      <div class="form-group col-md-6">
        <label for="rua">Rua:</label>
        <input type="text" class="form-control" id="rua">
      </div>

      <div class="form-group col-md-2">
        <label for="rua">Número:</label>
        <input type="text" class="form-control" id="rua">
      </div>

    </div>
    
    <div class="form-group">
      <label for="inputAddress2">Endereço:</label>
      <input type="text" class="form-control" id="Endereço" placeholder="Apartamento, hotel, casa, etc.">
    </div>

    <div class="form-group">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="confirmacao">
        <label class="form-check-label" for="confirmacao">
          Confirmo que toda as informações adicionadas são verdadeiras.
        </label>
      </div>
    </div>
  </div>
  <button type="submit" class="btn btn-success">Entrar</button>
</form>
</div>
@endsection