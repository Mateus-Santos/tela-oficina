@extends('layouts.layout')

@section('content')

@php
    $possuiCatalogo =
        $dadosCatalogo['possui_catalogo']
        ?? false;

    $fonteCatalogo =
        $dadosCatalogo['fonte']
        ?? 'Catálogo externo';

    $precoVenda =
        (float) (
            $dadosCatalogo['preco_venda']
            ?? $produto->preco_uni
        );

    $origemPreco =
        $dadosCatalogo['origem_preco']
        ?? 'Cadastro Jaime';

    $estoqueFornecedor =
        $dadosCatalogo['estoque_observado']
        ?? null;

    $ultimaColeta =
        $dadosCatalogo['ultima_coleta_em']
        ?? null;

    $galeria = collect();

    foreach ($produto->imagens as $imagem) {
        if (! $imagem->caminho) {
            continue;
        }

        $galeria->push([
            'url' => asset(
                'storage/'.$imagem->caminho
            ),
            'origem' => 'Jaime',
            'remota' => false,
        ]);
    }

    if ($catalogoPrincipal) {
        foreach (
            $catalogoPrincipal
                ->imagens
                ->sortByDesc(
                    fn ($imagem) =>
                        (int) $imagem->principal
                )
            as $imagem
        ) {
            $url =
                $imagem->url_original
                ?: $imagem->url_thumb;

            if (! $url) {
                continue;
            }

            $galeria->push([
                'url' => $url,
                'origem' => $fonteCatalogo,
                'remota' => true,
            ]);
        }

        $fallback =
            $catalogoPrincipal->imagem_url
            ?: $catalogoPrincipal
                ->imagem_thumb_url;

        if (
            $fallback
            && ! $galeria->contains(
                'url',
                $fallback
            )
        ) {
            $galeria->push([
                'url' => $fallback,
                'origem' => $fonteCatalogo,
                'remota' => true,
            ]);
        }
    }

    $galeria = $galeria
        ->unique('url')
        ->values();

    $atributos =
        $catalogoPrincipal?->atributos
        ?? collect();
@endphp

