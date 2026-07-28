@extends('layouts.layout')

@section('content')

  <div class="container cadastro">
  
  <h1>EDITAR ENDEREÇO</h1>

  <form action="{{ route('enderecos.update', $enderecos->id) }}" method="post" class="row g-3">
    @csrf
    @method('PUT')

      <div class="campos">

        <!-- ID da Pessoa e Nome (Igual ao cadastro) -->
        <div class="row mb-4">
            <div class="col-md-1">
                <label class="form-label" for="pessoa_id">ID:</label>
                <input class="form-control" value="{{ $enderecos->pessoa_id }}" type="text" name="pessoa_id" id="pessoa_id" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nome">Nome pessoa:</label>
                <input class="form-control" value="{{ $enderecos->pessoa->nome ?? 'N/A' }}" type="text" name="nome" id="nome" disabled readonly>
            </div>
        </div>
        
        <div class="row mb-2">
            <div class="col-md-2">
                <label class="form-label" for="cep">CEP:*</label>
                <input type="text" class="form-control" id="cep" name="cep" value="{{ old('cep', $enderecos->cep) }}" required>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="region">Estado:*</label>
                <select id="region" class="form-control" name="region" required>
                    <option value="">Estado</option>
                    @php
                        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        $estadoAtual = old('region', $enderecos->estado);
                    @endphp
                    @foreach($estados as $uf)
                        <option value="{{ $uf }}" {{ $estadoAtual == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="city">Cidade:*</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $enderecos->cidade) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="neighborhood">Bairro:*</label>
                <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="{{ old('neighborhood', $enderecos->bairro) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="address">Rua:*</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $enderecos->rua) }}" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-1">
                <label class="form-label" for="numero">Número:*</label>
                <input type="text" class="form-control" id="numero" name="numero" value="{{ old('numero', $enderecos->numero) }}" required>
            </div>

            <div class="col">
                <label class="form-label" for="ponto_referencia">Ponto de referência:*</label>
                <input type="text" class="form-control" id="ponto_referencia" name="ponto_referencia" value="{{ old('ponto_referencia', $enderecos->ponto_referencia) }}" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col">
                <input class="form-check-input" type="checkbox" id="confirmacao" required>
                <label for="confirmacao">
                    Confirmo que todas as informações alteradas são verdadeiras.
                </label>
            </div>
        </div>

        <div class="col text-center">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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
        <button id="remove-button" type="button" class="btn btn-success">CLOSE</button>
    </div>
@endif
@endsection

@section('scripts')
    @vite(['resources/js/cep.js'])    
@endsection