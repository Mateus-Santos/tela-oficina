@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="AJUSTE DE ESTOQUE"
        icon="bi-sliders"
        create-route="estoque.index"
        create-text="Voltar"
        create-icon="bi-arrow-left"
    />

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h5 class="mb-1">
                    <i class="bi bi-box-seam"></i>
                    {{ $produto->nome }}
                </h5>

                <div class="text-muted">
                    Código do fabricante:
                    <strong>{{ $produto->codigo_fabricante }}</strong>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">Estoque atual</small>
                        <strong
                            id="estoque-atual"
                            class="fs-4"
                            data-valor="{{ $produto->quantidade }}"
                        >
                            {{ number_format($produto->quantidade, 3, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">Estoque mínimo</small>
                        <strong class="fs-4">
                            {{ number_format($produto->estoque_minimo, 3, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">Preço unitário</small>
                        <strong class="fs-4">
                            R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('estoque.registrarAjuste', $produto) }}"
                id="form-ajuste-estoque"
            >
                @csrf

                <div class="mb-3">
                    <label for="novo_saldo" class="form-label">
                        Novo estoque
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        name="novo_saldo"
                        id="novo_saldo"
                        class="form-control @error('novo_saldo') is-invalid @enderror"
                        value="{{ old('novo_saldo') }}"
                        min="0"
                        step="0.001"
                        required
                        autofocus
                    >

                    @error('novo_saldo')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="form-text">
                        Informe a quantidade que realmente existe no estoque após a conferência.
                    </div>
                </div>

                <div
                    id="resultado-ajuste"
                    class="alert alert-secondary d-none mb-4"
                    aria-live="polite"
                >
                    <div class="d-flex align-items-start gap-2">
                        <i id="resultado-ajuste-icone" class="bi bi-info-circle fs-5"></i>

                        <div>
                            <strong id="resultado-ajuste-titulo">
                                Ajuste
                            </strong>

                            <div id="resultado-ajuste-texto" class="mt-1"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="observacoes" class="form-label">
                        Motivo do ajuste
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="observacoes"
                        id="observacoes"
                        class="form-control @error('observacoes') is-invalid @enderror"
                        rows="4"
                        maxlength="1000"
                        placeholder="Informe o motivo do ajuste, por exemplo: conferência física do estoque..."
                        required
                    >{{ old('observacoes') }}</textarea>

                    @error('observacoes')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="form-text">
                        O motivo ficará registrado no histórico de movimentações.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a
                        href="{{ route('estoque.index') }}"
                        class="btn btn-outline-secondary"
                    >
                        <i class="bi bi-x-lg"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btn-registrar-ajuste"
                    >
                        <i class="bi bi-check-lg"></i>
                        Registrar ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const estoqueAtualElement = document.getElementById('estoque-atual');
    const novoSaldoInput = document.getElementById('novo_saldo');
    const resultado = document.getElementById('resultado-ajuste');
    const resultadoTitulo = document.getElementById('resultado-ajuste-titulo');
    const resultadoTexto = document.getElementById('resultado-ajuste-texto');
    const resultadoIcone = document.getElementById('resultado-ajuste-icone');

    if (!estoqueAtualElement || !novoSaldoInput || !resultado) {
        return;
    }

    const estoqueAtual = Number(estoqueAtualElement.dataset.valor);

    function formatarQuantidade(valor) {
        return valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        });
    }

    function atualizarResultado() {
        const novoSaldo = Number(novoSaldoInput.value);

        if (
            novoSaldoInput.value === '' ||
            !Number.isFinite(novoSaldo) ||
            novoSaldo < 0
        ) {
            resultado.classList.add('d-none');
            return;
        }

        const diferenca = novoSaldo - estoqueAtual;

        resultado.classList.remove('d-none');
        resultado.classList.remove('alert-success', 'alert-danger', 'alert-secondary');

        if (diferenca > 0) {
            resultado.classList.add('alert-success');
            resultadoIcone.className = 'bi bi-arrow-up-circle fs-5';
            resultadoTitulo.textContent = 'Aumento de estoque';
            resultadoTexto.textContent =
                'O estoque será ajustado de ' +
                formatarQuantidade(estoqueAtual) +
                ' para ' +
                formatarQuantidade(novoSaldo) +
                ', adicionando ' +
                formatarQuantidade(diferenca) +
                ' unidade(s).';
        } else if (diferenca < 0) {
            resultado.classList.add('alert-danger');
            resultadoIcone.className = 'bi bi-arrow-down-circle fs-5';
            resultadoTitulo.textContent = 'Redução de estoque';
            resultadoTexto.textContent =
                'O estoque será ajustado de ' +
                formatarQuantidade(estoqueAtual) +
                ' para ' +
                formatarQuantidade(novoSaldo) +
                ', retirando ' +
                formatarQuantidade(Math.abs(diferenca)) +
                ' unidade(s).';
        } else {
            resultado.classList.add('alert-secondary');
            resultadoIcone.className = 'bi bi-dash-circle fs-5';
            resultadoTitulo.textContent = 'Nenhuma alteração';
            resultadoTexto.textContent =
                'O novo estoque é igual ao estoque atual. Nenhum ajuste será realizado.';
        }
    }

    novoSaldoInput.addEventListener('input', atualizarResultado);
    atualizarResultado();
});
</script>
@endsection
