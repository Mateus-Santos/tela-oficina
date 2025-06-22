@extends('layouts.layout')

@section('content')
<section class="container cadastro">

    <h1><i class="bi bi-gear"></i> CADASTRO DE PRODUTOS</h1>

    {{-- Exibe erros de validação --}}
    @if ($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Exibe mensagem de sucesso --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form id="form-produto"
          action="{{ route('produtos.store') }}"
          enctype="multipart/form-data"
          method="POST"
          class="row g-3">
        @csrf

        <div class="campos">

            {{-- Linha 0 --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="codigo_barras">Código de Barras:</label>
                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="codigo_fabricante">Cod. do Fabricante:*</label>
                    <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
                </div>
            </div>

            {{-- Linha 1 --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label" for="nome">Nome:*</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="ano_modelo">Ano Modelo:*</label>
                    <input type="number" class="form-control" id="ano_modelo" name="ano_modelo" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="preco_uni">Preço Unitário (R$):*</label>
                    <input type="number" step="0.01" class="form-control" id="preco_uni" name="preco_uni" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="quantidade">Quantidade:*</label>
                    <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                </div>
            </div>

            {{-- Linha 2 --}}
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label" for="motor">Motor:</label>
                    <input type="text" class="form-control" id="motor" name="motor">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="img">Imagem:</label>
                    <input type="file" class="form-control" id="img" name="img">
                </div>
            </div>

            {{-- Linha 3 - Relacionamentos --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label" for="veiculos">Veículo(s):*</label>
                    <select name="veiculos[]" id="veiculos" class="form-select" multiple required>
                        @foreach($veiculos as $veiculo)
                            <option value="{{ $veiculo->id }}">{{ $veiculo->placa }} - {{ $veiculo->marca }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="marcas">Marca(s):*</label>
                    <input type="text" name="marcas[]" class="form-control" placeholder="Ex: Bosch, Marelli" required>
                    {{-- Ou use um select se tiver tabela separada de marcas --}}
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="departamentos">Departamento(s):*</label>
                    <select name="departamentos[]" id="departamentos" class="form-select" multiple required>
                        @foreach($departamentos as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="valvula">Válvula(s):</label>
                    <select name="valvula[]" id="valvula" class="form-select" multiple>
                        @foreach($valvulas as $valvula)
                            <option value="{{ $valvula->id }}">{{ $valvula->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="montadora">Montadora(s):*</label>
                    <select name="montadora[]" id="montadora" class="form-select" multiple required>
                        @foreach($montadoras as $montadora)
                            <option value="{{ $montadora->id }}">{{ $montadora->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Linha 4 --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label" for="descricao">Descrição:</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                </div>
            </div>

            {{-- Botão --}}
            <div class="col text-center">
                <button type="submit" class="btn btn-success">Cadastrar</button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/cadProduto.js'])

    {{-- Select2 (opcional) --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#veiculos, #departamentos, #valvula, #montadora').select2({
                placeholder: 'Selecione',
                width: '100%',
                allowClear: true
            });
        });
    </script>
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
