<div
    class="modal fade"
    id="modalAprovarCompra"
    tabindex="-1"
    aria-labelledby="modalAprovarCompraLabel"
    aria-hidden="true"
    data-valor-total="{{ $compra->valor_total }}"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
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

            <form
                method="POST"
                action="{{ route('compras.aprovar', $compra) }}"
                id="formAprovarCompra"
            >
                @csrf

                <div class="modal-body">

                    {{-- ETAPA 1: APROVAÇÃO --}}
                    <div
                        id="aprovacao-compra-etapa-inicial"
                    >
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>

                            Todos os itens foram conferidos e a compra está
                            pronta para aprovação.
                        </div>

                        <p class="mb-0">
                            Deseja gerar também a conta a pagar desta compra?
                        </p>
                    </div>

                    {{-- ETAPA 2: PARCELAS --}}
                    <div
                        id="aprovacao-compra-etapa-parcelas"
                        class="d-none"
                    >
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>

                            Configure as parcelas da conta a pagar.
                        </div>

                        <div class="row g-3">

                            {{-- QUANTIDADE --}}
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
                                    max="120"
                                    step="1"
                                    value="1"
                                    inputmode="numeric"
                                >
                            </div>

                            {{-- PRIMEIRO VENCIMENTO --}}
                            <div class="col-12 col-md-4">
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

                            {{-- INTERVALO --}}
                            <div class="col-12 col-md-4">
                                <label
                                    for="intervalo_parcelas"
                                    class="form-label"
                                >
                                    Intervalo
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

                        {{-- PRÉVIA --}}
                        <div class="card bg-light border mt-4">
                            <div class="card-body">

                                <h2 class="h6 mb-3">
                                    <i class="bi bi-list-check"></i>
                                    Prévia das parcelas
                                </h2>

                                <div
                                    id="aprovacao-compra-preview-parcelas"
                                    class="small"
                                ></div>

                                <div
                                    class="d-flex justify-content-between mt-3 pt-2 border-top"
                                >
                                    <span class="fw-semibold">
                                        Total
                                    </span>

                                    <strong
                                        id="aprovacao-compra-total"
                                    >
                                        R$
                                        {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                                    </strong>
                                </div>

                            </div>
                        </div>

                        {{-- CAMPOS HIDDEN DAS PARCELAS --}}
                        <div
                            id="aprovacao-compra-parcelas-hidden"
                        ></div>
                    </div>

                    {{-- CONTROLE DO BACKEND --}}
                    <input
                        type="hidden"
                        name="gerar_conta"
                        id="aprovacao-compra-gerar-conta"
                        value="0"
                    >

                </div>

                {{-- RODAPÉ --}}
                <div class="modal-footer">

                    {{-- ETAPA INICIAL --}}
                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                        data-aprovacao="nao-gerar"
                    >
                        <i class="bi bi-check-circle"></i>
                        Aprovar sem gerar conta
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        data-aprovacao="gerar"
                    >
                        <i class="bi bi-wallet2"></i>
                        Gerar conta a pagar
                    </button>

                    {{-- ETAPA PARCELAS --}}
                    <button
                        type="button"
                        class="btn btn-outline-secondary d-none"
                        data-aprovacao="voltar"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Voltar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success d-none"
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
