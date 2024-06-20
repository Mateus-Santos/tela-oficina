@extends('layout')

@section('content')

  <div class="container cadastro">
  
  <h1>CADASTRO DE VENDAS</h1>

  <form action="{{ route('vendas.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">
      <div class="row mb-3">

        <div class="col-md-6">
        <label class="form-label" for="data_venda">Data Venda:*</label>
        <input type="date" class="form-control" id="data_venda" placeholder="" name="data_venda" required>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="quantidade">Quantidade:*</label>
            <input class="form-control" id="quantidade" placeholder="" name="quantidade" required>
        </div>
        
      </div>

      <div class="row mb-3">

          <div class="col-md-3"> 
            <label class="form-label" for="valor_uni">Valor Unitário:</label>
            <input type="text" class="form-control" id="valor_uni" placeholder="" name="valor_uni">
          </div>

          <div class="col-md-2">          
            <label class="form-label" for="desconto">Desconto:*</label>
            <input type="text" class="form-control" id="desconto" placeholder="" name="desconto" required>
          </div>
        
        <div class="col-md-3">
          <label class="form-label" for="juros">Juros:*</label>
          <input type="text" class="form-control" id="juros" placeholder="" name="juros" required>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="data_venc">Data de Vencimento:*</label>
          <input type="date" class="form-control" id="data_venc" name="data_venc" required>
        </div>

      </div>

      <div class="row mb-3">
        <div class="col-5">
          <label class="form-label" for="data_pagto">Data de Pagamento:*</label>
          <input type="date" class="form-control" id="data_pagto" name="data_pagto" required>
        </div>
      </div>

    <div class="col text-center">
        <button type="submit" class="btn btn-success">Cadastrar</button>
    </div>

  </div>
</form>
</div>
@endsection