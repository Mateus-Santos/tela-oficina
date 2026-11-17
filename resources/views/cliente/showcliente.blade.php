@extends('layouts.layout')

@section('content')

<div class="container cadastro">

{{-- Cabeçalho --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

    <h1 class="mb-0">
        <i class="bi bi-person"></i>
        FICHA DO CLIENTE
    </h1>

    <div class="d-flex gap-1">

        <a
            href="{{ route('clientes.edit', $cliente->id) }}"
            class="btn btn-sm btn-primary"
            title="Editar cliente"
        >
            <i class="bi bi-pencil-square"></i>
            Editar
        </a>

        <a
            href="{{ route('clientes.index') }}"
            class="btn btn-sm btn-secondary"
            title="Voltar para clientes"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

    </div>

</div>

{{-- Resumo do cliente --}}
<div class="card mb-4">

    <div class="card-header">

        <i class="bi bi-person-circle"></i>
        <strong>RESUMO DO CLIENTE</strong>

    </div>

    <div class="card-body">

        <div class="row g-4 align-items-center">

            <div class="col-md-8">

                <small class="text-muted d-block">
                    Nome
                </small>

                <h3 class="mb-0">
                    {{ $cliente->pessoa?->nome ?? 'Nome não cadastrado' }}
                </h3>

            </div>

            <div class="col-md-4">

                <small class="text-muted d-block">
                    Pontos de Fidelidade
                </small>

                <div class="d-flex align-items-center gap-2">

                    <i class="bi bi-star-fill"></i>

                    <strong class="fs-4">
                        {{ $cliente->pontos ?? 0 }}
                    </strong>

                    <span class="text-muted">
                        pontos
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>

{{-- Veiculos vinculados: relacao oficial cliente_veiculo_cliente. --}}
@php
    // Consulta uma vez, fora de loops Blade, e carrega os dados relacionados.
    // Transferir o eager load para ClienteController::show em tarefa de Back-end.
    $cliente->loadMissing('veiculosClientes.veiculo.montadora');
    $veiculosVinculados = $cliente->veiculosClientes;
@endphp
<section class="card mb-4" aria-labelledby="fe05-veiculos-titulo" id="veiculos-vinculados">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 id="fe05-veiculos-titulo" class="h5 mb-0">
            <i class="bi bi-car-front me-1" aria-hidden="true"></i>
            Veículos vinculados ({{ $veiculosVinculados->count() }})
        </h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('veiculosclientes.create', ['cliente_id' => $cliente->id]) }}"
               class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Cadastrar veículo
            </a>
            <a href="{{ route('veiculosclientes.index') }}" class="btn btn-sm btn-outline-secondary">
                Consultar todos os veículos
            </a>
        </div>
    </div>
    <div class="card-body">
        @if ($veiculosVinculados->isEmpty())
            <p class="text-body-secondary mb-0" role="status">
                Nenhum veículo vinculado a este cliente. Use “Cadastrar veículo” para criar um registro e confirmar o responsável.
            </p>
        @else
            <div class="row g-3">
                @foreach ($veiculosVinculados as $veiculoVinculado)
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="border rounded p-3 h-100 d-flex flex-column gap-2">
                            <h3 class="h6 mb-0 text-break">
                                {{ $veiculoVinculado->veiculo?->montadora?->nome ?? 'Montadora não informada' }}
                                {{ $veiculoVinculado->veiculo?->nome ?? 'Modelo não informado' }}
                            </h3>
                            <div class="small text-body-secondary text-break">
                                Placa: <strong>{{ $veiculoVinculado->placa ?: 'Não informada' }}</strong>
                                <span aria-hidden="true">·</span>
                                Ano: {{ $veiculoVinculado->ano ?? 'Não informado' }}
                            </div>
                            <div class="mt-auto pt-2">
                                <a href="{{ route('veiculosclientes.edit', $veiculoVinculado->id) }}"
                                   class="btn btn-outline-primary btn-sm"
                                   aria-label="Consultar ou editar veículo placa {{ $veiculoVinculado->placa ?: 'não informada' }}">
                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                    Abrir cadastro do veículo
                                </a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Dados pessoais --}}
<div class="card mb-4">

    <div class="card-header">

        <i class="bi bi-person"></i>
        <strong>DADOS PESSOAIS</strong>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <div class="col-md-6">

                <small class="text-muted d-block">
                    Nome completo
                </small>

                <strong>
                    {{ $cliente->pessoa?->nome ?? 'Não informado' }}
                </strong>

            </div>

            <div class="col-md-3">

                <small class="text-muted d-block">
                    CPF
                </small>

                <strong>

                    @if ($cliente->pessoa?->cpf)

                        {{ preg_replace(
                            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
                            '$1.$2.$3-$4',
                            $cliente->pessoa->cpf
                        ) }}

                    @else

                        Não informado

                    @endif

                </strong>

            </div>

            <div class="col-md-3">

                <small class="text-muted d-block">
                    RG
                </small>

                <strong>
                    {{ $cliente->pessoa?->rg ?? 'Não informado' }}
                </strong>

            </div>

            <div class="col-md-3">

                <small class="text-muted d-block">
                    Data de nascimento
                </small>

                <strong>

                    @if ($cliente->pessoa?->data_nascimento)

                        {{ \Carbon\Carbon::parse($cliente->pessoa->data_nascimento)->format('d/m/Y') }}

                    @else

                        Não informado

                    @endif

                </strong>

            </div>

        </div>

    </div>

