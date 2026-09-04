@extends('layouts.layout')

@section('content')

<section class="container cadastro">

```
<h1>
    <i class="bi bi-credit-card-fill"></i> FORMAS DE PAGAMENTO
</h1>

@if (session('success'))
    <div class="alert alert-success mensseger_error_container">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger mensseger_error_container">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-end mb-4">
    <a href="{{ route('formas-pagamento.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i>
        Cadastrar Forma de Pagamento
    </a>
</div>

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle">

        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Cadastro</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($formasPagamento as $formaPagamento)

                <tr>

                    <td>
                        {{ $formaPagamento->id }}
                    </td>

                    <td>
                        <i class="bi bi-credit-card me-1"></i>
                        {{ $formaPagamento->nome }}
                    </td>

                    <td>
                        @if ($formaPagamento->ativo)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                                Ativo
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-x-circle"></i>
                                Inativo
                            </span>
                        @endif
                    </td>

                    <td>
                        {{ $formaPagamento->created_at?->format('d/m/Y') }}
                    </td>

                    <td class="text-center">

                        <div class="d-flex justify-content-center gap-2">

                            <a
                                href="{{ route('formas-pagamento.edit', $formaPagamento) }}"
                                class="btn btn-sm btn-primary"
                                title="Editar"
                            >
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form
                                action="{{ route('formas-pagamento.destroy', $formaPagamento) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Tem certeza que deseja excluir esta forma de pagamento?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger"
                                    title="Excluir"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bi bi-credit-card-2-front fs-3 d-block mb-2"></i>
                        Nenhuma forma de pagamento cadastrada.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

@if ($formasPagamento->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $formasPagamento->links() }}
    </div>
@endif
```

</section>

@endsection
