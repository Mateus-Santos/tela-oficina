@extends('layouts.layout')
@section('content')
<section class="container cadastro py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Editar informações do produto</h1>
        <a class="btn btn-secondary" href="{{ route('produtos.catalogo.show', $catalogo) }}">Voltar</a>
    </div>
    <p class="text-body-secondary">As informações que preencher aqui prevalecem nas próximas importações. Deixe vazio para utilizar o catálogo de referência. Isto não altera estoque nem movimentações.</p>
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
    @endif
    <form action="{{ route('produtos.catalogo.update', $catalogo) }}" method="POST" class="card">
        @csrf @method('PUT')
        <div class="card-body row g-3">
            @foreach(['nome' => 'Nome', 'marca' => 'Marca', 'codigo_fabricante' => 'Código do fabricante', 'codigo_barras' => 'Código de barras'] as $campo => $rotulo)
                <div class="col-md-6"><label class="form-label" for="{{ $campo }}">{{ $rotulo }}</label>
                    <input id="{{ $campo }}" name="{{ $campo }}" class="form-control" value="{{ old($campo, $catalogo->edicao?->$campo) }}" placeholder="{{ $dados[match($campo) {'codigo_fabricante' => 'referencia', default => $campo}] ?? '' }}">
                </div>
            @endforeach
            <div class="col-12"><label class="form-label" for="descricao">Descrição personalizada</label>
                <textarea id="descricao" name="descricao" rows="4" class="form-control" placeholder="{{ $catalogo->descricao }}">{{ old('descricao', $catalogo->edicao?->descricao) }}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="preco_venda">Preço de venda definido pela oficina (R$)</label>
                <input id="preco_venda" name="preco_venda" type="number" step="0.01" min="0" class="form-control" value="{{ old('preco_venda', $catalogo->edicao?->preco_venda) }}" placeholder="{{ $dados['sugerido'] }}">
                <div class="form-text">Vazio: usa o preço sugerido calculado. Preenchido: a escolha manual prevalece.</div></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Salvar personalizações</button></div>
        </div>
    </form>
</section>
@endsection
