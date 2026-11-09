@extends('layouts.layout')

@section('content')
<section class="container cadastro py-3">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-box-seam"></i> Detalhes do produto</h1>
        <a href="{{ route('produtos.index') }}" class="btn btn-secondary">Voltar à lista</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-md-3">
                    @php
                        $imagem = $catalogo->imagens->sortByDesc('principal')->first();
                        $imagemUrl = $imagem?->url_original ?: $imagem?->url_thumb
                            ?: $catalogo->imagem_url ?: $catalogo->imagem_thumb_url;
                    @endphp
                    @if($imagemUrl)
                        <img src="{{ $imagemUrl }}" alt="{{ $dadosExibicao['descricao'] }}" class="img-fluid rounded" referrerpolicy="no-referrer">
                    @else
                        <div class="text-body-secondary">Sem imagem disponível</div>
                    @endif
                </div>
                <div class="col-12 col-md-9">
                    <p class="text-body-secondary mb-1">{{ $dadosExibicao['marca'] ?: 'Marca não informada' }}</p>
                    <h2 class="h4">{{ $dadosExibicao['nome'] }}</h2>
                    <p>Referência: <strong>{{ $dadosExibicao['referencia'] ?: 'Não informada' }}</strong></p>
                    <p>Código de barras: {{ $dadosExibicao['codigo_barras'] ?: 'Não informado' }}</p>
                    <div class="fs-4 fw-bold" @if($precoSugerido !== null) title="Preço de referência: R$ {{ number_format((float) $precoSugerido, 2, ',', '.') }}" @endif>
                        Preço de venda: {{ $dadosExibicao['preco'] !== null ? 'R$ '.number_format((float) $dadosExibicao['preco'], 2, ',', '.') : 'Sob consulta' }}
                    </div>
                    <p class="text-body-secondary small mt-2 mb-0">
                        Disponibilidade física na oficina: sem cadastro operacional.
                    </p>
                    @if($catalogo->estado?->estoque_observado !== null)
                        <p class="small mt-2 mb-0">
                            Estoque observado no fornecedor:
                            <strong>{{ $catalogo->estado->estoque_observado }}</strong>.
                            Disponibilidade sujeita a confirmação.
                        </p>
                    @endif
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalCatalogoSimilares"
                            data-url-similares="{{ route('produtos.catalogo.similares', $catalogo) }}">Similares</button>
                        @if(auth()->user()?->isAdmin())
                            <a class="btn btn-primary" href="{{ route('produtos.catalogo.edit', $catalogo) }}">Editar informações</a>
                            <form method="POST" action="{{ route('produtos.catalogo.habilitar', $catalogo) }}"
                                  onsubmit="return confirm('Habilitar este produto para venda? O sistema aproveitará o cadastro existente quando houver correspondência segura, sem alterar preço ou estoque local.');">
                                @csrf <button type="submit" class="btn btn-outline-primary">Habilitar para compras e notas</button>
                            </form>
                        @endif
                    </div>
                    @if($errors->has('catalogo'))<div class="alert alert-danger mt-2">{{ $errors->first('catalogo') }}</div>@endif
                </div>
            </div>
        </div>
    </div>

    @if($catalogo->atributos->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header"><strong>Características técnicas</strong></div>
            <div class="card-body"><dl class="row mb-0">
                @foreach($catalogo->atributos as $atributo)
                    <dt class="col-sm-4">{{ $atributo->chave_raw ?: $atributo->chave }}</dt>
                    <dd class="col-sm-8">{{ $atributo->valor ?: '—' }}</dd>
                @endforeach
            </dl></div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong><i class="bi bi-car-front"></i> Aplicações veiculares ({{ $aplicacoesTecnicas->total() }})</strong></div>
        <div class="card-body">
            @if($aplicacoesTecnicas->isEmpty())
                <p class="text-body-secondary mb-2">
                    Nenhuma aplicação veicular informada pela Sky.
                </p>
                <p class="text-body-secondary mb-0">
                    A ausência de aplicações não significa compatibilidade
                    universal. Verifique tecnicamente antes da utilização.
                </p>
            @else
                <div class="table-responsive"><table class="table table-striped table-hover align-middle">
                    <thead><tr><th>Montadora</th><th>Veículo</th><th>Mês/ano</th><th>Modelo</th><th>Motor</th><th>Combustível</th><th>Complemento</th></tr></thead>
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
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                {{ $aplicacoesTecnicas->links() }}
            @endif
        </div>
    </div>
</section>
@include('produto.partials.similares-modal')
@endsection
