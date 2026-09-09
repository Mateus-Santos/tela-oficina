{{-- =========================================================
     MODAL DE DESCONTOS
========================================================= --}}

<div
    class="modal fade"
    id="modalDescontos"
    tabindex="-1"
    aria-labelledby="modalDescontosLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            {{-- =================================================
                 CABEÇALHO
            ================================================== --}}
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalDescontosLabel"
                >
                    <i class="bi bi-percent"></i>
                    Gerenciar descontos
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>

            </div>

            {{-- =================================================
                 CORPO
            ================================================== --}}
            <div class="modal-body">

                {{-- =============================================
                     DESCONTO DE PEÇAS / PRODUTOS
                ============================================== --}}
                <div class="card mb-3">

                    <div class="card-header">
                        <strong>
                            <i class="bi bi-box-seam"></i>
                            Desconto em peças
                        </strong>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            {{-- VALOR --}}
                            <div class="col-md-6">

                                <label
                                    for="modal-desc-pecas-valor"
                                    class="form-label"
                                >
                                    Valor do desconto
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        R$
                                    </span>

                                    <input
                                        type="text"
                                        id="modal-desc-pecas-valor"
                                        class="form-control"
                                        value="0,00"
                                        inputmode="decimal"
                                        autocomplete="off"
                                    >

                                </div>

                            </div>

                            {{-- PERCENTUAL --}}
                            <div class="col-md-6">

                                <label
                                    for="modal-desc-pecas-porcent"
                                    class="form-label"
                                >
                                    Percentual
                                </label>

                                <div class="input-group">

                                    <input
                                        type="text"
                                        id="modal-desc-pecas-porcent"
                                        class="form-control"
                                        value="0,00"
                                        inputmode="decimal"
                                        autocomplete="off"
                                    >

                                    <span class="input-group-text">
                                        %
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="mt-3 small text-muted">
                            O desconto será distribuído entre os itens de
                            peças/produtos.
                        </div>

                    </div>

                </div>


                {{-- =============================================
                     DESCONTO DE SERVIÇOS / MÃO DE OBRA
                ============================================== --}}
                <div class="card mb-3">

                    <div class="card-header">
                        <strong>
                            <i class="bi bi-tools"></i>
                            Desconto em serviços
                        </strong>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            {{-- VALOR --}}
                            <div class="col-md-6">

                                <label
                                    for="modal-desc-servicos-valor"
                                    class="form-label"
                                >
                                    Valor do desconto
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        R$
                                    </span>

                                    <input
                                        type="text"
                                        id="modal-desc-servicos-valor"
                                        class="form-control"
                                        value="0,00"
                                        inputmode="decimal"
                                        autocomplete="off"
                                    >

                                </div>

                            </div>

                            {{-- PERCENTUAL --}}
                            <div class="col-md-6">

                                <label
                                    for="modal-desc-servicos-porcent"
                                    class="form-label"
                                >
                                    Percentual
                                </label>

                                <div class="input-group">

                                    <input
                                        type="text"
                                        id="modal-desc-servicos-porcent"
                                        class="form-control"
                                        value="0,00"
                                        inputmode="decimal"
                                        autocomplete="off"
                                    >

                                    <span class="input-group-text">
                                        %
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="mt-3 small text-muted">
                            O desconto será distribuído entre os itens de
                            serviços/mão de obra.
                        </div>

                    </div>

                </div>


                {{-- =============================================
                     RESUMO
                ============================================== --}}
                <div class="alert alert-info mb-0">

                    <div class="d-flex justify-content-between">
                        <span>
                            <i class="bi bi-info-circle"></i>
                            Desconto em peças:
                        </span>

                        <strong id="modal-resumo-desc-pecas">
                            R$ 0,00
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mt-1">
                        <span>
                            <i class="bi bi-info-circle"></i>
                            Desconto em serviços:
                        </span>

                        <strong id="modal-resumo-desc-servicos">
                            R$ 0,00
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>
                            Total de descontos:
                        </strong>

                        <strong id="modal-resumo-desc-total">
                            R$ 0,00
                        </strong>
                    </div>

                </div>

            </div>

            {{-- =================================================
                 RODAPÉ
            ================================================== --}}
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    <i class="bi bi-x-circle"></i>
                    Fechar
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btn-aplicar-descontos-modal"
                >
                    <i class="bi bi-check-circle"></i>
                    Aplicar descontos
                </button>

            </div>

        </div>
    </div>
</div>
