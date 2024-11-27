@extends('layouts.layout')

@section('content')
<main class="veiculos">
  <div class="campos">

    <h1 class="mb-4">LISTAR VEÍCULOS</h1>

    @if (session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
    @endif

    {{-- @if ($veiculos->isEmpty()) --}}
    <div class="alert alert-warning">
      Não há veículos cadastrados.
    </div>
    {{-- @else --}}
    <table class="table table-striped">
      <thead>
        <tr>
          <th scope="col">Placa</th>
          <th scope="col">Ano</th>
          <th scope="col">Marca</th>
          <th scope="col">Cor</th>
          <th scope="col">Ações</th>
        </tr>
      </thead>
      <tbody>
        {{-- @foreach ($veiculos as $veiculo) --}}
        <tr>
          <td>{{-- $veiculo->placa --}}</td>
          <td>{{-- $veiculo->ano --}}</td>
          <td>{{-- $veiculo->marca --}}</td>
          <td>{{-- $veiculo->cor --}}</td>
          <td>
            <a href="{{-- route('veiculos.edit', $veiculo->id) --}}" class="btn btn-sm btn-warning">Editar</a>
            <form action="{{-- route('veiculos.destroy', $veiculo->id) --}}" method="POST" style="display:inline-block;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este veículo?')">Excluir</button>
            </form>
          </td>
        </tr>
        {{-- @endforeach --}}
      </tbody>
    </table>

    <div class="d-flex justify-content-center mt-4">
      {{-- $veiculos->links() --}}
    </div>
    {{-- @endif --}}

    <div class="mt-4">
      <a href="/veiculo/cadastrar"" class="btn btn-primary">Cadastrar Novo Veículo</a>
    </div>

  </div>
</main>
@endsection