@extends('layout')

@section('content')

<div class="container cadastro">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">id</th>
                <th scope="col">Nome Completo</th>
                <th scope="col">Data de Nascimento</th>
                <th scope="col">E-mail</th>
                <th scope="col">CPF</th>
                <th scope="col">RG</th>
                <th scope="col">Telefone Principal</th>
                <th scope="col">Telefone Secundario</th>
            </tr>
    </thead>

    <tbody>
    <tr>
    @foreach($pessoas as $pessoa)
      <th scope="row">1</th>
      <td>{{ $pessoa->nome }}</td>
      <td>{{ $pessoa->data_nascimento }}</td>
      <td>{{ $pessoa->email }}</td>
      <td>{{ $pessoa->cpf }}</td>
      <td>{{ $pessoa->rg }}</td>
      <td>{{ $pessoa->telefone_1 }}</td>
      <td>{{ $pessoa->telefone_2 }}</td>
    @endforeach
  </tbody>
</table>
</div>

@endsection