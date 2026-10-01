<div
    class="modal fade"
    id="modalFinalizarNota"
    tabindex="-1"
    aria-labelledby="modalFinalizarNotaLabel"
    aria-hidden="true"
    data-valor-total="{{ number_format(
        $totalNota,
        2,
        '.',
        ''
    ) }}"
    data-reabrir="{{
        (
            $errors->has('finalizacao')
            || $errors->has('categoria_financeira_id')
            || $errors->has('parcelas')
            || $errors->has('parcelas.*')
        )
            ? '1'
            : '0'
    }}"
>

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <form
            method="POST"
            action="{{ route(
                'notas.finalizar',
                $nota
            ) }}"
            id="formFinalizarNota"
            class="modal-content"
        >

            @csrf

            <div class="modal-header">

                <div>

                    <h1
                        class="modal-title fs-5 mb-1"
                        id="modalFinalizarNotaLabel"
                    >
                        <i class="bi bi-check-circle"></i>
                        Finalizar Nota #{{ $nota->id }}
                    </h1>

                    <div class="text-muted small">

                        {{
                            $contaReceber
                                ? 'Confira os dados antes de finalizar a Nota.'
                                : 'Configure a Conta a Receber antes de finalizar a Nota.'
                        }}

                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>

            </div>


            <div class="modal-body">

                @if($errors->has('finalizacao'))

                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ $errors->first('finalizacao') }}
                    </div>

                @endif


                @if(
                    $errors->has('categoria_financeira_id')
                    || $errors->has('parcelas')
                    || $errors->has('parcelas.*')
                )

                    <div class="alert alert-danger">

                        <div class="fw-semibold mb-1">
                            <i class="bi bi-exclamation-triangle"></i>
                            Corrija os dados financeiros antes de finalizar.
                        </div>

                        <ul class="mb-0 ps-3">

                            @foreach($errors->all() as $erro)

                                @if(
                                    $erro
                                        !== $errors->first('finalizacao')
                                    && $erro
                                        !== $errors->first('cancelamento')
                                    && $erro
                                        !== $errors->first('nota')
                                )

                                    <li>
                                        {{ $erro }}
                                    </li>

                                @endif

                            @endforeach

                        </ul>

                    </div>

                @endif


                <div class="alert alert-warning">

                    <div class="fw-semibold mb-1">
                        <i class="bi bi-exclamation-triangle"></i>
                        Atenção
                    </div>

                    Ao finalizar, os produtos serão baixados do estoque
                    e a Nota não poderá mais ser editada.

                    @if(!$contaReceber)

                        A Conta a Receber será criada nesta operação.

                    @endif

                </div>


                <div class="row g-3 mb-4">

                    <div class="col-12 col-lg-4">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block mb-1">
                                Nota
                            </small>

                            <strong>
                                #{{ $nota->id }}
                            </strong>

                        </div>

                    </div>

                    <div class="col-12 col-lg-4">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block mb-1">
                                Cliente
                            </small>

                            <strong>
                                {{ $clienteNome }}
                            </strong>

                        </div>

                    </div>

                    <div class="col-12 col-lg-4">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted d-block mb-1">
                                Total
                            </small>

                            <strong class="fs-5">

                                R$
                                {{
                                    number_format(
                                        $totalNota,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}

                            </strong>

                        </div>

                    </div>

                </div>


                @if($contaReceber)

                    <div class="card mb-0">

                        <div class="card-header">

                            <h2 class="h6 mb-0">
                                <i class="bi bi-cash-coin"></i>
                                Conta a Receber existente
                            </h2>

                        </div>

                        <div class="card-body">

                            @if(!$financeiroSincronizado)

                                <div class="alert alert-danger">

                                    <i class="bi bi-exclamation-triangle"></i>

                                    O financeiro da Nota está inconsistente.
                                    Regularize a Conta a Receber antes
                                    de finalizar.

                                </div>

                            @endif

                            <div class="row g-3">

                                <div class="col-md-3">

                                    <small class="text-muted d-block">
                                        Conta
                                    </small>

                                    <strong>
                                        #{{ $contaReceber->id }}
                                    </strong>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted d-block">
                                        Valor
                                    </small>

                                    <strong>
                                        R$
                                        {{
                                            number_format(
                                                $valorConta,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}
                                    </strong>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted d-block">
                                        Recebido
                                    </small>

                                    <strong class="text-success">
                                        R$
                                        {{
                                            number_format(
                                                $valorRecebido,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}
                                    </strong>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted d-block">
                                        Saldo
                                    </small>

                                    <strong>
                                        R$
                                        {{
                                            number_format(
                                                $saldoConta,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </div>

                @else

                    <div class="card mb-4">

                        <div class="card-header">

                            <h2 class="h6 mb-0">
                                <i class="bi bi-tag"></i>
                                Classificação financeira
                            </h2>

                        </div>

                        <div class="card-body">

                            <label
                                for="categoria_financeira_id"
                                class="form-label"
                            >
                                Categoria financeira de entrada
                            </label>

                            <select
                                name="categoria_financeira_id"
                                id="categoria_financeira_id"
                                class="form-select @error('categoria_financeira_id') is-invalid @enderror"
                                required
                            >

                                <option value="">
                                    Selecione a categoria...
                                </option>

                                @foreach(
                                    $categoriasFinanceiras
                                    as $categoria
                                )

                                    <option
                                        value="{{ $categoria->id }}"
                                        @selected(
                                            (string) old(
                                                'categoria_financeira_id'
                                            )
                                            ===
                                            (string) $categoria->id
                                        )
                                    >
                                        {{ $categoria->nome }}
                                    </option>

                                @endforeach

                            </select>

                            @error('categoria_financeira_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    <div class="card mb-4">

                        <div class="card-header">

                            <h2 class="h6 mb-0">
                                <i class="bi bi-calendar3"></i>
                                Parcelamento
                            </h2>

                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-12 col-md-4">

                                    <label
                                        for="finalizacao_nota_parcelas_quantidade"
                                        class="form-label"
                                    >
                                        Quantidade de parcelas
                                    </label>

                                    <input
                                        type="number"
                                        id="finalizacao_nota_parcelas_quantidade"
                                        class="form-control"
                                        min="1"
                                        max="120"
                                        step="1"
                                        value="1"
                                        inputmode="numeric"
                                    >

                                </div>

                                <div class="col-12 col-md-4">

                                    <label
                                        for="finalizacao_nota_primeira_data_vencimento"
                                        class="form-label"
                                    >
                                        Primeiro vencimento
                                    </label>

                                    <input
                                        type="date"
                                        id="finalizacao_nota_primeira_data_vencimento"
                                        class="form-control"
                                        value="{{ now()->format('Y-m-d') }}"
                                    >

                                </div>

                                <div class="col-12 col-md-4">

                                    <label
                                        for="finalizacao_nota_intervalo_parcelas"
                                        class="form-label"
                                    >
                                        Intervalo
                                    </label>

                                    <select
                                        id="finalizacao_nota_intervalo_parcelas"
                                        class="form-select"
                                    >

                                        <option value="30">
                                            A cada 30 dias
                                        </option>

                                        <option value="15">
                                            A cada 15 dias
                                        </option>

                                        <option value="7">
                                            A cada 7 dias
                                        </option>

                                        <option value="1">
                                            Diariamente
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card bg-light border mb-0">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                                <h2 class="h6 mb-0">
                                    <i class="bi bi-list-check"></i>
                                    Prévia das parcelas
                                </h2>

                                <span class="text-muted small">
                                    Confira os vencimentos antes de finalizar.
                                </span>

                            </div>

                            <div id="finalizacao-nota-preview-parcelas"></div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">

                                <span class="fw-semibold">
                                    Total das parcelas
                                </span>

                                <strong
                                    id="finalizacao-nota-total"
                                    class="fs-5"
                                >
                                    R$
                                    {{
                                        number_format(
                                            $totalNota,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </strong>

                            </div>

                        </div>

                    </div>

                    <div id="finalizacao-nota-parcelas-hidden"></div>

                @endif

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    <i class="bi bi-x-circle"></i>
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-success"
                    id="botaoFinalizarNota"
                >
                    <i class="bi bi-check-circle"></i>
                    Finalizar nota
                </button>

            </div>

        </form>

    </div>

</div>
