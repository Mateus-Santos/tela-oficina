@extends('layouts.layout')
@section('content')
<div class="container cadastro text-start">
    <h1>Duplicar Nota #{{ $nota->id }}</h1>
    <p class="alert alert-warning">A copia sera uma nova Nota aberta, sem pagamentos ou anexos. Cada OS utilizada pela Nota sera recriada como OS aberta independente, com novo codigo. Confirme apenas se deseja criar tambem essas OS.</p>
    <p>Itens da origem: {{ $nota->itens->count() }}. Total de referencia: R$ {{ number_format((float) $nota->total, 2, ',', '.') }}.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('notas.duplicar.store', $nota) }}">
        @csrf
        <label><input type="checkbox" name="confirmar" value="1" required> Confirmo que quero criar outra Nota, sem reutilizar seus vinculos financeiros.</label>
        <div class="mt-3"><button class="btn btn-primary" type="submit">Criar nova Nota</button>
        <a href="{{ route('notas.show', $nota) }}" class="btn btn-secondary">Voltar</a></div>
    </form>
</div>
@endsection
