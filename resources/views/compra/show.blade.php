@extends('layouts.layout')

@section('content')

<div class="container cadastro">

@php
    $estoqueLancado = $compra->itens->isNotEmpty() && $compra->itens->every(
        fn ($item) => $item->movimentacoesEstoque->contains('tipo', 'entrada')
    );

    $algumEstoqueLancado = $compra->itens->contains(
        fn ($item) => $item->movimentacoesEstoque->contains('tipo', 'entrada')
    );

    $todosItensConferidos = $compra->itens->isNotEmpty() && $compra->itens->every(
        fn ($item) => $item->quantidade_conferida !== null
            && (float) $item->quantidade_conferida > 0
    );

    $contaPagar = $compra->contaPagar;
@endphp

<x-list-header title="VISUALIZAR COMPRA" icon="bi-cart-check" />

@if (session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        {{ session('error') }}
    </div>
@endif

{{-- STATUS DA COMPRA --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <h2 class="h5 mb-1">
                    <i class="bi bi-cart-check"></i>
                    Situação da compra
                </h2>
                <p class="text-muted mb-0">
                    Acompanhe o andamento da compra desde o cadastro até a conclusão das operações.
                </p>
            </div>

            @if ($compra->status === 'pendente')
                <span class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-clock"></i>
                    Pendente
                </span>
            @elseif ($compra->status === 'conferindo')
                <span class="badge bg-info text-dark fs-6">
                    <i class="bi bi-clipboard-check"></i>
                    Em conferência
                </span>
            @elseif ($compra->status === 'aprovada')
                <span class="badge bg-success fs-6">
                    <i class="bi bi-check-circle"></i>
                    Aprovada
                </span>
            @elseif ($compra->status === 'cancelada')
                <span class="badge bg-danger fs-6">
                    <i class="bi bi-x-circle"></i>
                    Cancelada
                </span>
            @endif
        </div>

        @if ($compra->status === 'pendente')
            <div class="alert alert-warning mt-3 mb-0">
                <i class="bi bi-info-circle"></i>
                Esta compra está pendente e ainda não iniciou a conferência dos produtos.
            </div>
        @elseif ($compra->status === 'conferindo')
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-clipboard-check"></i>
                A compra está em conferência. Informe a quantidade realmente recebida de cada item antes de aprovar.
            </div>
        @elseif ($compra->status === 'aprovada')
            <div class="alert alert-success mt-3 mb-0">
                <i class="bi bi-check-circle"></i>
                A compra foi aprovada. As integrações de estoque e financeiro podem ser concluídas separadamente.
            </div>
        @elseif ($compra->status === 'cancelada')
            <div class="alert alert-danger mt-3 mb-0">
                <i class="bi bi-x-circle"></i>
                Esta compra foi cancelada e não pode mais ser alterada.
            </div>
        @endif
    </div>
</div>

{{-- INTEGRAÇÕES DA COMPRA --}}
@if ($compra->status === 'aprovada')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="mb-4">
                <h2 class="h5 mb-1">
                    <i class="bi bi-diagram-3"></i>
                    Integrações da compra
                </h2>
                <p class="text-muted mb-0">
                    Acompanhe e conclua as operações geradas a partir desta compra.
                </p>
            </div>

            <div class="row g-4">
                {{-- ESTOQUE --}}
                <div class="col-12 col-lg-6">
                    <div class="border rounded h-100 p-3">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h3 class="h6 mb-1">
                                    <i class="bi bi-box-seam"></i>
                                    Estoque
                                </h3>
                                <small class="text-muted">
                                    Entrada dos produtos desta compra no estoque.
                                </small>
                            </div>

                            @if ($estoqueLancado)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i>
                                    Concluído
                                </span>
                            @elseif ($algumEstoqueLancado)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Parcial
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-clock"></i>
                                    Pendente
                                </span>
                            @endif
                        </div>

                        @if ($estoqueLancado)
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle"></i>
                                Todos os itens desta compra já possuem entrada registrada no estoque.
                            </div>
                        @elseif ($algumEstoqueLancado)
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                Existem itens com entrada registrada no estoque.
                            </div>

                            <small class="text-muted d-block mb-3">
                                Para desfazer essas movimentações, utilize o estorno da compra.
                            </small>
                        @else
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i>
                                A compra está aprovada e pronta para entrada no estoque.
                            </div>

                            <form
                                method="POST"
                                action="{{ route('compras.estoque', $compra) }}"
                                onsubmit="return confirm('Tem certeza que deseja lançar esta compra no estoque? Após o lançamento, o estoque poderá ser revertido posteriormente pela opção de estorno.');"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                >
                                    <i class="bi bi-box-arrow-in-down"></i>
                                    Lançar no estoque
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- FINANCEIRO --}}
                <div class="col-12 col-lg-6">
                    <div class="border rounded h-100 p-3">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h3 class="h6 mb-1">
                                    <i class="bi bi-wallet2"></i>
                                    Financeiro
                                </h3>
                                <small class="text-muted">
                                    Conta a pagar vinculada a esta compra.
                                </small>
                            </div>

                            @if ($contaPagar)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i>
                                    Gerada
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-clock"></i>
                                    Pendente
                                </span>
                            @endif
                        </div>

                        @if ($contaPagar)
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="text-muted small">
                                        Descrição
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $contaPagar->descricao }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-muted small">
                                        Valor
                                    </div>
                                    <div class="fw-semibold">
                                        R$ {{ number_format((float) $contaPagar->valor, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-muted small">
                                        Status
                                    </div>
                                    <div class="fw-semibold">
                                        {{ ucfirst($contaPagar->status) }}
                                    </div>
                                </div>
                            </div>

                            <a
                                href="{{ route('contas-pagar.show', $contaPagar) }}"
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-wallet2"></i>
                                Visualizar conta a pagar
                            </a>
                        @else
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                Nenhuma conta a pagar foi gerada para esta compra.
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalGerarContaCompra"
                            >
                                <i class="bi bi-wallet2"></i>
                                Gerar conta a pagar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ANEXOS --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
            <h2 class="h5 mb-0">
                <i class="bi bi-paperclip"></i>
                Documentos e anexos
            </h2>

            <span class="badge bg-secondary">
                <i class="bi bi-files"></i>
                {{ $compra->anexosVinculos->count() }}
                {{ $compra->anexosVinculos->count() === 1 ? 'arquivo' : 'arquivos' }}
            </span>
        </div>

        @if (!in_array($compra->status, ['aprovada', 'cancelada'], true))
            <form
                method="POST"
                action="{{ route('compras.anexos.store', $compra) }}"
                enctype="multipart/form-data"
                class="mb-4"
            >
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="tipo" class="form-label">
                            Tipo do documento
                        </label>

                        <select
                            name="tipo"
                            id="tipo"
                            class="form-select"
                            required
                        >
                            <option value="">Selecione...</option>
                            <option value="nf">Nota fiscal</option>
                            <option value="nf_xml">NF-e XML</option>
                            <option value="foto">Foto</option>
                            <option value="comprovante">Comprovante</option>
                            <option value="boleto">Boleto</option>
                            <option value="contrato">Contrato</option>
                            <option value="orcamento">Orçamento</option>
                            <option value="conta_luz">Conta de luz</option>
                            <option value="conta_agua">Conta de água</option>
                            <option value="conta_telefone">Conta de telefone</option>
                            <option value="recibo">Recibo</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label for="arquivo" class="form-label">
                            Arquivo
                        </label>

                        <input
                            type="file"
                            name="arquivo"
                            id="arquivo"
                            class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.xml"
                            required
                        >

                        <small class="text-muted">
                            PDF, JPG, JPEG, PNG, WEBP ou XML — máximo de 2 MB.
                        </small>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="observacoes" class="form-label">
                            Observações
                        </label>

                        <input
                            type="text"
                            name="observacoes"
                            id="observacoes"
                            class="form-control"
                            maxlength="1000"
                        >
                    </div>

                    <div class="col-12">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-cloud-arrow-up"></i>
                            Enviar anexo
                        </button>
                    </div>
                </div>
            </form>
        @elseif ($compra->status === 'aprovada')
            <div class="alert alert-info mb-4">
                <i class="bi bi-lock"></i>
                Esta compra está aprovada. Novos anexos não podem ser adicionados.
            </div>
        @else
            <div class="alert alert-warning mb-4">
                <i class="bi bi-lock"></i>
                Esta compra está cancelada. Novos anexos não podem ser adicionados.
            </div>
        @endif

        @if ($compra->anexosVinculos->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>TIPO</th>
                            <th>ARQUIVO</th>
                            <th>TAMANHO</th>
                            <th>OBSERVAÇÕES</th>
                            <th>DATA</th>
                            <th class="text-end">AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($compra->anexosVinculos as $vinculo)
                            @php
                                $anexo = $vinculo->anexo;
                                $tipoAnexo = match ($vinculo->tipo) {
                                    'nf' => ['label' => 'Nota fiscal', 'icon' => 'bi-receipt'],
                                    'nf_xml' => ['label' => 'NF-e XML', 'icon' => 'bi-filetype-xml'],
                                    'foto' => ['label' => 'Foto', 'icon' => 'bi-image'],
                                    'comprovante' => ['label' => 'Comprovante', 'icon' => 'bi-file-earmark-check'],
                                    'boleto' => ['label' => 'Boleto', 'icon' => 'bi-upc'],
                                    'contrato' => ['label' => 'Contrato', 'icon' => 'bi-file-earmark-text'],
                                    'orcamento' => ['label' => 'Orçamento', 'icon' => 'bi-file-earmark-spreadsheet'],
                                    'conta_luz' => ['label' => 'Conta de luz', 'icon' => 'bi-lightbulb'],
                                    'conta_agua' => ['label' => 'Conta de água', 'icon' => 'bi-droplet'],
                                    'conta_telefone' => ['label' => 'Conta de telefone', 'icon' => 'bi-telephone'],
                                    'recibo' => ['label' => 'Recibo', 'icon' => 'bi-file-earmark-check'],
                                    default => ['label' => 'Outro', 'icon' => 'bi-file-earmark'],
                                };
                            @endphp

                            <tr>
                                <td>
                                    <span class="badge bg-secondary">
                                        <i class="bi {{ $tipoAnexo['icon'] }}"></i>
                                        {{ $tipoAnexo['label'] }}
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-semibold text-break">
                                        {{ $anexo->nome_original }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $anexo->mime_type }}
                                    </small>
                                </td>

                                <td>
                                    {{ number_format($anexo->tamanho / 1024 / 1024, 2, ',', '.') }} MB
                                </td>

                                <td>
                                    {{ $vinculo->observacoes ?: '-' }}
                                </td>

                                <td>
                                    {{ $vinculo->created_at?->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <a
                                            href="{{ route('anexos.download', $anexo) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Baixar arquivo"
                                        >
                                            <i class="bi bi-download"></i>
                                        </a>

                                        @if (!in_array($compra->status, ['aprovada', 'cancelada'], true))
                                            <form
                                                method="POST"
                                                action="{{ route('anexos.destroy', $vinculo) }}"
                                                onsubmit="return confirm('Tem certeza que deseja excluir este anexo?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Excluir anexo"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-light border mb-0">
                <i class="bi bi-info-circle"></i>
                Nenhum documento ou anexo foi cadastrado para esta compra.
            </div>
        @endif
    </div>
</div>

{{-- DADOS DA NF --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">
            <i class="bi bi-file-earmark-text"></i>
            Dados da nota fiscal
        </h2>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="text-muted small">
                    Fornecedor
                </div>
                <div class="fw-semibold">
                    <i class="bi bi-truck"></i>
                    {{ $compra->fornecedor->nome ?? 'Não informado' }}
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="text-muted small">
                    Número
                </div>
                <div class="fw-semibold">
                    {{ $compra->numero_nf }}
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="text-muted small">
                    Série
                </div>
                <div class="fw-semibold">
                    {{ $compra->serie_nf ?: '-' }}
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="text-muted small">
                    Emissão
                </div>
                <div class="fw-semibold">
                    {{ $compra->data_emissao?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div class="col-12 col-md-2">
                <div class="text-muted small">
                    Entrada
                </div>
                <div class="fw-semibold">
                    {{ $compra->data_entrada?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            @if ($compra->chave_nf)
                <div class="col-12">
                    <div class="text-muted small">
                        Chave de acesso
                    </div>
                    <div class="fw-semibold text-break">
                        <i class="bi bi-upc-scan"></i>
                        {{ $compra->chave_nf }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ITENS --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-0">
                    <i class="bi bi-box-seam"></i>
                    Produtos da compra
                </h2>

                @if ($compra->status === 'conferindo')
                    <small class="text-muted">
                        Informe abaixo a quantidade realmente recebida de cada produto.
                    </small>
                @endif
            </div>

            <span class="badge bg-secondary">
                <i class="bi bi-boxes"></i>
                {{ $compra->itens->count() }}
                {{ $compra->itens->count() === 1 ? 'item' : 'itens' }}
            </span>
        </div>

        @if ($compra->itens->isNotEmpty())
            @if ($compra->status === 'conferindo')
                <form
                    method="POST"
                    action="{{ route('compras.conferir', $compra) }}"
                >
                    @csrf
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">PRODUTO</th>
                            <th scope="col">DESCRIÇÃO</th>
                            <th scope="col">QUANTIDADE NF</th>
                            <th scope="col">RECEBIDA</th>
                            <th scope="col">VALOR UNITÁRIO</th>
                            <th scope="col">DESCONTO</th>
                            <th scope="col">TOTAL</th>
                            <th scope="col">ESTOQUE</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($compra->itens as $item)
                            @php
                                $quantidade = (float) $item->quantidade;
                                $conferida = $item->quantidade_conferida !== null
                                    ? (float) $item->quantidade_conferida
                                    : null;
                                $conferenteIgual = $conferida !== null
                                    && abs($quantidade - $conferida) < 0.0001;
                                $itemEstoqueLancado = $item->movimentacoesEstoque->contains('tipo', 'entrada');
                            @endphp

                            <tr>
                                <td>
                                    <strong>
                                        {{ $item->produto->nome ?? 'Produto não encontrado' }}
                                    </strong>

                                    @if ($item->produto?->codigo_fabricante)
                                        <br>
                                        <small class="text-muted">
                                            Código: {{ $item->produto->codigo_fabricante }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    {{ $item->descricao }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format($quantidade, 3, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($compra->status === 'conferindo')
                                        <input
                                            type="number"
                                            name="itens[{{ $item->id }}][quantidade_conferida]"
                                            class="form-control"
                                            value="{{ old('itens.' . $item->id . '.quantidade_conferida', $conferida) }}"
                                            min="0.001"
                                            step="0.001"
                                            required
                                        >

                                        @if ($conferida !== null)
                                            <small class="text-muted">
                                                Atual: {{ number_format($conferida, 3, ',', '.') }}
                                            </small>
                                        @endif
                                    @elseif ($conferida !== null)
                                        <span class="badge {{ $conferenteIgual ? 'bg-success' : 'bg-warning text-dark' }}">
                                            <i class="bi {{ $conferenteIgual ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>
                                            {{ number_format($conferida, 3, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            <i class="bi bi-dash-circle"></i>
                                            Não conferida
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    R$ {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}
                                </td>

                                <td>
                                    R$ {{ number_format((float) $item->desconto, 2, ',', '.') }}
                                </td>

                                <td>
                                    <strong>
                                        R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($itemEstoqueLancado)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i>
                                            Lançado
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-clock"></i>
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($compra->status === 'conferindo')
                <div class="d-flex justify-content-end mt-3">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-save"></i>
                        Salvar conferência
                    </button>
                </div>
            @endif

            @if ($compra->status === 'conferindo')
                </form>
            @endif
        @else
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle"></i>
                Esta compra não possui produtos cadastrados.
            </div>
        @endif
    </div>
</div>

{{-- RESUMO FINANCEIRO --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">
            <i class="bi bi-calculator"></i>
            Resumo financeiro
        </h2>

        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="text-muted small">
                    Produtos
                </div>
                <div class="fw-semibold">
                    R$ {{ number_format((float) $compra->valor_produtos, 2, ',', '.') }}
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="text-muted small">
                    Desconto
                </div>
                <div class="fw-semibold">
                    R$ {{ number_format((float) $compra->desconto, 2, ',', '.') }}
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="text-muted small">
                    Frete
                </div>
                <div class="fw-semibold">
                    R$ {{ number_format((float) $compra->frete, 2, ',', '.') }}
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="text-muted small">
                    Outras despesas
                </div>
                <div class="fw-semibold">
                    R$ {{ number_format((float) $compra->outras_despesas, 2, ',', '.') }}
                </div>
            </div>

            <div class="col-12">
                <hr>

                <div class="d-flex justify-content-end align-items-center gap-3">
                    <span class="text-muted">
                        Valor total:
                    </span>

                    <strong class="fs-4">
                        R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- OBSERVAÇÕES --}}
@if ($compra->observacoes)
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <i class="bi bi-chat-left-text"></i>
                Observações
            </h2>

            <div class="text-break">
                {!! nl2br(e($compra->observacoes)) !!}
            </div>
        </div>
    </div>
@endif

{{-- AÇÕES DA COMPRA --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h2 class="h5 mb-1">
                    <i class="bi bi-lightning"></i>
                    Ações da compra
                </h2>

                <p class="text-muted mb-0">
                    Operações relacionadas ao ciclo de vida desta compra.
                </p>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <a
                href="{{ route('compras.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>

            <div class="d-flex gap-2 flex-wrap">
                @if ($compra->status === 'pendente')
                    <a
                        href="{{ route('compras.edit', $compra) }}"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </a>

                    <form
                        method="POST"
                        action="{{ route('compras.iniciar-conferencia', $compra) }}"
                        onsubmit="return confirm('Deseja iniciar a conferência desta compra? Após iniciar, a compra não poderá mais ser editada.');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-info"
                        >
                            <i class="bi bi-clipboard-check"></i>
                            Iniciar conferência
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('compras.cancelar', $compra) }}"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta compra?');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                    </form>
                @elseif ($compra->status === 'conferindo')
                    @if ($todosItensConferidos)
                        <button
                            type="button"
                            class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAprovarCompra"
                        >
                            <i class="bi bi-check-circle"></i>
                            Aprovar compra
                        </button>
                    @else
                        <button
                            type="button"
                            class="btn btn-success"
                            disabled
                            title="Todos os itens precisam ser conferidos com quantidade maior que zero antes da aprovação."
                        >
                            <i class="bi bi-check-circle"></i>
                            Aprovar compra
                        </button>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('compras.cancelar', $compra) }}"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta compra?');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                    </form>
                @elseif ($compra->status === 'aprovada')
                    <span class="text-success d-flex align-items-center">
                        <i class="bi bi-check-circle me-1"></i>
                        Compra aprovada
                    </span>

                    @if ($algumEstoqueLancado)
                        <form
                            method="POST"
                            action="{{ route('compras.estornar', $compra) }}"
                            onsubmit="return confirm('Tem certeza que deseja estornar o estoque e cancelar esta compra? As entradas de estoque serão revertidas e uma movimentação de saída será registrada.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Estornar e cancelar
                            </button>
                        </form>
                    @else
                        <form
                            method="POST"
                            action="{{ route('compras.cancelar', $compra) }}"
                            onsubmit="return confirm('Tem certeza que deseja cancelar esta compra?');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                <i class="bi bi-x-circle"></i>
                                Cancelar
                            </button>
                        </form>
                    @endif
                @elseif ($compra->status === 'cancelada')
                    <span class="text-danger d-flex align-items-center">
                        <i class="bi bi-x-circle me-1"></i>
                        Compra cancelada
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

</div>

@include('compra.show._modal_aprovacao')

@if ($compra->status === 'aprovada' && !$contaPagar)
    <div
        class="modal fade"
        id="modalGerarContaCompra"
        tabindex="-1"
        aria-labelledby="modalGerarContaCompraLabel"
        aria-hidden="true"
        data-valor-total="{{ $compra->valor_total }}"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1
                        class="modal-title fs-5"
                        id="modalGerarContaCompraLabel"
                    >
                        <i class="bi bi-wallet2"></i>
                        Gerar conta a pagar
                    </h1>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <form
                    method="POST"
                    action="{{ route('compras.gerar-conta', $compra) }}"
                    id="formGerarContaCompra"
                >
                    @csrf

                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Configure as parcelas da conta a pagar desta compra.
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label
                                    for="gerar_conta_parcelas_quantidade"
                                    class="form-label"
                                >
                                    Quantidade de parcelas
                                </label>

                                <input
                                    type="number"
                                    id="gerar_conta_parcelas_quantidade"
                                    class="form-control"
                                    min="1"
                                    max="120"
                                    step="1"
                                    value="1"
                                    inputmode="numeric"
                                >
                            </div>

                            <div class="col-12 col-md-8">
                                <label
                                    for="gerar_conta_primeira_data_vencimento"
                                    class="form-label"
                                >
                                    Primeiro vencimento
                                </label>

                                <input
                                    type="date"
                                    id="gerar_conta_primeira_data_vencimento"
                                    class="form-control"
                                    value="{{ now()->format('Y-m-d') }}"
                                >
                            </div>

                            <div class="col-12">
                                <label
                                    for="gerar_conta_intervalo_parcelas"
                                    class="form-label"
                                >
                                    Intervalo entre parcelas
                                </label>

                                <select
                                    id="gerar_conta_intervalo_parcelas"
                                    class="form-select"
                                >
                                    <option value="30">
                                        A cada 30 dias
                                    </option>
                                    <option value="15">
                                        A cada 15 dias
                                    </option>
                                    <option value="7">
                                        A cada 7 dias
                                    </option>
                                    <option value="1">
                                        Diariamente
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="card bg-light border mt-4">
                            <div class="card-body">
                                <h2 class="h6 mb-3">
                                    <i class="bi bi-list-check"></i>
                                    Prévia das parcelas
                                </h2>

                                <div
                                    id="gerar-conta-preview-parcelas"
                                    class="small"
                                >
                                    <div class="d-flex justify-content-between border-bottom py-2">
                                        <span>
                                            Parcela 1
                                        </span>

                                        <strong>
                                            R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                                    <span class="fw-semibold">
                                        Total
                                    </span>

                                    <strong id="gerar-conta-total">
                                        R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div id="gerar-conta-parcelas-hidden"></div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-success"
                            id="botaoGerarContaCompra"
                        >
                            <i class="bi bi-check-circle"></i>
                            Gerar conta a pagar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')

@vite(['resources/js/compras/show.js'])

@endsection
