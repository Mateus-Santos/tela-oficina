<div
    class="modal fade"
    id="modalAdicionarItem"
    tabindex="-1"
    aria-labelledby="modalAdicionarItemLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalAdicionarItemLabel"
                >
                    <i class="bi bi-plus-circle"></i>
                    Adicionar item
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>

            </div>


            <div class="modal-body">

                {{-- Tipo --}}
                <div class="mb-3">

                    <label
                        for="builder_type"
                        class="form-label"
                    >
                        Tipo de item
                    </label>

                    <select
                        id="builder_type"
                        class="form-select"
                    >

                        <option value="produto">
                            Produto
                        </option>

                        <option value="os">
                            Ordem de Serviço
                        </option>

                    </select>

                </div>


                {{-- Pesquisa --}}
                <div class="mb-3">

                    <label
                        for="builder_item_busca"
                        class="form-label"
                    >
                        Buscar item
                    </label>

                    <input
                        type="search"
                        id="builder_item_busca"
                        class="form-control"
                        placeholder="Digite nome, código, descrição ou número..."
                        autocomplete="off"
                    >

                    <div
                        id="builder_busca_status"
                        class="form-text"
                        aria-live="polite"
                    >
                        Digite para pesquisar.
                    </div>

                </div>


                {{-- Resultados --}}
                <div
                    id="builder_resultados"
                    class="list-group mb-4"
                    aria-live="polite"
                ></div>


                {{-- ID do item selecionado --}}
                <input
                    type="hidden"
                    id="builder_item_id"
                    value=""
                >


                {{-- Descrição --}}
                <div class="mb-3">

                    <label
                        for="builder_descricao"
                        class="form-label"
                    >
                        Descrição
                    </label>

                    <textarea
                        id="builder_descricao"
                        class="form-control"
                        rows="3"
                        placeholder="Descrição do item"
                    ></textarea>

                </div>


                <div class="row">

                    {{-- Quantidade --}}
                    <div class="col-md-4 mb-3">

                        <label
                            for="builder_quantidade"
                            class="form-label"
                        >
                            Quantidade
                        </label>

                        <input
                            type="number"
                            id="builder_quantidade"
                            class="form-control"
                            value="1"
                            min="0.01"
                            step="0.01"
                        >

                    </div>


                    {{-- Valor unitário --}}
                    <div class="col-md-4 mb-3">

                        <label
                            for="builder_valor_unitario"
                            class="form-label"
                        >
                            Valor unitário
                        </label>

                        <input
                            type="number"
                            id="builder_valor_unitario"
                            class="form-control"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    {{-- Desconto --}}
                    <div class="col-md-4 mb-3">

                        <label
                            for="builder_desconto"
                            class="form-label"
                        >
                            Desconto
                        </label>

                        <input
                            type="number"
                            id="builder_desconto"
                            class="form-control"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>

                </div>


                {{-- Garantia --}}
                <div class="mb-3">

                    <label
                        for="builder_garantia_dias"
                        class="form-label"
                    >
                        Garantia (dias)
                    </label>

                    <input
                        type="number"
                        id="builder_garantia_dias"
                        class="form-control"
                        value="0"
                        min="0"
                        step="1"
                    >

                </div>


                {{-- Prévia do total --}}
                <div
                    id="builder_previa"
                    class="alert alert-secondary mb-0"
                >

                    <div class="d-flex justify-content-between gap-3">

                        <span>
                            Total do item:
                        </span>

                        <strong id="builder_total">
                            R$ 0,00
                        </strong>

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    <i class="bi bi-x-circle"></i>
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btn-adicionar-item"
                >
                    <i class="bi bi-plus-circle"></i>
                    Adicionar item
                </button>

            </div>

        </div>

    </div>

</div>