</div>

{{-- Contato --}}
<div class="card mb-4">

    <div class="card-header">

        <i class="bi bi-telephone"></i>
        <strong>CONTATO</strong>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <div class="col-md-4">

                <small class="text-muted d-block">
                    Telefone principal
                </small>

                <strong>

                    @if ($cliente->pessoa?->telefone_1)

                        {{ strlen($cliente->pessoa->telefone_1) === 11
                            ? preg_replace(
                                '/(\d{2})(\d{5})(\d{4})/',
                                '($1) $2-$3',
                                $cliente->pessoa->telefone_1
                            )
                            : preg_replace(
                                '/(\d{2})(\d{4})(\d{4})/',
                                '($1) $2-$3',
                                $cliente->pessoa->telefone_1
                            )
                        }}

                    @else

                        Não informado

                    @endif

                </strong>

            </div>

            <div class="col-md-4">

                <small class="text-muted d-block">
                    Telefone secundário
                </small>

                <strong>

                    @if ($cliente->pessoa?->telefone_2)

                        {{ strlen($cliente->pessoa->telefone_2) === 11
                            ? preg_replace(
                                '/(\d{2})(\d{5})(\d{4})/',
                                '($1) $2-$3',
                                $cliente->pessoa->telefone_2
                            )
                            : preg_replace(
                                '/(\d{2})(\d{4})(\d{4})/',
                                '($1) $2-$3',
                                $cliente->pessoa->telefone_2
                            )
                        }}

                    @else

                        Não informado

                    @endif

                </strong>

            </div>

            <div class="col-md-4">

                <small class="text-muted d-block">
                    E-mail
                </small>

                <strong>

                    {{ $cliente->pessoa?->user?->email ?? 'Não informado' }}

                </strong>

            </div>

        </div>

    </div>

</div>

{{-- Conta de acesso --}}
<div class="card mb-4">

    <div class="card-header">

        <i class="bi bi-person-lock"></i>
        <strong>CONTA DE ACESSO</strong>

    </div>

    <div class="card-body">

        @if ($cliente->pessoa?->user)

            <div class="row g-4">

                <div class="col-md-4">

                    <small class="text-muted d-block">
                        Status
                    </small>

                    <strong>
                        <i class="bi bi-check-circle"></i>
                        Conta cadastrada
                    </strong>

                </div>

                <div class="col-md-8">

                    <small class="text-muted d-block">
                        E-mail de acesso
                    </small>

                    <strong>
                        {{ $cliente->pessoa->user->email }}
                    </strong>

                </div>

            </div>

        @else

            <div class="text-muted">

                <i class="bi bi-info-circle"></i>

                Este cliente não possui uma conta de acesso ao sistema.

            </div>

        @endif

    </div>

</div>

{{-- Endereços --}}
<div class="card mb-4">

    <div class="card-header">

        <i class="bi bi-geo-alt"></i>
        <strong>ENDEREÇO(S)</strong>

    </div>

    <div class="card-body">

        @forelse ($cliente->pessoa?->enderecos ?? [] as $endereco)

            <div class="border rounded p-3 mb-3">

                <div class="row g-4">

                    <div class="col-md-3">

                        <small class="text-muted d-block">
                            CEP
                        </small>

                        <strong>
                            {{ $endereco->cep ?? 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-md-6">

                        <small class="text-muted d-block">
                            Rua
                        </small>

                        <strong>
                            {{ $endereco->rua ?? $endereco->endereco ?? 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-md-3">

                        <small class="text-muted d-block">
                            Número
                        </small>

                        <strong>
                            {{ $endereco->numero ?? 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-md-4">

                        <small class="text-muted d-block">
                            Bairro
                        </small>

                        <strong>
                            {{ $endereco->bairro ?? 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-md-5">

                        <small class="text-muted d-block">
                            Cidade
                        </small>

                        <strong>
                            {{ $endereco->cidade ?? 'Não informado' }}
                        </strong>

                    </div>

                    <div class="col-md-3">

                        <small class="text-muted d-block">
                            Estado
                        </small>

                        <strong>
                            {{ $endereco->estado ?? 'Não informado' }}
                        </strong>

                    </div>

                    @if ($endereco->ponto_referencia)

                        <div class="col-12">

                            <small class="text-muted d-block">
                                Ponto de referência
                            </small>

                            <strong>
                                {{ $endereco->ponto_referencia }}
                            </strong>

                        </div>

                    @endif

                </div>

            </div>

        @empty

            <div class="text-center text-muted py-3">

                <i class="bi bi-geo-alt fs-3 d-block mb-2"></i>

                Nenhum endereço cadastrado para este cliente.

            </div>

        @endforelse

    </div>

</div>


</div>

@endsection
