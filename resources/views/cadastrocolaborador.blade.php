@extends('layout')

@section('content')

  <section class="container cadastro">
  
  <h1>CADASTRO DE COLABORADOR</h1>

  <form action="{{ route('colaboradors.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">
      <div class="row">

        <div class="col-md-4">
          <label class="form-label" for="id_user">Usuário:</label>
          <select id="id_user" class="form-control" name="id_user" required>
          <option selected>Escolher...</option>
          @foreach($users as $user)
          <option value="{{$user->id}}">{{ $user->email }}</option>
          @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="id_pessoa">Pessoa:</label>
          <select id="id_pessoa" class="form-control" name="id_pessoa" required>
          <option selected>Escolher...</option>
          @foreach($pessoas as $pessoa)
          <option value="{{$pessoa->id_pessoa}}">{{ $pessoa->nome }}</option>
          @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label" for="chave_pix">Chave Pix:*</label>
          <input type="text" class="form-control" id="chave_pix" placeholder="Digite seu Nome Completo." name="chave_pix" required>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="conta_banco">Conta banco:*</label>
          <input type="text" class="form-control" id="conta_banco" placeholder="Digite seu Nome Completo." name="conta_banco" required>
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