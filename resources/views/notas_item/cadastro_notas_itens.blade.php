@extends('layouts.layout')

@section('content')

<div class="container cadastro feux-notas-lote02 feux-notas-formulario feux-workspace-redesign feux-moderno">

    <header class="feux-notas-cabecalho">
        <div class="feux-notas-cabecalho__texto">
            <p class="feux-notas-eyebrow mb-1">Gestão de notas</p>
            <h1 class="feux-notas-cabecalho__titulo">Nova nota</h1>
            <p class="feux-notas-cabecalho__descricao">Defina o cliente, inclua os itens e confira os valores antes de salvar.</p>
        </div>
        <a class="btn btn-outline-dark" href="{{ route('notas.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar às notas
        </a>
    </header>
    @if ($errors->any())
        <section class="feux-notas-avisos" aria-label="Erros de validação da nota" role="alert" tabindex="-1">
            <div class="feux-notas-avisos__cabecalho">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <div>
                    <h2 class="h5 mb-1">Revise os campos da nota</h2>
                    <p class="mb-0">Confira as mensagens abaixo antes de salvar novamente.</p>
                </div>
            </div>
            <ul class="feux-notas-avisos__lista">
                @foreach ($errors->getMessages() as $feuxCampo => $feuxMensagens)
                    @php
                        $feuxCampoTexto = (string) $feuxCampo;
                        $feuxSecao = str_starts_with($feuxCampoTexto, 'itens.') || $feuxCampoTexto === 'itens'
                            ? 'itens'
                            : (in_array($feuxCampoTexto, ['cliente_id', 'veiculo_cliente_id', 'km', 'km_proxima_troca_oleo', 'km_diferenca_troca_oleo'], true)
                                ? 'identificacao'
                                : (str_contains($feuxCampoTexto, 'desconto') ? 'descontos' : 'salvar'));
                    @endphp
                    @foreach ($feuxMensagens as $feuxMensagem)
                        <li>
                            <span>{{ $feuxMensagem }}</span>
                            <a href="#feux-notas-{{ $feuxSecao }}" class="feux-notas-avisos__atalho">Ir à seção</a>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </section>
    @endif

    <nav class="feux-notas-passos" aria-label="Navegação do formulário de notas">
        <a href="#feux-notas-identificacao"><span>01</span> Identificação</a>
        <a href="#feux-notas-resumo"><span>02</span> Resumo</a>
        <a href="#feux-notas-itens"><span>03</span> Itens <small id="feux-notas-contador" aria-live="polite"></small></a>
        <a href="#feux-notas-descontos"><span>04</span> Descontos</a>
        <a href="#feux-notas-salvar"><span>05</span> Salvar</a>
    </nav>

    <p class="feux-notas-envio-status visually-hidden" role="status" aria-live="polite"></p>

    <div class="feux-notas-workspace__grid">
        <div class="feux-notas-workspace__editor">
    <form data-feux-form="true"
        action="{{ route('notasitem.store') }}"
        method="POST"
        id="form-os-itens"
    >

        @csrf

        @include('notas_item._form')

    </form>
        </div>
        @include('notas_item.partials._workspace_sidebar')
    </div>

</div>

{{-- Modais ficam fora do .cadastro --}}
@include('notas_item.modal._modal_adicionar_item')
@include('notas_item.modal._modal_descontos')

@endsection


@section('scripts')

    @vite([
        'resources/js/notaitem/form.js',
        'resources/js/notaitem/itens.js',
        'resources/js/notaitem/descontos.js',
        'resources/js/notaitem/feux-notas-lote02.js',
        'resources/js/notaitem/feux-notas-lote04.js',
        'resources/js/notaitem/feux-notas-lote05.js',
        'resources/js/notaitem/feux-notas-lote06.js',
        'resources/js/notaitem/feux-notas-workspace.js',
        'resources/js/notaitem/feux-notas-itens-operacao.js'
    ])

@endsection
