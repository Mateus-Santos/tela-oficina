@extends('layouts.layout')

@section('content')
  <section class="container cadastro">
  
    <h1>CADASTRO DE PEÇAS</h1>

    <form action="/pecas/update/{{$peca->id_peca}}" method="post" class="row g-3">
    @csrf
    @method('PUT')
        <div class="campos">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label" for="montadora">Montadora:*</label>
                <input type="text" class="form-control" id="montadora" name="montadora" value="{{ $peca->montadora }}" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="nome">Nome:*</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ $peca->nome }}" required>
              </div>
              <div class="col-md-1">
                <label class="form-label" for="ano">Ano:*</label>
                <input type="text" class="form-control" id="ano" name="ano" value="{{ $peca->ano }}" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="veiculo">Veículo:*</label>
                <input type="text" class="form-control" id="veiculos" name="veiculos" value="{{ $peca->veiculos }}" required>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                  <label class="form-label" for="quantidade">Quantidade em estoque:*</label>
                  <input type="text" class="form-control" id="quantidade" name="quantidade" value="{{ $peca->quantidade }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="marcas">Marcas:*</label>
                  <input type="text" class="form-control" id="marcas" name="marcas" value="{{ $peca->marcas }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="departamentos">Departamentos:*</label>
                  <input type="text" class="form-control" id="departamentos" name="departamentos" value="{{ $peca->departamentos }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="produtos">Produtos:*</label>
                  <input type="text" class="form-control" id="produtos" name="produtos" value="{{ $peca->produtos }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="vulvula">Vulvula:*</label>
                  <input type="text" class="form-control" id="vulvula" name="vulvula" value="{{ $peca->vulvula }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="motor">Motor:*</label>
                  <input type="text" class="form-control" id="motor" name="motor" value="{{ $peca->motor }}" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="descricao_peca">Descrição da peça:*</label>
                  <input type="text" class="form-control" id="descricao_peca" name="descricao_peca" value="{{ $peca->descricao_peca }}" required>
                </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <input class="form-check-input" type="checkbox" id="confirmacao">
                <label for="confirmacao" name="confirmacao" required>
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