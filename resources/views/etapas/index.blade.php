@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="ETAPAS DO ATENDIMENTO"
        icon="bi-signpost-split"
    />

    @if(session('success'))

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            {{ session('success') }}

        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>

            <strong>
                Não foi possível salvar.
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $erro)

                    <li>
                        {{ $erro }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif

    <div class="card mb-4">

        <div class="card-header">

            <strong>
                <i class="bi bi-plus-circle"></i>
                Nova etapa
            </strong>

        </div>

        <div class="card-body">

            <form
                method="POST"
                action="{{ route('etapas.store') }}"
            >

                @csrf

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-lg-3">

                        <label
                            for="nome"
                            class="form-label"
                        >
                            Nome
                        </label>

                        <input
                            type="text"
                            name="nome"
                            id="nome"
                            class="form-control"
                            value="{{ old('nome') }}"
                            maxlength="100"
                            required
                        >

                    </div>

                    <div class="col-12 col-lg-3">

                        <label
                            for="descricao"
                            class="form-label"
                        >
                            Descrição
                        </label>

                        <input
                            type="text"
                            name="descricao"
                            id="descricao"
                            class="form-control"
                            value="{{ old('descricao') }}"
                            maxlength="255"
                        >

                    </div>

                    <div class="col-6 col-lg-2">

                        <label
                            for="cor"
                            class="form-label"
                        >
                            Cor
                        </label>

                        <select
                            name="cor"
                            id="cor"
                            class="form-select"
                            required
                        >

                            <option value="secondary">
                                Cinza
                            </option>

                            <option value="primary">
                                Azul
                            </option>

                            <option value="success">
                                Verde
                            </option>

                            <option value="warning">
                                Amarelo
                            </option>

                            <option value="danger">
                                Vermelho
                            </option>

                            <option value="info">
                                Azul claro
                            </option>

                            <option value="dark">
                                Escuro
                            </option>

                        </select>

                    </div>

                    <div class="col-6 col-lg-1">

                        <label
                            for="ordem"
                            class="form-label"
                        >
                            Ordem
                        </label>

                        <input
                            type="number"
                            name="ordem"
                            id="ordem"
                            class="form-control"
                            value="{{ old('ordem', 80) }}"
                            min="1"
                            max="9999"
                            required
                        >

                    </div>

                    <div class="col-12 col-lg-2">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                name="aplica_nota"
                                id="nova-aplica-nota"
                                value="1"
                                class="form-check-input"
                                checked
                            >

                            <label
                                for="nova-aplica-nota"
                                class="form-check-label"
                            >
                                Notas
                            </label>

                        </div>

                        <div class="form-check">

                            <input
                                type="checkbox"
                                name="aplica_ordem_servico"
                                id="nova-aplica-os"
                                value="1"
                                class="form-check-input"
                                checked
                            >

                            <label
                                for="nova-aplica-os"
                                class="form-check-label"
                            >
                                O.S.
                            </label>

                        </div>

                    </div>

                    <div class="col-12 col-lg-1">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                            title="Criar etapa"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table table-striped table-hover align-middle">

            <thead>

                <tr>
                    <th>ORDEM</th>
                    <th>ETAPA</th>
                    <th>TIPO</th>
                    <th>COR</th>
                    <th>NOTA</th>
                    <th>O.S.</th>
                    <th>ATIVA</th>
                    <th class="text-center">
                        SALVAR
                    </th>
                </tr>

            </thead>

            <tbody>

                @foreach($etapas as $etapa)

                    @php

                        $estrutural =
                            in_array(
                                $etapa->tipo,
                                [
                                    'inicial',
                                    'final',
                                ],
                                true
                            );

                        $tipoNome =
                            match($etapa->tipo) {
                                'inicial' =>
                                    'Inicial',

                                'final' =>
                                    'Final',

                                default =>
                                    'Normal',
                            };

                    @endphp

                    <tr>

                        <form
                            method="POST"
                            action="{{ route(
                                'etapas.update',
                                $etapa
                            ) }}"
                        >

                            @csrf
                            @method('PUT')

                            <td style="width: 100px;">

                                <input
                                    type="number"
                                    name="ordem"
                                    class="form-control form-control-sm"
                                    value="{{ $etapa->ordem }}"
                                    min="1"
                                    max="9999"
                                    required
                                >

                            </td>

                            <td style="min-width: 240px;">

                                <input
                                    type="text"
                                    name="nome"
                                    class="form-control form-control-sm"
                                    value="{{ $etapa->nome }}"
                                    maxlength="100"
                                    required
                                >

                                <input
                                    type="text"
                                    name="descricao"
                                    class="form-control form-control-sm mt-1"
                                    value="{{ $etapa->descricao }}"
                                    maxlength="255"
                                    placeholder="Descrição"
                                >

                            </td>

                            <td>

                                @if($etapa->tipo === 'inicial')

                                    <span class="badge bg-primary">
                                        {{ $tipoNome }}
                                    </span>

                                @elseif($etapa->tipo === 'final')

                                    <span class="badge bg-success">
                                        {{ $tipoNome }}
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ $tipoNome }}
                                    </span>

                                @endif

                            </td>

                            <td style="min-width: 140px;">

                                <select
                                    name="cor"
                                    class="form-select form-select-sm"
                                >

                                    @foreach([
                                        'secondary' => 'Cinza',
                                        'primary' => 'Azul',
                                        'success' => 'Verde',
                                        'warning' => 'Amarelo',
                                        'danger' => 'Vermelho',
                                        'info' => 'Azul claro',
                                        'dark' => 'Escuro',
                                    ] as $cor => $nome)

                                        <option
                                            value="{{ $cor }}"
                                            @selected(
                                                $etapa->cor
                                                === $cor
                                            )
                                        >
                                            {{ $nome }}
                                        </option>

                                    @endforeach

                                </select>

                                <span
                                    class="badge bg-{{ $etapa->cor }} mt-1"
                                >
                                    {{ $etapa->nome }}
                                </span>

                            </td>

                            <td class="text-center">

                                <input
                                    type="checkbox"
                                    name="aplica_nota"
                                    value="1"
                                    class="form-check-input"
                                    @checked(
                                        $etapa->aplica_nota
                                    )
                                    @disabled($estrutural)
                                >

                                @if(
                                    $estrutural
                                    && $etapa->aplica_nota
                                )

                                    <input
                                        type="hidden"
                                        name="aplica_nota"
                                        value="1"
                                    >

                                @endif

                            </td>

                            <td class="text-center">

                                <input
                                    type="checkbox"
                                    name="aplica_ordem_servico"
                                    value="1"
                                    class="form-check-input"
                                    @checked(
                                        $etapa
                                            ->aplica_ordem_servico
                                    )
                                    @disabled($estrutural)
                                >

                                @if(
                                    $estrutural
                                    && $etapa
                                        ->aplica_ordem_servico
                                )

                                    <input
                                        type="hidden"
                                        name="aplica_ordem_servico"
                                        value="1"
                                    >

                                @endif

                            </td>

                            <td class="text-center">

                                @if($estrutural)

                                    <i
                                        class="bi bi-lock-fill text-muted"
                                        title="Etapas inicial e final não podem ser desativadas"
                                    ></i>

                                @else

                                    <input
                                        type="checkbox"
                                        name="ativo"
                                        value="1"
                                        class="form-check-input"
                                        @checked(
                                            $etapa->ativo
                                        )
                                    >

                                @endif

                            </td>

                            <td class="text-center">

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    title="Salvar etapa"
                                >
                                    <i class="bi bi-floppy"></i>
                                </button>

                            </td>

                        </form>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    <div class="alert alert-light border mt-3">

        <i class="bi bi-info-circle"></i>

        As etapas
        <strong>Inicial</strong>
        e
        <strong>Final</strong>
        fazem parte da estrutura do fluxo e não podem ser desativadas.

        Etapas normais podem ser reorganizadas, desativadas e aplicadas somente a Notas, somente a O.S. ou a ambos.

    </div>

</div>

@endsection
