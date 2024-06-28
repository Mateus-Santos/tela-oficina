@extends('layout')

@section('content')

  <div class="container cadastro">
  
  <h1>CADASTRO DE PESSOA FÍSICA</h1>

  <form action="{{ route('pessoas.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">
      <div class="row mb-3">

        <div class="col-md-6">
        <label class="form-label" for="nome">Nome Completo:*</label>
        <input type="text" class="form-control" id="nome" placeholder="Digite seu Nome Completo." name="nome" required>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" placeholder="Digite o seu Email." name="email" required>
        </div>
        
      </div>

      <div class="row mb-3">

          <div class="col-md-3"> 
            <label class="form-label" for="rg">RG:</label>
            <input type="text" class="form-control" id="rg" placeholder="0123456-7" name="rg">
          </div>

          <div class="col-md-2">          
            <label class="form-label" for="cpf">CPF:*</label>
            <input maxlength="14" type="text" class="form-control" id="cpf" placeholder="999.999.999-99" name="cpf" required>
          </div>
        
        <div class="col-md-3">
          <label class="form-label" for="telefone_1">Telefone Principal:*</label>
          <input maxlength="16" type="text" class="form-control" id="telefone_1" placeholder="(99) 9 9999-9999" name="telefone_1" required>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="telefone_2">Telefone Secundario:*</label>
          <input maxlength="16" type="text" class="form-control" id="telefone_2" placeholder="(99) 9 9999-9999" name="telefone_2" required>
        </div>

      </div>

      <div class="row mb-3">
        <div class="col-2">
          <label class="form-label" for="data_nascimento">Data Nascimento:*</label>
          <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
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

    <div class="col text-center">
        <button type="submit" class="btn btn-success">Cadastrar</button>
    </div>

  </div>
</form>
</div>
@endsection