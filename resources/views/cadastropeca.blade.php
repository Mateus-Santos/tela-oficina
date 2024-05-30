@extends('layout')

@section('content')
  <section class="container cadastro">
  
    <h1>CADASTRO DE PEÇAS</h1>

    <form action="{{ route('pecas.store') }}" method="post" class="row g-3">
    @csrf
        <div class="campos">
            <div class="row">
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

            <div class="row">
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
                  <label class="form-label" for="produto">Produtos:*</label>
                  <input type="text" class="form-control" id="produto" name="produto" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="vulvula">Vulvula:*</label>
                  <input type="text" class="form-control" id="vulvula" name="vulvula" required>
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