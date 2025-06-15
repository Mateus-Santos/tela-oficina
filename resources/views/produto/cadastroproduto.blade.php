@extends('layouts.layout')

@section('content')
  <section class="container cadastro">

    <h1><i class="bi bi-gear"></i> CADASTRO DE PRODUTOS</h1>

    {{-- Exibe erros de validação --}}
    @if ($errors->any())
      <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Exibe mensagem de sucesso --}}
    @if (session('success'))
      <div class="alert alert-success">
        {{ session('success') }}
      </div>
    @endif

    <form id="form-produto"
          action="{{ route('produtos.store') }}"
          enctype="multipart/form-data"
          method="POST"
          class="row g-3">
      @csrf
      <div class="campos">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label" for="codigo_barras">Código de Barras:</label>
            <input type="text"
                    class="form-control"
                    id="codigo_barras"
                    name="codigo_barras">
          </div>
        </div>
        
        {{-- Linha 1 --}}
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label" for="nome">Nome:*</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>

          <div class="col-md-2">
            <label class="form-label" for="ano_modelo">Ano Modelo:*</label>
            <input type="number" class="form-control" id="ano_modelo" name="ano_modelo" required>
          </div>

          <div class="col-md-3">
            <label class="form-label" for="preco_uni">Preço Unitário (R$):*</label>
            <input type="number" step="0.01" class="form-control" id="preco_uni" name="preco_uni" required>
          </div>

          <div class="col-md-2">
            <label class="form-label" for="quantidade">Quantidade:*</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" required>
          </div>

        </div>

        {{-- Linha 2 --}}
        <div class="row mb-3">
          <div class="col-md-2">
            <label class="form-label" for="motor">Motor:</label>
            <input type="text" class="form-control" id="motor" name="motor">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="codigo_fabricante">Cod. do Fabricante:*</label>
            <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="img">Imagem:</label>
            <input type="file" class="form-control" id="img" name="img">
          </div>
        </div>
        
        {{-- Linha 3 --}}
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Veículo(s):*</label>
            <div class="tags-input" data-name="veiculos[]">
              <input class="form-control" type="text" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Marca(s):*</label>
            <div class="tags-input" data-name="marcas[]">
              <input class="form-control" type="text" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Departamento(s):*</label>
            <div class="tags-input" data-name="departamentos[]">
              <input class="form-control" type="text" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Válvula(s):</label>
            <div class="tags-input" data-name="valvula[]">
              <input class="form-control" type="text" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Montadora(s):*</label>
            <div class="tags-input" data-name="montadora[]">
              <input class="form-control" type="text" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>
            
          </div>
          <div class="row mb-3">
            <div>
              <label class="form-label" for="descricao">Descrição:</label>
              <textarea type="text" class="form-control" id="descricao" name="descricao"></textarea>
            </div>
          </div>
        </div>
        {{-- Botão --}}
        <div class="col text-center">
          <button type="submit" class="btn btn-success">Cadastrar</button>
        </div>
      </div>
    </form>
  </section>
@endsection

@section('scripts')
    @vite(['resources/js/cadProduto.js'])
@endsection
