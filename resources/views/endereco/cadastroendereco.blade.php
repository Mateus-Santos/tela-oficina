@extends('layouts.layout')

@section('content')

  <div class="container cadastro">
  
  <h1>CADASTRO DE ENDEREÇO</h1>

  <form action="{{ route('enderecos.store') }}" method="post" class="row g-3">
  @csrf
      <div class="campos">

        <div class="row mb-4">
            <div class="col-md-1">
                <label class="form-label" for="id_user">ID:</label>
                <input class="form-control" value="{{$user->id}}" type="text" name="id_user" id="id_user" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="id_user">Nome pessoa:</label>
                <input class="form-control" value="{{$user->name}}" type="text" name="name" id="name" disabled readonly>
            </div>
        </div>
        
        <div class="row mb-2">
            <div class="col-md-2">
            <label class="form-label" for="cep">CEP:*</label>
            <input type="text" class="form-control" id="cep" name="cep" required>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="region">Estado:*</label>
            <select id="region" class="form-control" name="region" readonly required>
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
            <input type="text" class="form-control" id="city" placeholder="Exemplo: Feira de Santana..." name="city" readonly required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="neighborhood">Bairro:*</label>
            <input type="text" class="form-control" id="neighborhood" placeholder="Exemplo: Tranquilópolis, Rio Verde..." name="neighborhood" readonly required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="address">Rua:*</label>
            <input type="text" class="form-control" id="address" name="address" readonly required>
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

@if ($errors->any())
    @vite(['resources/js/cadError.js'])
    <div id="content-to-remove" class="mensseger_error_container">
        <div class="font-medium text-red-600">Ops, algo deu errado.</div>

        <ul class="mt-3 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button id="remove-button" type="submit" class="btn btn-success">CLOSE</button>
    </div>
@endif
@endsection

@section('scripts')
    @vite(['resources/js/cep.js'])    
@endsection