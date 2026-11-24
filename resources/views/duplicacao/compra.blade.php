@extends('layouts.layout')
@section('content')
<div class="container cadastro text-start">
    <h1>Duplicar Compra #{{ $compra->id }}</h1>
    <p class="alert alert-warning">Será criada uma NOVA Compra Pendente, sem chave NF-e, anexos, lancamento de estoque, conciliacao ou contas a pagar herdadas. Informe a nova NF antes de salvar.</p>
    <p>Itens copiados: {{ $compra->itens->count() }}.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('compras.duplicar.store', $compra) }}">
        @csrf
        <div class="mb-3"><label for="numero_nf" class="form-label">Numero da nova NF</label>
            <input name="numero_nf" id="numero_nf" class="form-control" maxlength="255" required value="{{ old('numero_nf') }}"></div>
        <label><input type="checkbox" name="confirmar" value="1" required> Confirmo que os dados fiscais e os vinculos deverao ser revisados.</label>
        <div class="mt-3"><button class="btn btn-primary" type="submit">Criar compra pendente</button>
        <a href="{{ route('compras.show', $compra) }}" class="btn btn-secondary">Voltar</a></div>
    </form>
</div>
@endsection
