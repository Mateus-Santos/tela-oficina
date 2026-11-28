@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/css/notas/vue.css')
@endpush

@section('content')
<main class="sos-vue-notas-shell" aria-label="Central de Notas">
    <div id="sos-notas-vue" data-api="{{ url('/notas/painel/api') }}" data-base="{{ url('/notas/painel') }}" data-attachments-template="{{ route('notas.anexos.store', ['nota' => '__NOTA_ID__']) }}" data-create-cliente="{{ route('clientes.create') }}" data-create-veiculo="{{ route('veiculosclientes.create') }}" data-create-produto="{{ route('produtos.create') }}" data-create-legacy="{{ route('notasitem.create') }}">
        <div class="sos-vue-boot" role="status"><span class="spinner-border spinner-border-sm"></span> Iniciando Central de Notas…</div>
    </div>
</main>
@endsection

@section('scripts')
    @vite('resources/js/notas/vue/main.js')
@endsection
