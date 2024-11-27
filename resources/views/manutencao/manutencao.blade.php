@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<main class="manutencao">
  <div class="container edit-profile">

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <h1>MANUTENÇÃO DE PEÇAS</h1>

    <form action="" method="POST">
      @csrf

      <div class="campos">
        <h1 class="mt-4">CADASTRAR PEÇA</h1>

        <div class="row mb-3 mt-4">
          <div class="col-md-6">
            <label class="form-label" for="peca">Peça:*</label>
            <input type="text" class="form-control" id="peca" name="peca" value="" placeholder="Digite o nome da peça" maxlength="150" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="descricao">Descrição:*</label>
            <input type="text" class="form-control" id="descricao" name="descricao" value="" placeholder="Descrição da peça" maxlength="250" required disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="valor">Valor (R$):*</label>
            <input type="text" class="form-control" id="valor" name="valor" value="" placeholder="Valor da peça" required disabled>
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-primary">Salvar Peça</button>
        </div>
      </div>
    </form>

    <hr>

    <div class="listParts">
      <h1>LISTA DE PEÇAS CADASTRADAS</h1>
      <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Peça</th>
            <th scope="col">Descrição</th>
            <th scope="col">Valor (R$)</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          {{--@foreach ($pecas as $peca) --}}
          <td> peca->peca </td>
          <td> peca->descricao </td>
          <td>R$ number_format($peca->valor, 2, ',', '.') </td>
          <td>
            <a href="" class="btn btn-warning btn-sm">Editar</a>
            <form action="" method="POST" style="display: inline-block;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta peça?')">Excluir</button>
            </form>
          </td>
          </tr>
          {{-- @endforeach --}}
        </tbody>
      </table>
    </div>
  </div>
</main>
@endsection