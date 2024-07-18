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
            <input type="text" class="form-control" id="cep" name="cep" required>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="region">Estado:*</label>
            <select id="region" class="form-control" name="region" disabled required>
            <option selected>Estado</option>
              <option value="AC">Acre</option>
              <option value="AL">Alagoas</option>
              <option value="AP">Amapá</option>
              <option value="AM">Amazonas</option>
              <option value="BA">Bahia</option>
              <option value="CE">Ceará</option>
              <option value="DF">Distrito Federal</option>
              <option value="ES">Espírito Santo</option>
              <option value="GO">Goiás</option>
              <option value="MA">Maranhão</option>
              <option value="MT">Mato Grosso</option>
              <option value="MS">Mato Grosso do Sul</option>
              <option value="MG">Minas Gerais</option>
              <option value="PA">Pará</option>
              <option value="PB">Paraíba</option>
              <option value="PR">Paraná</option>
              <option value="PE">Pernambuco</option>
              <option value="PI">Piauí</option>
              <option value="RJ">Rio de Janeiro</option>
              <option value="RN">Rio Grande do Norte</option>
              <option value="RS">Rio Grande do Sul</option>
              <option value="RO">Rondônia</option>
              <option value="RR">Roraima</option>
              <option value="SC">Santa Catarina</option>
              <option value="SP">São Paulo</option>
              <option value="SE">Sergipe</option>
              <option value="TO">Tocantins</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="city">Cidade:*</label>
            <input type="text" class="form-control" id="city" placeholder="Exemplo: Feira de Santana..." name="cidade" disabled required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="neighborhood">Bairro:*</label>
            <input type="text" class="form-control" id="neighborhood" placeholder="Exemplo: Tranquilópolis, Rio Verde..." name="neighborhood" disabled required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="address">Rua:*</label>
            <input type="text" class="form-control" id="address" name="rua" disabled required>
        </div>

        </div>

        <div class="row mb-3">
            
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

@section('scripts')
    @vite(['resources/js/cep.js'])
@endsection