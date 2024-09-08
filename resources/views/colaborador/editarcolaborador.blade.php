@extends('layouts.layout')

@section('content')

  <section class="container cadastro">
  
  <h1>CADASTRO DE COLABORADOR</h1>

  <form action="/colaboradors/update/{{$colaborador->id_colaborador}}" method="post" class="row g-3">
  @csrf
  @method('PUT')
      <div class="campos">
      <div class="row">

        <div class="col-md-2">
          <label class="form-label" for="chave_pix">Chave Pix:*</label>
          <input type="text" class="form-control" id="chave_pix" placeholder="Digite seu Nome Completo." value="{{$colaborador->chave_pix}}" name="chave_pix" required>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="conta_banco">Conta banco:*</label>
          <input type="text" class="form-control" id="conta_banco" placeholder="Digite seu Nome Completo." value="{{$colaborador->conta_banco}}" name="conta_banco" required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="id_user">Usuário da Pessoa:*</label>
           <select class="form-select" name="id_user" aria-label="Default select example">
           @foreach($colaborador_pessoa_users as $colaborador_pessoa_user)
                <option name="id_user" value="{{$colaborador_pessoa_user->user->id}}">{{$colaborador_pessoa_user->user->email}}</option>
            @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="id_pessoa">Usuário da Pessoa:*</label>
           <select class="form-select" name="id_pessoa" aria-label="Default select example">
           @foreach($colaborador_pessoa_users as $colaborador_pessoa_user)
                <option name="id_pessoa" value="{{$colaborador_pessoa_user->pessoa->id_pessoa}}">{{$colaborador_pessoa_user->pessoa->nome}}</option>
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