@extends('layouts.layout')

@section('content')

<div class="container cadastro">
  
  <h1>DETALHES DA PESSOA FÍSICA</h1>
  
    <div class="campos">
      <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label" for="nome">Nome Completo:*</label>
            <input type="text" class="form-control" id="nome" value="{{ $user->name }}" name="nome" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" value="{{ $user->email }}" name="email" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-3"> 
            <label class="form-label" for="rg">RG:</label>
            <input type="text" class="form-control" id="rg" value="{{ $user->rg }}" name="rg" disabled>
        </div>

        <div class="col-md-2">          
            <label class="form-label" for="cpf">CPF:*</label>
            <input type="text" class="form-control" id="cpf" value="{{ $user->cpf }}" name="cpf" disabled>
        </div>
        
        <div class="col-md-3">
          <label class="form-label" for="telefone_1">Telefone Principal:*</label>
          <input type="text" class="form-control" id="telefone_1" value="{{ $user->telefone_1 }}" name="telefone_1" disabled>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="telefone_2">Telefone Secundario:*</label>
          <input type="text" class="form-control" id="telefone_2" value="{{ $user->telefone_2 }}" name="telefone_2" disabled>
        </div>
      </div>
    </div>

    <div class="row mb-3">
        <div class="col-2">
            <label class="form-label" for="data_nascimento">Data Nascimento:*</label>
            <input type="date" class="form-control" id="data_nascimento" value="{{ $user->data_nascimento }}" name="data_nascimento" disabled>
        </div>
    </div>
    @if($enderecos->isEmpty())
    <h1>Usuário não possui endereços cadastrados</h1>
    
    <a class="btn btn-success" href="/endereco/create/{{ $user->id }}">Cadastrar Novo endereço</a>
    @else
    <h1>ENDEREÇOS CADASTRADOS</h1>
    <div class="row mb-3">
    <a class="btn btn-success" href="/endereco/create/{{ $user->id }}">Cadastrar Novo endereço</a>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">CEP</th>
                    <th scope="col">Cidade</th>
                    <th scope="col">Bairro</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Rua</th>
                    <th scope="col">Número</th>
                    <th scope="col">Ponto Refêrencia</th>
                    <th scope="col">Editar</th>
                    <th scope="col">Excluir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enderecos as $endereco)
                <tr>
                <th scope="row">{{ $endereco->id_endereco }}</th>
                <td>{{ $endereco->cep }}</td>
                <td>{{ $endereco->cidade }}</td>
                <td>{{ $endereco->bairro }}</td>
                <td>{{ $endereco->estado }}</td>
                <td>{{ $endereco->rua }}</td>
                <td>{{ $endereco->numero }}</td>
                <td>{{ $endereco->ponto_referencia }}</td>
                <td><a href="/enderecos/edit/{{$endereco->id_endereco}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
                <td>
                    <form action="/enderecos/{{$endereco->id_endereco}}" method="post">
                        @csrf
                        @method('DELETE')
                        <button href="" class="btn btn-danger delete-btn"><i class="bi bi-trash3"></i>Deletar</button>
                    </form>
                </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection