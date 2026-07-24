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
    <h1 class="mb-4">EDITAR NOTA FISCAL #{{ $nota->id }}</h1>

    <form action="{{ route('notasitem.update', $nota->id) }}" method="POST" id="form-os-itens">
        @csrf
        @method('PUT')

        {{-- 1. Identificação do Cliente / Veículo --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-dark text-white">1. Identificação do Cliente / Veículo</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Placa veículo (Opcional para balcão):</label>
                        <input type="text" class="form-control" id="placa_input" placeholder="Digite a placa" value="{{ $nota->veiculoscliente?->placa }}">
                        <input type="hidden" name="veiculo_cliente_id" id="placa_input" value="{{ $nota->veiculo_cliente_id }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cliente:*</label>
                        <input type="text" id="cliente_nome" class="form-control" placeholder="Nome do cliente" readonly required value="{{ $nota->cliente?->pessoa?->nome ?? 'Cliente Balcão' }}">
                        <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $nota->cliente_id }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Adicionar Produto ou Serviço ao Carrinho --}}
        <div class="card mb-4 shadow-sm border-primary">
            <div class="card-header bg-primary text-white">2. Adicionar Novo Item</div>
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
                        <input type="text" id="builder_descricao" class="form-control" placeholder="Ex: Mão de Obra Mecânica Geral">
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
                        <label class="form-label">Desconto Item (R$)</label>
                        <input type="number" id="builder_desconto" class="form-control" value="0.00" step="0.01" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Garantia (Dias)</label>
                        <input type="number" id="builder_garantia" class="form-control" min="0" placeholder="Ex: 90">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="btn-adicionar-item" class="btn btn-primary w-100">
                            Inserir Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Select oculto para carga de produtos do banco --}}
        <select id="produtos_estatiticos_local" style="display: none;">
            @foreach($produtos as $prod)
                <option value="{{ $prod->id }}" data-preco="{{ $prod->preco_uni ?? $prod->preco ?? $prod->valor ?? 0 }}">
                    {{ $prod->nome ?? $prod->descricao }}
                </option>
            @endforeach
        </select>

        {{-- 3. Tabela de Itens Editáveis --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">3. Itens da Nota Fiscal</div>
            <div class="card-body">
                <table class="table table-bordered align-middle" id="tabela-itens-os">
                    <thead>
                        <tr class="table-secondary">
                            <th width="10%">Tipo</th>
                            <th width="25%">Descrição</th>
                            <th width="10%">Qtd</th>
                            <th width="13%">Val. Unitário (R$)</th>
                            <th width="12%">Desconto (R$)</th>
                            <th width="12%">Total (R$)</th>
                            <th width="10%">Garantia</th>
                            <th width="8%">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="container-itens-dinamicos">
                        @forelse($nota->notasitem as $index => $item)
                        @php
                            $isProduto = $item->itemable_type === 'App\Models\Produto';
                            $valorTotalItem = ($item->quantidade * $item->valor_unitario) - $item->desconto;
                        @endphp
                        <tr class="item-row">
                            <td>
                                <span class="badge {{ $isProduto ? 'bg-info' : 'bg-warning' }} text-dark">
                                    {{ $isProduto ? 'Produto' : 'Serviço' }}
                                </span>
                                <input type="hidden" name="itens[{{ $index }}][id]" value="{{ $item->id }}">
                                <input type="hidden" name="itens[{{ $index }}][itemable_type]" value="{{ $item->itemable_type }}">
                                <input type="hidden" name="itens[{{ $index }}][itemable_id]" value="{{ $item->itemable_id }}">
                            </td>
                            <td>
                                <input type="text" name="itens[{{ $index }}][descricao]" class="form-control form-control-sm input-desc" value="{{ $item->descricao }}" required>
                            </td>
                            <td>
                                <input type="number" name="itens[{{ $index }}][quantidade]" class="form-control form-control-sm input-qtd" value="{{ $item->quantidade }}" min="1" step="1" required>
                            </td>
                            <td>
                                <input type="number" name="itens[{{ $index }}][valor_unitario]" class="form-control form-control-sm input-vunit" value="{{ number_format($item->valor_unitario, 2, '.', '') }}" step="0.01" min="0" required>
                            </td>
                            <td>
                                <input type="number" name="itens[{{ $index }}][desconto]" class="form-control form-control-sm input-desc-val" value="{{ number_format($item->desconto ?? 0, 2, '.', '') }}" step="0.01" min="0">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm input-vtotal fw-bold bg-light" value="{{ number_format($valorTotalItem, 2, ',', '.') }}" readonly>
                            </td>
                            <td>
                                <input type="number" name="itens[{{ $index }}][garantia_dias]" class="form-control form-control-sm input-garantia" value="{{ $item->garantia_dias }}" min="0" placeholder="Dias">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-remover-item" title="Remover Item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="linha-vazia">
                            <td colspan="8" class="text-center text-muted py-4">Nenhum item adicionado a esta lista ainda.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Resumo Financeiro & Desconto Geral da Nota --}}
        <div class="card mb-4 shadow-sm border-secondary">
            <div class="card-header bg-secondary text-white">4. Resumo Financeiro & Desconto Geral</div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Desconto Geral da Nota</label>
                        <div class="input-group">
                            <select name="tipo_desconto_geral" id="tipo-desconto-geral" class="form-select" style="max-width: 90px;">
                                <option value="valor" {{ ($nota->tipo_desconto ?? '') == 'valor' ? 'selected' : '' }}>R$</option>
                                <option value="porcentagem" {{ ($nota->tipo_desconto ?? '') == 'porcentagem' ? 'selected' : '' }}>%</option>
                            </select>
                            <input type="number" name="desconto_geral" id="input-desconto-geral" class="form-control" step="0.01" min="0" value="{{ number_format($nota->desconto ?? 0, 2, '.', '') }}" placeholder="0.00">
                        </div>
                        <small class="text-muted">Aplica um desconto sobre a soma total dos itens.</small>
                    </div>

                    <div class="col-md-8">
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal dos Itens:</span>
                                <strong id="resumo-subtotal">R$ {{ number_format($nota->subtotal ?? $nota->total, 2, ',', '.') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Desconto Aplicado:</span>
                                <strong id="resumo-desconto">- R$ {{ number_format($nota->desconto ?? 0, 2, ',', '.') }}</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between text-success fs-4 fw-bold">
                                <span>Total Final:</span>
                                <span id="valor-geral-os">R$ {{ number_format($nota->total, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-5">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                Atualizar Nota Fiscal
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@vite(['resources/js/gerenciadorItensOs.js', ])
@endsection
