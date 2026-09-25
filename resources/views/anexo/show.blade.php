@extends('layouts.layout')

@section('content')

@php
    $vinculo = $anexo->vinculos->first();
    $origem = $vinculo?->vinculavel;

    $ehImagem = str_starts_with($anexo->mime_type ?? '', 'image/');
    $ehPdf = ($anexo->mime_type ?? '') === 'application/pdf';
    $possuiPreview = $ehImagem || $ehPdf;

    $tamanhoFormatado = match (true) {
        $anexo->tamanho >= 1048576 => number_format($anexo->tamanho / 1048576, 2, ',', '.') . ' MB',
        $anexo->tamanho >= 1024 => number_format($anexo->tamanho / 1024, 1, ',', '.') . ' KB',
        default => $anexo->tamanho . ' bytes',
    };

    $tipos = [
        'nf' => 'Nota fiscal',
        'nf_xml' => 'NF-e XML',
        'foto' => 'Foto',
        'comprovante' => 'Comprovante',
        'boleto' => 'Boleto',
        'contrato' => 'Contrato',
        'orcamento' => 'Orçamento',
        'conta_luz' => 'Conta de luz',
        'conta_agua' => 'Conta de água',
        'conta_telefone' => 'Conta de telefone',
        'recibo' => 'Recibo',
        'outro' => 'Outro',
    ];

    $tipo = $tipos[$vinculo?->tipo] ?? ($vinculo?->tipo ?: 'Não informado');

    $origemNome = 'Não identificada';
    $origemUrl = null;

    if ($origem instanceof \App\Models\Compra) {
        $origemNome = 'Compra #' . str_pad($origem->id, 6, '0', STR_PAD_LEFT);
        $origemUrl = route('compras.show', $origem);
    } elseif ($origem instanceof \App\Models\ContaPagar) {
        $origemNome = 'Conta a pagar #' . str_pad($origem->id, 6, '0', STR_PAD_LEFT);
        $origemUrl = route('contas-pagar.show', $origem);
    }
@endphp

<div class="container cadastro">

    <x-list-header
        title="VISUALIZAR ANEXO"
        icon="bi-file-earmark"
    />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
        @if ($origemUrl)
            <a
                href="{{ $origemUrl }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>
        @else
            <button
                type="button"
                class="btn btn-secondary"
                onclick="history.back()"
            >
                <i class="bi bi-arrow-left"></i>
                Voltar
            </button>
        @endif

        <a
            href="{{ route('anexos.download', $anexo) }}"
            class="btn btn-primary"
        >
            <i class="bi bi-download"></i>
            Baixar arquivo
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-info-circle"></i>
            <strong>Informações do anexo</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="text-muted small">
                        Nome do arquivo
                    </div>

                    <div class="fw-semibold text-break">
                        {{ $anexo->nome_original }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">
                        Tipo
                    </div>

                    <div class="fw-semibold">
                        {{ $tipo }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">
                        Tamanho
                    </div>

                    <div class="fw-semibold">
                        {{ $tamanhoFormatado }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">
                        Tipo do arquivo
                    </div>

                    <div class="fw-semibold text-break">
                        {{ $anexo->mime_type ?: 'Não informado' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">
                        Data de envio
                    </div>

                    <div class="fw-semibold">
                        {{ $anexo->created_at?->format('d/m/Y H:i') ?? 'Não informada' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">
                        Origem
                    </div>

                    <div class="fw-semibold">
                        @if ($origemUrl)
                            <a
                                href="{{ $origemUrl }}"
                                class="text-decoration-none"
                            >
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                {{ $origemNome }}
                            </a>
                        @else
                            {{ $origemNome }}
                        @endif
                    </div>
                </div>

                @if ($vinculo?->observacoes)
                    <div class="col-12">
                        <div class="text-muted small">
                            Observações
                        </div>

                        <div class="fw-semibold text-break">
                            {{ $vinculo->observacoes }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-eye"></i>
                <strong>Visualização</strong>
            </div>

            @if ($possuiPreview)
                <a
                    href="{{ route('anexos.preview', $anexo) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-sm btn-outline-secondary"
                >
                    <i class="bi bi-box-arrow-up-right"></i>
                    Abrir em nova aba
                </a>
            @endif
        </div>

        <div class="card-body">

            @if ($ehPdf)
                <iframe
                    src="{{ route('anexos.preview', $anexo) }}"
                    title="{{ $anexo->nome_original }}"
                    class="w-100 border rounded"
                    style="height: 75vh; min-height: 600px;"
                ></iframe>

            @elseif ($ehImagem)
                <div class="text-center">
                    <img
                        src="{{ route('anexos.preview', $anexo) }}"
                        alt="{{ $anexo->nome_original }}"
                        class="img-fluid rounded border"
                        style="max-height: 75vh;"
                    >
                </div>

            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-file-earmark fs-1 d-block mb-3"></i>

                    <h5 class="mb-2">
                        Pré-visualização indisponível
                    </h5>

                    <p class="mb-4">
                        Este tipo de arquivo não possui visualização disponível no sistema.
                    </p>

                    <a
                        href="{{ route('anexos.download', $anexo) }}"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-download"></i>
                        Baixar arquivo
                    </a>
                </div>
            @endif

        </div>
    </div>

</div>

@endsection
