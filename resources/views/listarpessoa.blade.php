@extends('layout')

@section('content')

<div class="container cadastro">
    <div class="row mb-3">
        <ul>
            @foreach($pessoas as $pessoa)
            <li>{{ $pessoa->nome }} TESTE</li>
            @endforeach
        </ul>
    </div>
</div>

@endsection