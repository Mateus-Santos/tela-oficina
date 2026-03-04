@extends('layouts.layout')

@section('content')
<section class="container cadastro">

    <h1><i class="bi bi-gear"></i> CADASTRO DE PRODUTOS</h1>

    {{-- Erros --}}
    @if ($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Sucesso --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="form-produto"
          action="{{ route('produtos.store') }}"
          enctype="multipart/form-data"
          method="POST"
          class="row g-3">

        @csrf

        <div class="campos">

            {{-- Código de Barras --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="codigo_barras">Código de Barras:</label>
                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras">
                </div>
            </div>

            {{-- Linha 1 --}}
            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label" for="nome">Nome:*</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="preco_uni">Preço Unitário (R$):*</label>
                    <input type="text" inputmode="numeric" class="form-control" id="preco_uni" name="preco_uni" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="quantidade">Quantidade:*</label>
                    <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                </div>

            </div>

            {{-- Linha 2 --}}
            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label" for="codigo_fabricante">Cod. Fabricante:*</label>
                    <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
                </div>

                <div class="col-md-4">
                <label class="form-label" for="img">Imagem:</label>
                {{-- Preview da imagem selecionada --}}
                <div class="mb-2">
                    <img 
                        id="img-preview"
                        style="display:none; max-width: 120px; border-radius: 10px;"
                        alt="Preview da imagem"
                    >
                </div>

                <input 
                    type="file" 
                    class="form-control" 
                    id="img" 
                    name="img"
                    accept="image/*"
                >
            </div>


            </div>

            {{-- Linha 3 (Montadora → Veículos → Tags) --}}
            <div class="row mb-3">

                {{-- Montadora --}}
                <div class="col-md-4">
                    <label class="form-label">Montadora:*</label>
                    <select id="montadora_select" class="form-control">
                        <option value="">Escolha uma Montadora</option>
                        @foreach($montadoras as $montadora)
                            <option value="{{ $montadora->id }}">{{ $montadora->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Veículos dependentes --}}
                <div class="col-md-4">
                    <label class="form-label">Veículo(s):*</label>
                    <select id="veiculo_select" class="form-control">
                        <option value="">Selecione uma montadora primeiro</option>
                    </select>

                    <div class="tags-input mt-2" data-name="veiculos[]">
                        <div class="tags-container"></div>
                    </div>
                </div>
            </div>

            {{-- Descrição --}}
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label" for="descricao">Descrição:</label>
                    <textarea class="form-control" id="descricao" name="descricao"></textarea>
                </div>
            </div>

        </div>

        {{-- Botão --}}
        <div class="col text-center">
            <button type="submit" class="btn btn-success">Cadastrar</button>
        </div>

    </form>

</section>
@endsection

@section('scripts')
@vite(['resources/js/cadProduto.js'])
@endsection
