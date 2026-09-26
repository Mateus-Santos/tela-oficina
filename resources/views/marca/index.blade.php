@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Marcas</h1>
            <p class="text-muted mb-0">
                Gerencie as marcas utilizadas no cadastro de produtos.
            </p>
        </div>

        <a
            href="{{ route('marcas.create') }}"
            class="btn btn-success"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Nova Marca
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($marcas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 90px;">Logo</th>
                                <th>Marca</th>
                                <th class="text-center">Produtos</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width: 220px;">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($marcas as $marca)
                                <tr>
                                    <td>
                                        @if($marca->logo_path)
                                            <img
                                                src="{{ asset('storage/' . $marca->logo_path) }}"
                                                alt="Logo {{ $marca->nome }}"
                                                class="img-thumbnail"
                                                style="max-width: 60px; max-height: 50px;"
                                            >
                                        @elseif($marca->logo_url)
                                            <img
                                                src="{{ $marca->logo_url }}"
                                                alt="Logo {{ $marca->nome }}"
                                                class="img-thumbnail"
                                                style="max-width: 60px; max-height: 50px;"
                                            >
                                        @else
                                            <span class="text-muted">
                                                <i class="bi bi-image"></i>
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <strong>{{ $marca->nome }}</strong>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge text-bg-secondary">
                                            {{ $marca->produtos_count }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        @if($marca->ativo)
                                            <span class="badge text-bg-success">
                                                Ativa
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">
                                                Inativa
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('marcas.show', $marca) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Visualizar"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a
                                                href="{{ route('marcas.edit', $marca) }}"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form
                                                action="{{ route('marcas.destroy', $marca) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Deseja realmente excluir esta marca?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Excluir"
                                                    @disabled($marca->produtos_count > 0)
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        @if($marca->produtos_count > 0)
                                            <div class="small text-muted mt-1">
                                                Possui produtos vinculados
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($marcas->hasPages())
                    <div class="p-3 border-top">
                        {{ $marcas->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-tags fs-1 text-muted"></i>

                    <h5 class="mt-3">
                        Nenhuma marca cadastrada
                    </h5>

                    <p class="text-muted">
                        Cadastre a primeira marca para começar a utilizá-la nos produtos.
                    </p>

                    <a
                        href="{{ route('marcas.create') }}"
                        class="btn btn-success"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        Cadastrar Marca
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
