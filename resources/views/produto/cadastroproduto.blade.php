@extends('layouts.layout')

@section('content')
  <section class="container cadastro">
  
    <h1>CADASTRO DE PRODUTOS</h1>

    <form action="{{ route('produtos.store') }}" enctype="multipart/form-data" method="post" class="row g-3">
    @csrf
        <div class="campos">
            <div class="row md-3">
              <div class="col-md-3">
                <label class="form-label" for="montadora">Montadora:*</label>
                <input type="text" class="form-control" id="montadora" name="montadora" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="nome">Nome:*</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
              </div>
              <div class="col-md-1">
                <label class="form-label" for="ano">Ano:*</label>
                <input type="text" class="form-control" id="ano" name="ano" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="veiculo">Veículo:*</label>
                <input type="text" class="form-control" id="veiculo" name="veiculo" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3">
                  <label class="form-label" for="quantidade">Quantidade em estoque:*</label>
                  <input type="text" class="form-control" id="quantidade" name="quantidade" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="marcas">Marcas:*</label>
                  <input type="text" class="form-control" id="marcas" name="marcas" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="departamento">Departamentos:*</label>
                  <input type="text" class="form-control" id="departamento" name="departamento" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="valvula">Válvula:*</label>
                  <input type="text" class="form-control" id="valvula" name="valvula" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="motor">Motor:*</label>
                  <input type="text" class="form-control" id="motor" name="motor" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="descricao">Descrição do produto:*</label>
                  <input type="text" class="form-control" id="descricao" name="descricao" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="codigo_fabricante">Código Peça:*</label>
                  <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="preco_uni">Preço Unitario:*</label>
                  <input type="text" class="form-control" id="preco_uni" name="preco_uni" required>
                </div>
                <div>
                  <label class="form-label" for="img">Imagem:*</label>
                  <input type="file" class="form-control" id="img" name="img">
                </div>
            </div>

          <div class="col text-center">
              <button type="submit" class="btn btn-success">Cadastrar</button>
          </div>
        </div>
    </form>
  </section>
@endsection