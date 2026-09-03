@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="VISUALIZAR COMPRA"
        icon="bi-cart-check"
    />

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
                    {{ $compra->anexos->count() }}
                    {{ $compra->anexos->count() === 1 ? 'arquivo' : 'arquivos' }}
                </span>
            </div>

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
                            PDF, JPG, JPEG, PNG, WEBP ou XML — máximo de 20 MB.
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
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Enviar anexo
                        </button>
                    </div>
                </div>
            </form>

            @if ($compra->anexos->isNotEmpty())
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
                            @foreach ($compra->anexos as $anexo)
                                @php
                                    $tipoAnexo = match ($anexo->tipo) {
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
                                        {{ $anexo->observacoes ?: '-' }}
                                    </td>
                                    <td>
                                        {{ $anexo->created_at?->format('d/m/Y H:i') }}
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
                                                    action="{{ route('anexos.destroy', $anexo) }}"
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

                <h2 class="h5 mb-0">
                    <i class="bi bi-box-seam"></i>
                    Produtos da compra
                </h2>

                <span class="badge bg-secondary">
                    <i class="bi bi-boxes"></i>
                    {{ $compra->itens->count() }}
                    {{ $compra->itens->count() === 1 ? 'item' : 'itens' }}
                </span>

            </div>

            @if ($compra->itens->isNotEmpty())

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle mb-0">

                        <thead>

                            <tr>
                                <th scope="col">PRODUTO</th>
                                <th scope="col">DESCRIÇÃO</th>
                                <th scope="col">QUANTIDADE</th>
                                <th scope="col">CONFERIDA</th>
                                <th scope="col">VALOR UNITÁRIO</th>
                                <th scope="col">DESCONTO</th>
                                <th scope="col">TOTAL</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($compra->itens as $item)

                                <tr>

                                    <td>

                                        <strong>
                                            {{ $item->produto->nome ?? 'Produto não encontrado' }}
                                        </strong>

                                        @if ($item->produto?->codigo_fabricante)

                                            <br>

                                            <small class="text-muted">
                                                Código:
                                                {{ $item->produto->codigo_fabricante }}
                                            </small>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $item->descricao }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->quantidade, 3, ',', '.') }}
                                    </td>

                                    <td>

                                        @if ($item->quantidade_conferida !== null)

                                            @php
                                                $quantidade = (float) $item->quantidade;
                                                $conferida = (float) $item->quantidade_conferida;
                                                $conferenteIgual = abs($quantidade - $conferida) < 0.0001;
                                            @endphp

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
                                        R$
                                        {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        R$
                                        {{ number_format((float) $item->desconto, 2, ',', '.') }}
                                    </td>

                                    <td>

                                        <strong>
                                            R$
                                            {{ number_format((float) $item->valor_total, 2, ',', '.') }}
                                        </strong>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

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
                        R$
                        {{ number_format((float) $compra->valor_produtos, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Desconto
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->desconto, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Frete
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->frete, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Outras despesas
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->outras_despesas, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12">

                    <hr>

                    <div class="d-flex justify-content-end align-items-center gap-3">

                        <span class="text-muted">
                            Valor total:
                        </span>

                        <strong class="fs-4">
                            R$
                            {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
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


    {{-- ANEXOS --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h2 class="h5 mb-0">
                    <i class="bi bi-paperclip"></i>
                    Documentos e anexos
                </h2>

                <span class="badge bg-secondary">
                    Em breve
                </span>

            </div>

            <p class="text-muted mb-0 mt-3">

                <i class="bi bi-info-circle"></i>

                Os documentos da compra, como NF, XML, fotos e outros arquivos,
                serão disponibilizados nesta seção.

            </p>

        </div>

    </div>


    {{-- AÇÕES --}}
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-4">

        <a
            href="{{ route('compras.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        <div class="d-flex gap-2">

            @if (in_array($compra->status, ['pendente', 'conferindo'], true))

                <a
                    href="{{ route('compras.edit', $compra) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil"></i>
                    Editar
                </a>

            @endif

            @if ($compra->status === 'pendente')

                <button
                    type="button"
                    class="btn btn-success"
                    disabled
                    title="A aprovação será disponibilizada após a implementação da conferência"
                >
                    <i class="bi bi-check-circle"></i>
                    Aprovar
                </button>

            @endif

            @if ($compra->status === 'aprovada')

                <span class="text-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-1"></i>
                    Compra aprovada
                </span>

            @endif

        </div>

    </div>

</div>

@endsection
