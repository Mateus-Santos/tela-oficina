@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="DETALHES DA CONTA A PAGAR"
        icon="bi-wallet2"
    />

    <div class="d-flex justify-content-end gap-2 mb-4">

        <a
            href="{{ route('contas-pagar.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar para contas
        </a>

        @if ($conta->status !== 'cancelada' && !$conta->estaPaga())
            <a
                href="{{ route('contas-pagar.edit', $conta) }}"
                class="btn btn-primary"
            >
                <i class="bi bi-pencil-square"></i>
                Editar conta
            </a>
        @endif

        @if (
            $conta->status !== 'cancelada'
            && (float) $conta->valor_pago <= 0
        )
            <button
                type="button"
                class="btn btn-danger"
                data-bs-toggle="modal"
                data-bs-target="#modalCancelarConta"
            >
                <i class="bi bi-x-circle"></i>
                Cancelar conta
            </button>
        @endif

    </div>

    @include('contas_pagar.show._dados')

    @include('contas_pagar.show._parcelas')

    @include('contas_pagar.show._anexos')

    @include('contas_pagar.show._pagamentos')

</div>

@include('contas_pagar.show._modais')

@vite('resources/js/conta-pagar.js')

@endsection
