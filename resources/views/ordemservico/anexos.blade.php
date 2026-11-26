@extends('layouts.layout')
@section('content')
<div class="container cadastro">
    <h1>Anexos da OS #{{ $ordemservico->id }}</h1>
    @include('anexo._gestao_operacional', [
        'urlStore' => route('ordemservicos.anexos.store', $ordemservico),
        'podeAlterar' => $ordemservico->status === 'aberta',
        'anexosVinculos' => $ordemservico->anexosVinculos,
    ])
    <a class="btn btn-secondary" href="{{ route('ordemservicos.index') }}">Voltar</a>
</div>
@endsection