<section class="container cadastro">

    {{-- CABEÇALHO --}}
    <div
        class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"
    >

        <div>

            <div
                class="d-flex flex-wrap align-items-center gap-2"
            >

                <h1 class="mb-0">
                    <i class="bi bi-box-seam"></i>
                    VISUALIZAR PRODUTO
                </h1>

                @if($produto->status)

                    <span class="badge text-bg-success">
                        Ativo
                    </span>

                @else

                    <span class="badge text-bg-secondary">
                        Inativo
                    </span>

                @endif

                @if($possuiCatalogo)

                    <span class="badge text-bg-info">
                        <i class="bi bi-cloud-arrow-down me-1"></i>
                        {{ $fonteCatalogo }}
                    </span>

                @endif

            </div>

            <div class="text-body-secondary mt-1">
                Código {{ $produto->codigo }}
                · Ref. {{ $produto->codigo_fabricante }}
                · ID #{{ $produto->id }}
            </div>

        </div>

        <div class="d-flex gap-2">

            <a
                href="{{ route('produtos.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>

            @if(auth()->user()?->isAdmin())

                <a
                    href="{{ route(
                        'produtos.edit',
                        $produto->id
                    ) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil"></i>
                    Editar
                </a>

            @endif

        </div>

    </div>

    {{-- PRINCIPAL --}}
    <div class="card mb-4">

        <div class="card-body">

            <div class="row g-4">

                {{-- GALERIA --}}
                <div class="col-12 col-lg-4">

                    @if($galeria->isNotEmpty())

                        <div
                            id="produtoImagensCarousel"
                            class="carousel slide border rounded bg-light overflow-hidden"
                        >

                            <div class="carousel-inner">

                                @foreach($galeria as $imagem)

                                    <div
                                        class="carousel-item {{ $loop->first ? 'active' : '' }}"
                                    >

                                        <div class="position-relative bg-white">

                                            <img
                                                src="{{ $imagem['url'] }}"
                                                alt="{{ $produto->nome }}"
                                                class="d-block w-100"
                                                loading="lazy"
                                                @if($imagem['remota'])
                                                    referrerpolicy="no-referrer"
                                                @endif
                                                style="
                                                    height:340px;
                                                    object-fit:contain;
                                                    padding:16px;
                                                "
                                            >

                                            <span
                                                class="badge text-bg-dark position-absolute bottom-0 end-0 m-2"
                                            >
                                                {{ $imagem['origem'] }}
                                            </span>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                            @if($galeria->count() > 1)

                                <button
                                    class="carousel-control-prev"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="prev"
                                >
                                    <span
                                        class="carousel-control-prev-icon bg-dark rounded-circle p-3"
                                    ></span>
                                </button>

                                <button
                                    class="carousel-control-next"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="next"
                                >
                                    <span
                                        class="carousel-control-next-icon bg-dark rounded-circle p-3"
                                    ></span>
                                </button>

                            @endif

                        </div>

                    @else

                        <div
                            class="border rounded bg-light d-flex flex-column align-items-center justify-content-center text-body-secondary"
                            style="height:340px;"
                        >
                            <i class="bi bi-image fs-1"></i>
                            <strong class="mt-2">
                                Sem imagens
                            </strong>
                        </div>

                    @endif

                </div>

                {{-- IDENTIFICAÇÃO --}}
                <div class="col-12 col-lg-8">

                    <div
                        class="d-flex align-items-center gap-3 mb-4"
                    >

                        <div
                            class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden"
                            style="
                                width:64px;
                                height:64px;
                                flex:0 0 64px;
                            "
                        >

                            @if(
                                $produto
                                    ->marcaRelacionada
                                    ?->logo_path
                            )

                                <img
                                    src="{{ asset(
                                        'storage/'
                                        .$produto
                                            ->marcaRelacionada
                                            ->logo_path
                                    ) }}"
                                    style="
                                        width:100%;
                                        height:100%;
                                        object-fit:contain;
                                        padding:6px;
                                    "
                                >

                            @elseif(
                                $produto
                                    ->marcaRelacionada
                                    ?->logo_url
                            )

                                <img
                                    src="{{ $produto->marcaRelacionada->logo_url }}"
                                    style="
                                        width:100%;
                                        height:100%;
                                        object-fit:contain;
                                        padding:6px;
                                    "
                                >

                            @else

                                <i class="bi bi-tag fs-4"></i>

                            @endif

                        </div>

                        <div>

                            <div
                                class="text-body-secondary small fw-semibold text-uppercase"
                            >
                                {{ $produto
                                    ->marcaRelacionada
                                    ?->nome
                                    ?: 'Marca não informada' }}
                            </div>

                            <h2 class="h4 mb-0">
                                {{ $produto->nome }}
                            </h2>

                        </div>

                    </div>

                    <div class="row g-3">

                        <div class="col-12 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-body-secondary d-block">
                                    Código do produto
                                </small>

                                <strong class="fs-5 font-monospace">
                                    {{ $produto->codigo }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-12 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-body-secondary d-block">
                                    Código do fabricante
                                </small>

                                <strong class="fs-5">
                                    {{ $produto->codigo_fabricante
                                        ?: 'Não informado' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-12 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-body-secondary d-block">
                                    Código de barras
                                </small>

                                <strong class="font-monospace">
                                    {{ $produto->codigo_barras
                                        ?: 'Não informado' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-12">

                            <div class="border rounded bg-light p-3">

                                <small
                                    class="text-body-secondary d-block mb-1"
                                >
                                    Descrição
                                </small>

                                <div
                                    class="overflow-auto text-break"
                                    style="
                                        max-height: 180px;
                                        white-space: pre-line;
                                    "
                                >
                                    {{ $produto->descricao
                                        ?: 'Não informada' }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- RESUMO --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100">
                <div class="card-body">

                    <small class="text-body-secondary">
                        <i class="bi bi-tag"></i>
                        Preço de venda
                    </small>

                    <div class="fs-4 fw-bold mt-1"
                         @if(($dadosCatalogo['preco_sugerido'] ?? null) !== null)
                           title="Preço de referência: R$ {{ number_format((float) $dadosCatalogo['preco_sugerido'], 2, ',', '.') }}"
                         @endif>
                        R$ {{ number_format(
                            $precoVenda,
                            2,
                            ',',
                            '.'
                        ) }}
                    </div>



                </div>
            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100">
                <div class="card-body">

                    <small class="text-body-secondary">
                        <i class="bi bi-house-gear"></i>
                        Estoque Jaime
                    </small>

                    <div class="fs-4 fw-bold mt-1">
                        {{ $produto->quantidade }}
                    </div>

                    <small class="text-body-secondary">
                        Estoque físico
                    </small>

                </div>
            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100">
                <div class="card-body">

                    <small class="text-body-secondary">
                        <i class="bi bi-truck"></i>
                        Estoque fornecedor
                    </small>

                    <div class="fs-4 fw-bold mt-1">
                        {{ $estoqueFornecedor !== null
                            ? $estoqueFornecedor
                            : '—' }}
                    </div>

                    <small class="text-body-secondary">
                        {{ $possuiCatalogo
                            ? $fonteCatalogo
                            : 'Sem catálogo' }}
                    </small>

                </div>
            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100">
                <div class="card-body">

                    <small class="text-body-secondary">
                        <i class="bi bi-boxes"></i>
                        Estoque mínimo
                    </small>

                    <div class="fs-4 fw-bold mt-1">
                        {{ $produto->estoque_minimo }}
                    </div>

                    <small class="text-body-secondary">
                        Cadastro Jaime
                    </small>

                </div>
            </div>

        </div>

    </div>

    {{-- DADOS DO CATÁLOGO --}}
    @if($possuiCatalogo && $catalogoPrincipal)

        <div class="card mb-4">

            <div
                class="card-header d-flex justify-content-between align-items-center gap-2"
            >

                <strong>
                    <i class="bi bi-cloud-arrow-down"></i>
                    Dados externos
                </strong>

                <span class="badge text-bg-info">
                    {{ $fonteCatalogo }}
                </span>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <small class="text-body-secondary d-block">
                            Código externo
                        </small>

                        <strong>
                            {{ $dadosCatalogo['codigo_externo']
                                ?: 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-12 col-md-4">

                        <small class="text-body-secondary d-block">
                            Estoque observado
                        </small>

                        <strong>
                            {{ $estoqueFornecedor !== null
                                ? $estoqueFornecedor
                                : 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-12 col-md-4">

                        <small class="text-body-secondary d-block">
                            Última coleta
                        </small>

                        <strong>
                            {{ $ultimaColeta
                                ? $ultimaColeta
                                    ->timezone(
                                        config('app.timezone')
                                    )
                                    ->format(
                                        'd/m/Y H:i'
                                    )
                                : 'Não informada' }}
                        </strong>

                    </div>

                    @if(
                        $dadosCatalogo['promocao']
                        ?? false
                    )

                        <div class="col-12">

                            <div class="alert alert-warning mb-0">

                                <i class="bi bi-percent me-1"></i>

                                Promoção detectada

                                @if(
                                    !empty(
                                        $dadosCatalogo[
                                            'promocao_texto'
                                        ]
                                    )
                                )
                                    —
                                    {{ $dadosCatalogo[
                                        'promocao_texto'
                                    ] }}
                                @endif

                            </div>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    @endif

    {{-- ATRIBUTOS --}}
    @if($atributos->isNotEmpty())

        <div class="card mb-4">

            <div class="card-header">
                <strong>
                    <i class="bi bi-sliders"></i>
                    Atributos técnicos
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    @foreach(
                        $atributos->sortBy('chave')
                        as $atributo
                    )

                        <div class="col-12 col-md-6 col-xl-4">

                            <div class="border rounded p-3 h-100">

                                <small class="text-body-secondary d-block">
                                    {{ $atributo->chave_raw
                                        ?: $atributo->chave }}
                                </small>

                                <strong>
                                    {{ $atributo->valor
                                        ?: 'Não informado' }}
                                </strong>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </div>

    @endif

    @if($catalogoPrincipal)
        @php
            $complementosForge = array_filter([
                'NCM do fornecedor' => $catalogoPrincipal->ncm,
                'Unidade do fornecedor' => $catalogoPrincipal->unidade,
                'Quantidade por jogo' => $catalogoPrincipal->quantidade_jogo,
                'Quantidade por caixa' => $catalogoPrincipal->quantidade_caixa,
                'Codigo auxiliar' => $catalogoPrincipal->codigo_auxiliar,
                'Origem informada' => $catalogoPrincipal->origem_raw,
            ], fn ($valor) => $valor !== null && trim((string) $valor) !== '');
        @endphp
        @if($complementosForge !== [])
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informacoes complementares do Forge</strong>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @foreach($complementosForge as $rotulo => $valor)
                            <dt class="col-sm-4">{{ $rotulo }}</dt>
                            <dd class="col-sm-8">{{ $valor }}</dd>
                        @endforeach
                    </dl>
                    <div class="small text-muted">
                        Dados informativos da fonte externa; os dados
                        fiscais e comerciais locais permanecem independentes.
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- DADOS FISCAIS --}}
    <div class="card mb-4">

        <div class="card-header">
            <strong>
                <i class="bi bi-receipt"></i>
                Dados fiscais e comerciais
            </strong>
        </div>

        <div class="card-body">

            <div class="row g-3">

                @foreach([
                    'NCM' => $produto->ncm,
                    'CEST' => $produto->cest,
                    'EX-TIPI' => $produto->ex_tipi,
                    'Origem' => $produto->origem_mercadoria,
                    'Unidade comercial' => $produto->unidade_comercial,
                    'Unidade tributável' => $produto->unidade_tributavel,
                ] as $rotulo => $valor)

                    <div class="col-6 col-md-4">

                        <small class="text-body-secondary d-block">
                            {{ $rotulo }}
                        </small>

                        <strong>
                            {{ $valor !== null && $valor !== ''
                                ? $valor
                                : 'Não informado' }}
                        </strong>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    {{-- FORNECEDOR INTERNO --}}
    <div class="card mb-4">

        <div class="card-header">
            <strong>
                <i class="bi bi-building"></i>
                Fornecedor cadastrado no Jaime
            </strong>
        </div>

        <div class="card-body">

            @if($produto->fornecedor)

                <div class="row g-3">

                    <div class="col-12 col-md-6">

                        <small class="text-body-secondary d-block">
                            Nome
                        </small>

                        <strong>
                            {{ $produto->fornecedor->nome }}
                        </strong>

                    </div>

                    <div class="col-12 col-md-6">

                        <small class="text-body-secondary d-block">
                            CNPJ
                        </small>

                        <strong>
                            {{ $produto->fornecedor->cnpj
                                ?: 'Não informado' }}
                        </strong>

                    </div>

                </div>

            @else

                <span class="text-body-secondary">
                    Nenhum fornecedor interno vinculado.
                </span>

            @endif

        </div>

    </div>

    {{-- APLICAÇÕES JAIME --}}
    <div class="card mb-4">

        <div class="card-header">
            <strong>
                <i class="bi bi-car-front"></i>
                Aplicações veiculares
            </strong>
        </div>

        <div class="card-body">

            @if($produto->veiculos->isNotEmpty())

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover align-middle mb-0"
                    >

                        <thead>
                            <tr>
                                <th>Montadora</th>
                                <th>Veículo</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach(
                                $produto->veiculos
                                as $veiculo
                            )

                                <tr>

                                    <td>
                                        {{ $veiculo
                                            ->montadora
                                            ->nome
                                            ?? 'Não informado' }}
                                    </td>

                                    <td>
                                        {{ $veiculo->nome }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <span class="text-body-secondary">
                    Nenhuma aplicação manual cadastrada.
                </span>

            @endif

        </div>

    </div>

    @if($catalogoPrincipal)
        <div class="mb-3">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalCatalogoSimilares"
                data-url-similares="{{ route('produtos.catalogo.similares', $catalogoPrincipal) }}">Ver produtos similares</button>
        </div>
    @endif

    @if($catalogoPrincipal && $aplicacoesTecnicas?->total() === 0)
        <div class="alert alert-warning mb-4">
            <strong>Nenhuma aplicação veicular informada pelo Fornecedor S.</strong>
            Este produto pode ser cadastrado e editado, mas sua
            compatibilidade com veículos não foi confirmada pela fonte.
            Verifique a aplicação correta antes da utilização.
        </div>
    @endif

    {{-- Aplicacoes tecnicas: apenas leitura; nao altera as aplicacoes manuais. --}}
    @if($aplicacoesTecnicas && $aplicacoesTecnicas->total() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <strong><i class="bi bi-car-front"></i> Compatibilidades detalhadas</strong>
                <span class="text-body-secondary ms-2">({{ $aplicacoesTecnicas->total() }})</span>
                @if($aplicacoesPendentes > 0)
                    <span class="badge text-bg-warning ms-2">
                        {{ $aplicacoesPendentes }} pendente(s) de identificação
                    </span>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead><tr>
                            <th>Montadora</th><th>Veículo</th><th>Mês/ano</th>
                            <th>Modelo</th><th>Motor</th><th>Combustível</th>
                            <th>Complemento</th><th>Identificação</th>
                        </tr></thead>
                        <tbody>
                            @foreach($aplicacoesTecnicas as $aplicacao)
                                <tr>
                                    <td>{{ $aplicacao->montadora_raw ?: '—' }}</td>
                                    <td>{{ $aplicacao->veiculo_raw ?: '—' }}</td>
                                    <td>{{ $aplicacao->mes_ano ?: '—' }}</td>
                                    <td>{{ $aplicacao->modelo ?: '—' }}</td>
                                    <td>{{ collect([$aplicacao->motor, $aplicacao->comp, $aplicacao->valvulas ? $aplicacao->valvulas.'V' : null, $aplicacao->cilindros ? $aplicacao->cilindros.' cil.' : null])->filter()->implode(' · ') ?: '—' }}</td>
                                    <td>{{ $aplicacao->combustivel ?: '—' }}</td>
                                    <td>{{ $aplicacao->complemento ?: '—' }}</td>
                                    <td>
                                        @if(
                                            $aplicacao->produtoAplicacao?->veiculo_id
                                            && $aplicacao->produtoAplicacao?->status_resolucao === 'resolvida'
                                            && (int) $aplicacao->produtoAplicacao?->produto_id === (int) $produto->id
                                        )
                                            <span class="badge text-bg-success">Veículo identificado</span>
                                        @else
                                            <span class="badge text-bg-warning">Revisão necessária</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $aplicacoesTecnicas->links() }}
            </div>
        </div>
    @endif

</section>

@include('produto.partials.similares-modal')
@endsection
