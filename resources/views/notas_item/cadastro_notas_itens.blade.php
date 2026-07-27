@extends('layouts.layout')

@section('content')

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="container cadastro">
    <h1 class="mb-4">GERENCIAR ITENS DA NOTA / O.S</h1>

    <form action="{{ route('notasitem.store') }}" method="POST" id="form-os-itens">
        @csrf

    {{-- 1. Identificação do Cliente / Veículo --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">1. Identificação do Cliente / Veículo</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Placa veículo (Opcional para balcão):</label>
                    <input type="text" class="form-control" id="placa_input" placeholder="Digite a placa">
                    <input type="hidden" name="veiculo_cliente_id" id="veiculo_cliente_id">
                </div>

                <div class="col-md-4">
                    <label class="form-label">KM Atual:</label>
                    <input type="number" name="km" id="km" class="form-control" placeholder="Ex: 85000" min="0" value="{{ old('km') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Cliente:*</label>
                    <input type="text" id="cliente_nome" class="form-control" placeholder="Nome do cliente" readonly required>
                    <input type="hidden" name="cliente_id" id="cliente_id">
                </div>
            </div>
        </div>
    </div>

        {{-- 2. Adicionar Produto ou Mão de Obra/Serviço (Polimórfico) --}}
        <div class="card mb-4 shadow-sm border-primary">
            <div class="card-header bg-primary text-white">2. Adicionar Produto ou Serviço (O.S)</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Item</label>
                        <select id="builder_type" class="form-control">
                            <option value="">Selecione...</option>
                            <option value="App\Models\Produto">Produto (Autopeça)</option>
                            <option value="App\Models\OrdemServico">Serviço (Ordem de Serviço)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Item Relacionado</label>
                        <select id="builder_item_id" class="form-control" disabled>
                            <option value="">Selecione o tipo primeiro</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Descrição Exibida na Nota*</label>
                        <input type="text" id="builder_descricao" class="form-control" placeholder="Ex: Mão de Obra Mecânica Geral ou Nome do Produto">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" id="builder_quantidade" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor Unitário (R$)</label>
                        <input type="number" id="builder_valor_unitario" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Desconto (R$)</label>
                        <input type="number" id="builder_desconto" class="form-control" value="0.00" step="0.01" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Garantia (Dias)</label>
                        <input type="number" id="builder_garantia" class="form-control" min="0" placeholder="Ex: 90">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="btn-adicionar-item" class="btn btn-primary w-100">
                            Inserir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Select oculto para carga estática de produtos --}}
        <select id="produtos_estatiticos_local" style="display: none;">
            @foreach($produtos as $prod)
                <option value="{{ $prod->id }}" data-preco="{{ $prod->preco_uni ?? $prod->preco ?? $prod->valor ?? 0 }}">
                    {{ $prod->nome ?? $prod->descricao }}
                </option>
            @endforeach
        </select>

        {{-- 3. Lista de Itens a Serem Salvos --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">3. Lista de Itens a Serem Salvos</div>
            <div class="card-body">
                <table class="table table-bordered table-striped" id="tabela-itens-os">
                    <thead>
                        <tr class="table-secondary">
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th width="10%">Qtd</th>
                            <th width="15%">Val. Unitário</th>
                            <th width="12%">Desconto</th>
                            <th width="15%">Total</th>
                            <th width="10%">Garantia</th>
                            <th width="8%">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="container-itens-dinamicos">
                        <tr id="linha-vazia">
                            <td colspan="8" class="text-center text-muted py-4">Nenhum item adicionado a esta lista ainda.</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">Valor Geral Acumulado:</td>
                            <td id="valor-geral-os" class="text-success">R$ 0,00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="text-center mb-5">
            <button type="submit" class="btn btn-success btn-lg px-5">
                Gravar Todos os Itens
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@vite(['resources/js/gerenciadorItensOs.js', 'resources/js/cadOsItem.js'])
@endsection
