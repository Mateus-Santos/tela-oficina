<div
    class="modal fade"
    id="modalAprovarCompra"
    tabindex="-1"
    aria-labelledby="modalAprovarCompraLabel"
    aria-hidden="true"
    data-valor-total="{{ $compra->valor_total }}"
    data-feux-aprovacao-v4
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

                    {{-- FEUX V4: orientacao visual, sem efeito financeiro autonomo. --}}
                    <section class="feux-aprovacao-v4__resumo mb-3"
                        aria-labelledby="feux-aprovacao-v4-titulo"
                        data-feux-aprovacao-resumo
                        data-divergencia="{{ $compraPossuiDivergencia ? '1' : '0' }}">
                        <h2 id="feux-aprovacao-v4-titulo" class="h6 mb-3">Resumo antes de aprovar</h2>
                        <div class="feux-aprovacao-v4__indicadores">
                            <div><small>Itens na compra</small><strong>{{ $compra->itens->count() }}</strong></div>
                            <div><small>Itens conferidos</small><strong>{{ $compra->itens->filter(fn ($item) => $item->quantidade_conferida !== null)->count() }}</strong></div>
                            <div><small>Encaminhamento financeiro</small><strong>{{ $compraPossuiDivergencia ? 'Conciliação D03' : 'Opcional' }}</strong></div>
                        </div>
                        <p class="small text-body-secondary mt-3 mb-2">
                            Aprovar a compra não representa pagamento nem emissão de NF-e.
                            A entrada em estoque e os pagamentos seguem suas próprias operações.
                        </p>
                        <p class="small fw-semibold mb-0" role="status" aria-live="polite"
                            data-feux-aprovacao-orientacao>
                            @if ($compraPossuiDivergencia)
                                Há divergência física: aprove sem gerar conta e encaminhe o ajuste à conciliação D03.
                            @else
                                Escolha aprovar sem gerar conta ou configurar parcelas para gerar uma conta a pagar.
                            @endif
                        </p>
                    </section>

                    {{-- ETAPA 1: APROVAÇÃO --}}
                    <div
                        id="aprovacao-compra-etapa-inicial"
                        data-aprovacao-etapa="inicial"
                    >
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>

                            Todos os itens foram conferidos e a compra está
                            pronta para aprovação.
                        </div>

                        <p class="mb-0">
                            @if ($compraPossuiDivergencia)
                                Há divergência de recebimento. Aprove sem gerar conta; a conciliação financeira será tratada na D03.
                            @else
                                Deseja gerar também a conta a pagar desta compra?
                            @endif
                        </p>
                    </div>

                    {{-- ETAPA 2: PARCELAS --}}
                    <div
                        id="aprovacao-compra-etapa-parcelas"
                        class="d-none"
                        data-aprovacao-etapa="parcelas"
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

                    @unless ($compraPossuiDivergencia)
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        data-aprovacao="gerar"
                    >
                        <i class="bi bi-wallet2"></i>
                        Gerar conta a pagar
                    </button>
                    @endunless

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
