@extends('layout')

@section('content')

  <section class="container cadastro">
  
  <h1>CADASTRO DE COMPRA</h1>

  <form action="{{ route('pecavendas.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">

        <div class="row mb-3">

          <div class="col-md-3">
            <label class="form-label" for="id_cliente">Cliente:</label>
            <select id="id_cliente" class="form-control" name="id_cliente" required>
            <option selected>Escolher...</option>
            @foreach($clientes as $cliente)
            <option value="{{$cliente->id_cliente}}">{{ $cliente->pessoa->nome }}</option>
            @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label" for="id_colaborador">Vendedor:</label>
            <select class="form-control" id="id_colaborador" name="id_colaborador"  required>
            <option selected>Escolher...</option>
            @foreach($colaboradores as $colaborador)
            <option value="{{$colaborador->id_colaborador}}">{{ $colaborador->pessoa->nome }}</option>
            @endforeach
            </select>
          </div>

        </div>

        <div class="row mb-3">

          <div class="col-2">
            <label class="form-label" for="data_venc">Data Vencimento:*</label>
            <input type="date" class="form-control" id="data_venc" name="data_venc" required>
          </div>

          <div class="col-2">
            <label class="form-label" for="data_venda">Data Venda:*</label>
            <input type="date" class="form-control" id="data_venda" name="data_venda" required>
          </div>

        </div>

        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label" for="id_peca">Peça vendida:</label>
            <select onchange="valor_unitario(this.value)" class="form-control" id="id_peca" name="id_peca" required>
            <option selected>Escolher...</option>
            @foreach($pecas as $peca)
            <option value="{{$peca}}">{{$peca->nome}}</option>
            @endforeach
            </select>
          </div>

          <div class="col-2">
            <label class="form-label" for="valor_uni">Valor Unitario:*</label>
            <input type="text" class="form-control" id="valor_uni" name="valor_uni" disabled>
          </div>

        </div>

        <div class="row mb-3">
  
          <div class="col-2">
            <label class="form-label" for="quantidade">Quantidade:*</label>
            <input type="text" class="form-control" id="quantidade" name="quantidade" required>
          </div>

          <div class="col-2">
            <label class="form-label" for="desconto">Desconto (%):*</label>
            <input type="text" class="form-control" id="desconto" name="desconto" required>
          </div>

          <div class="col-2">
            <label class="form-label" for="juros">Juros (%):*</label>
            <input type="text" class="form-control" id="juros" name="juros" required>
          </div>

          <div class="col-1">
            <label class="form-label" for="valor_total">Calcular:*</label>
            <a class="btn btn-secondary" onclick="pegar_valor()">Calcular</a>
          </div>

          <div class="col-3">
            <label class="form-label" for="valor_total">Valor total:*</label>
            <input type="text" class="form-control" id="valor_total" name="valor_total" readonly disabled>
          </div>

        </div>

        <div class="row mb-3">

          <div class="col-2">
            <label class="form-label" for="data_pagto">Data Pagamento:*</label>
            <input type="date" class="form-control" id="data_pagto" name="data_pagto">
          </div>

          <div class="col-2">
            <label class="form-label" for="valor_pagto">Valor Pagamento:*</label>
            <input type="text" class="form-control" id="valor_pagto" name="valor_pagto">
          </div>

        </div>

      <div class="row mb-3">

        <div>
          <input class="form-check-input" type="checkbox" id="confirmacao" required>
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