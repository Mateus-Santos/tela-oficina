@extends('layout')

@section('content')

  <div class="container cadastro">

  <h1>CADASTRO DE PESSOA FÍSICA</h1>

  <form class="row g-3">
      <div class="campos">
      <div class="row mb-3">

        <div class="col-md-6">
        <label class="form-label" for="nomecompleto">Nome Completo:</label>
        <input type="text" class="form-control" id="nomecompleto" placeholder="Digite seu Nome Completo.">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control" id="email" placeholder="Digite o seu Email.">
        </div>
        
      </div>

      <div class="row mb-3">

          <div class="col-md-3"> 
            <label class="form-label" for="rg">RG:</label>
            <input type="text" class="form-control" id="rg" placeholder="0123456-7">
          </div>

          <div class="col-md-2">          
            <label class="form-label" for="cpf">CPF:</label>
            <input type="text" class="form-control" id="cpf" placeholder="999.999.999-99">
          </div>
        
        <div class="col-md-3">
          <label class="form-label" for="tell1">Telefone Principal:</label>
          <input type="text" class="form-control" id="tell1" placeholder="(99) 9 9999-9999">
        </div>

        <div class="col-md-3">
          <label class="form-label" for="tell2">Telefone Secundario:</label>
          <input type="text" class="form-control" id="tell2" placeholder="(99) 9 9999-9999">
        </div>

      </div>
      <div class="row mb-3">
        <div class="col-2">
          <label class="form-label" for="cep">CEP:</label>
          <input type="text" class="form-control" id="cep" placeholder="00000-000">
        </div>

      <div class="col">
        <label class="form-label" for="cidade">Cidade:</label>
        <input type="text" class="form-control" id="cidade" placeholder="Exemplo: Belmonte, Serraville, Tranquilópolis, Rio Verde...">
      </div>

      <div class="col-md-2">
        <label class="form-label" for="estado">Estado:</label>
        <select id="estado" class="form-control">
          <option selected>Escolher...</option>
          <option>BA</option>
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label" for="rua">Rua:</label>
        <input type="text" class="form-control" id="rua">
      </div>

      </div>

      <div class="row mb-3">
        <div class="col-md-5">
          <label class="form-label" for="endereco">Endereço:</label>
          <input type="text" class="form-control" id="endereco" placeholder="Apartamento, hotel, casa, etc.">
        </div>
        
        <div class="col-md-1">
          <label class="form-label" for="numero">Número:</label>
          <input type="text" class="form-control" id="numero">
        </div>
    </div>
    
      <div class="row mb-3">
        <div class="col">
          <label class="form-label" for="pont_ref">Ponto de referência:</label>
          <input type="text" class="form-control" id="pont_ref" placeholder="Exemplo: Próximo ao restaurante, loja, igreja...">
        </div>
      </div>

    <div class="row mb-3">
      <div class="col">
        <input class="form-check-input" type="checkbox" id="confirmacao">
        <label for="confirmacao">
          Confirmo que toda as informações adicionadas são verdadeiras.
        </label>
      </div>
    </div>

    <div class="col text-center">
        <button type="submit" class="btn btn-success">Cadastrar</button>
    </div>

  </div>
</form>
</div>
@endsection