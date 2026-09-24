@if ($compra->status === 'conferindo' && $todosItensConferidos)

    <div
        class="modal fade"
        id="modalAprovarCompra"
        tabindex="-1"
        aria-labelledby="modalAprovarCompraLabel"
        aria-hidden="true"
        data-valor-total="{{ $compra->valor_total }}"
    >
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h1
                        class="modal-title fs-5"
                        id="modalAprovarCompraLabel"
                    >
                        <i class="bi bi-check-circle"></i>
                        Aprovar compra
                    </h1>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>

                </div>

                <div class="modal-body">

                    <div id="aprovacao-compra-etapa-inicial">

                        <p class="mb-3">
                            A compra será aprovada após a confirmação.
                        </p>

                        <div class="alert alert-info mb-0">

                            <i class="bi bi-question-circle"></i>

                            Deseja gerar uma conta a pagar para esta compra?

                        </div>

                    </div>

                    <div
                        id="aprovacao-compra-etapa-parcelas"
                        class="d-none"
                    >

                        <div class="alert alert-info">

                            <i class="bi bi-info-circle"></i>

                            Configure as parcelas da conta a pagar que será vinculada a esta compra.

                        </div>

                        <div class="row g-3">

                            <div class="col-12 col-md-4">

                                <label
                                    for="parcelas_quantidade"
                                    class="form-label"
                                >
                                    Quantidade de parcelas
                                </label>

                                <input
                                    type="number"
                                    id="parcelas_quantidade"
                                    class="form-control"
                                    min="1"
                                    step="1"
                                    value="1"
                                    inputmode="numeric"
                                >

                            </div>

                            <div class="col-12 col-md-8">

                                <label
                                    for="primeira_data_vencimento"
                                    class="form-label"
                                >
                                    Primeiro vencimento
                                </label>

                                <input
                                    type="date"
                                    id="primeira_data_vencimento"
                                    class="form-control"
                                    value="{{ now()->format('Y-m-d') }}"
                                >

                            </div>

                            <div class="col-12">

                                <label
                                    for="intervalo_parcelas"
                                    class="form-label"
                                >
                                    Intervalo entre parcelas
                                </label>

                                <select
                                    id="intervalo_parcelas"
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

                        <div class="card bg-light border mt-4">

                            <div class="card-body">

                                <h2 class="h6 mb-3">

                                    <i class="bi bi-list-check"></i>

                                    Prévia das parcelas

                                </h2>

                                <div
                                    id="aprovacao-compra-preview-parcelas"
                                    class="small"
                                >
                                    <div
                                        class="d-flex justify-content-between border-bottom py-2"
                                    >
                                        <span>
                                            Parcela 1
                                        </span>

                                        <strong>
                                            R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                </div>

                                <div
                                    class="d-flex justify-content-between mt-3 pt-2 border-top"
                                >
                                    <span class="fw-semibold">
                                        Total
                                    </span>

                                    <strong id="aprovacao-compra-total">
                                        R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                                    </strong>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <form
                        method="POST"
                        action="{{ route('compras.aprovar', $compra) }}"
                        class="d-flex gap-2 flex-wrap justify-content-end w-100"
                        id="formAprovarCompra"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="gerar_conta"
                            id="aprovacao-compra-gerar-conta"
                            value="0"
                        >

                        <div
                            id="aprovacao-compra-parcelas-hidden"
                        ></div>

                        <div
                            id="aprovacao-compra-acoes-iniciais"
                            class="d-flex gap-2 flex-wrap justify-content-end w-100"
                        >

                            <button
                                type="submit"
                                class="btn btn-outline-secondary"
                                data-aprovacao="nao-gerar"
                            >
                                <i class="bi bi-check2"></i>
                                Não gerar agora
                            </button>

                            <button
                                type="button"
                                class="btn btn-success"
                                data-aprovacao="gerar"
                            >
                                <i class="bi bi-wallet2"></i>
                                Gerar conta a pagar
                            </button>

                        </div>

                        <div
                            id="aprovacao-compra-acoes-parcelas"
                            class="d-none gap-2 flex-wrap justify-content-end w-100"
                        >

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-aprovacao="voltar"
                            >
                                <i class="bi bi-arrow-left"></i>
                                Voltar
                            </button>

                            <button
                                type="button"
                                class="btn btn-success"
                                data-aprovacao="confirmar-geracao"
                            >
                                <i class="bi bi-check-circle"></i>
                                Aprovar e gerar conta
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>
    </div>

@endif
