@extends('layout')

@section('content')

  <div class="container cadastro">
  
  <h1>CADASTRO DE ENDEREÇO</h1>

  <form action="{{ route('enderecos.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">

        <div class="row mb-4">
            <div class="col-md-2">
                <label class="form-label" for="id_pessoa">Pessoa:</label>
                <select id="id_pessoa" class="form-control" name="id_pessoa" required>
                <option selected>Escolher...</option>
                @foreach($pessoas as $pessoa)
                <option value="{{$pessoa->id_pessoa}}">{{ $pessoa->nome }}</option>
                @endforeach
                </select>
            </div>
        </div>
        
        <div class="row mb-2">
            <div class="col-md-2">
            <label class="form-label" for="cep">CEP:*</label>
            <input type="text" class="form-control" id="cep" placeholder="00000-000" name="cep" required>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="estado">Estado:*</label>
            <select id="estado" class="form-control" name="estado" required>
            <option selected>Escolher...</option>
            <option value="Bahia">Bahia</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="cidade">Cidade:*</label>
            <input type="text" class="form-control" id="cidade" placeholder="Exemplo: Belmonte, Serraville, Tranquilópolis, Rio Verde..." name="cidade" required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="bairro">Bairro:*</label>
            <input type="text" class="form-control" id="bairro" placeholder="Exemplo: Belmonte, Serraville, Tranquilópolis, Rio Verde..." name="bairro" required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="rua">Rua:*</label>
            <input type="text" class="form-control" id="rua" name="rua" required>
        </div>

        </div>

        <div class="row mb-3">
            <div class="col-md-5">
            <label class="form-label" for="endereco">Endereço:*</label>
            <input type="text" class="form-control" id="endereco" placeholder="Apartamento, hotel, casa, etc." name="endereco" required>
            </div>
            
            <div class="col-md-1">
            <label class="form-label" for="numero">Número:*</label>
            <input type="text" class="form-control" id="numero" name="numero" required>
            </div>

            <div class="col">
            <label class="form-label" for="ponto_referencia">Ponto de referência:*</label>
            <input type="text" class="form-control" id="ponto_referencia" placeholder="Exemplo: Próximo ao restaurante, loja, igreja..." name="ponto_referencia" required>
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
</div>
@endsection