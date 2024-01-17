@extends('layout')

@section('content')

  <div class="container cadastro">
  
  <h1>EDIÇÃO DE PESSOA FÍSICA</h1>
  @csrf
  <form action="{{ route('pessoas.update'), ['pessoa'] -> $pessoa->id_pessoa }}" class="row g-3">
      <div class="campos">
      <div class="row mb-3">

        <div class="col-md-6">
        <label class="form-label" for="nome">Nome Completo:*</label>
        <input type="text" class="form-control" id="nome" value="{{ $pessoa->nome }}" name="nome" required>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" value="{{ $pessoa->email }}" name="email" required>
        </div>
        
      </div>

      <div class="row mb-3">

          <div class="col-md-3"> 
            <label class="form-label" for="rg">RG:</label>
            <input type="text" class="form-control" id="rg" value="{{ $pessoa->rg }}" name="rg">
          </div>

          <div class="col-md-2">          
            <label class="form-label" for="cpf">CPF:*</label>
            <input type="text" class="form-control" id="cpf" value="{{ $pessoa->cpf }}" name="cpf" required>
          </div>
        
        <div class="col-md-3">
          <label class="form-label" for="telefone_1">Telefone Principal:*</label>
          <input type="text" class="form-control" id="telefone_1" value="{{ $pessoa->tel_1 }}" name="telefone_1" required>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="telefone_2">Telefone Secundario:*</label>
          <input type="text" class="form-control" id="telefone_2" value="{{ $pessoa->tel_2 }}" name="telefone_2" required>
        </div>

      </div>
      <div class="row mb-3">
        <div class="col-2">
          <label class="form-label" for="cep">CEP:*</label>
          <input type="text" class="form-control" id="cep"  name="cep" required>
        </div>

      <div class="col">
        <label class="form-label" for="cidade">Cidade:*</label>
        <input type="text" class="form-control" id="cidade" name="cidade" required>
      </div>

      <div class="col-md-2">
        <label class="form-label" for="estado">Estado:*</label>
        <select id="estado" class="form-control" name="cidade" required>
          <option selected>Escolher...</option>
          <option>BA</option>
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label" for="rua">Rua:*</label>
        <input type="text" class="form-control" id="rua" name="rua" required>
      </div>

      </div>

      <div class="row mb-3">
        <div class="col-md-5">
          <label class="form-label" for="endereco">Endereço:*</label>
          <input type="text" class="form-control" id="endereco" required>
        </div>
        
        <div class="col-md-1">
          <label class="form-label" for="numero">Número:*</label>
          <input type="text" class="form-control" id="numero" name="numero" required>
        </div>

        <div class="col">
          <label class="form-label" for="pont_ref">Ponto de referência:*</label>
          <input type="text" class="form-control" id="pont_ref" name="pont_ref" required>
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
</div>
@endsection