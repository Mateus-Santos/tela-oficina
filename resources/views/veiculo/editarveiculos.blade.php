@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<section class="container cadastro">
    <h1><i class="bi bi-gear"></i> EDITAR VEÍCULO</h1>
    
    <div class="campos">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="{{ route('veiculosclientes.update', $veiculoscliente->id) }}" method="POST" class="row g-3">
            @csrf
            @method('PATCH')

            <div class="row mb-3">
                {{-- Visão de Administrador --}}
                @if(auth()->user() && auth()->user()->permitions == 1)
                    <div class="col-md-6">
                        <label class="form-label" for="id_cliente">Cliente:*</label>
                        <select class="form-control" id="id_cliente" name="id_cliente" required>
                            <option value="">Selecione um cliente...</option>
                            @foreach($clientes as $cliente)
                                <option 
                                    value="{{ $cliente->id }}"
                                    {{ $cliente->id == $veiculoscliente->cliente_id ? 'selected' : '' }}
                                >
                                    {{ $cliente->pessoa->nome ?? 'Cliente sem nome' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    {{-- Visão de Usuário Comum --}}
                    <div class="col-md-6">
                        <label class="form-label" for="cliente_nome">Cliente:*</label>
                        <input 
                            class="form-control" 
                            type="text" 
                            id="cliente_nome"
                            value="{{ $veiculoscliente->cliente->pessoa->nome ?? auth()->user()->pessoa->nome ?? 'Cliente' }}" 
                            disabled
                        >
                        <input 
                            type="hidden" 
                            name="id_cliente" 
                            value="{{ $veiculoscliente->cliente_id }}"
                        >
                    </div>
                @endif
            </div>

            {{-- Campos de Montadora e Veículo recuperados --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="montadora">Montadora:*</label>
                    <select class="form-control" id="montadora" name="montadora" required>
                        <option value="">Selecione...</option>
                        @foreach($montadoras as $montadora)
                            <option 
                                value="{{ $montadora->id }}"
                                {{ $montadora->id == optional($veiculoscliente->veiculo)->montadora_id ? 'selected' : '' }}
                            >
                                {{ $montadora->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="veiculo_id">Veículo:*</label>
                    <select id="veiculo_id" name="veiculo_id" class="form-control" required>
                        <option value="">Selecione a montadora primeiro</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="placa">Placa:*</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="{{ old('placa', $veiculoscliente->placa) }}" 
                        id="placa" 
                        name="placa" 
                        placeholder="Digite a placa do veículo" 
                        maxlength="8" 
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="ano">Ano:*</label>
                    <input 
                        type="number" 
                        class="form-control" 
                        value="{{ old('ano', $veiculoscliente->ano) }}" 
                        id="ano" 
                        name="ano" 
                        placeholder="ex.: 2022" 
                        min="1900" 
                        max="{{ date('Y') + 1 }}" 
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="cor">Cor:*</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="{{ old('cor', $veiculoscliente->cor) }}" 
                        id="cor" 
                        name="cor" 
                        placeholder="Digite a cor do veículo" 
                        maxlength="30" 
                        required
                    >
                </div>
            </div>

            <div class="col text-center mt-4">
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-pencil-square"></i> Editar Veículo
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    var veiculoSelecionado = "{{ $veiculoscliente->veiculo_id }}";
</script>

@vite(['resources/js/cadVeiculo.js'])
@endsection