@extends('layouts.layout')

@section('content')

<div class="container cadastro">

  <h1>DETALHES DA PESSOA FÍSICA</h1>

    <div class="campos">
      <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label" for="nome">Nome Completo:*</label>
            <input type="text" class="form-control" id="nome" value="{{ $user->name }}" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">E-mail:*</label>
            <input type="email" class="form-control" id="email" value="{{ $user->email }}" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label" for="rg">RG:</label>
            <input type="text" class="form-control" id="rg" value="{{ $user->pessoa?->rg }}" disabled>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="cpf">CPF:*</label>
            <input type="text" class="form-control" id="cpf" value="{{ $user->pessoa?->cpf }}" disabled>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="telefone_1">Telefone Principal:*</label>
          <input type="text" class="form-control" id="telefone_1" value="{{ $user->pessoa?->telefone_1 }}" disabled>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="telefone_2">Telefone Secundário:</label>
          <input type="text" class="form-control" id="telefone_2" value="{{ $user->pessoa?->telefone_2 }}" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label" for="data_nascimento">Data Nascimento:*</label>
            <input type="date" class="form-control" id="data_nascimento" value="{{ $user->pessoa?->data_nascimento }}" disabled>
        </div>
      </div>
    </div>

    <hr class="my-4">

    @if($enderecos->isEmpty())
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>Usuário não possui endereços cadastrados.</span>
            <a class="btn btn-success" href="/endereco/create/{{ $user->id }}">Cadastrar Novo Endereço</a>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>ENDEREÇOS CADASTRADOS</h3>
            <a class="btn btn-success" href="/endereco/create/{{ $user->id }}">
                <i class="bi bi-plus-circle"></i> Cadastrar Novo Endereço
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">CEP</th>
                        <th scope="col">Cidade</th>
                        <th scope="col">Bairro</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Rua</th>
                        <th scope="col">Número</th>
                        <th scope="col">Ponto Referência</th>
                        <th scope="col">Editar</th>
                        <th scope="col">Excluir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enderecos as $endereco)
                    <tr>
                        <th scope="row">{{ $endereco->id ?? $endereco->id_endereco }}</th>
                        <td>{{ $endereco->cep }}</td>
                        <td>{{ $endereco->cidade }}</td>
                        <td>{{ $endereco->bairro }}</td>
                        <td>{{ $endereco->estado }}</td>
                        <td>{{ $endereco->rua }}</td>
                        <td>{{ $endereco->numero }}</td>
                        <td>{{ $endereco->ponto_referencia ?? '-' }}</td>
                        <td>
                            <a href="/enderecos/{{ $endereco->id ?? $endereco->id_endereco }}/edit" class="btn btn-info btn-sm">
                                <i class="bi bi-pencil-square"></i> Editar
                            </a>
                        </td>
                        <td>
                            <form action="/enderecos/{{ $endereco->id ?? $endereco->id_endereco }}" method  ="post" onsubmit="return confirm('Tem certeza que deseja excluir este endereço?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i> Deletar
                                </button>
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
