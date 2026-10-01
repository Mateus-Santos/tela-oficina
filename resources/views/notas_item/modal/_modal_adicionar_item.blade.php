<div
    class="modal fade"
    id="modalAdicionarItem"
    tabindex="-1"
    aria-labelledby="modalAdicionarItemLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

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

                {{-- =====================================================
                     AVISOS
                ====================================================== --}}

                <div
                    id="builder_alertas"
                    class="mb-3 d-none"
                    aria-live="polite"
                ></div>

                {{-- =====================================================
                     TIPO
                ====================================================== --}}

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

                {{-- =====================================================
                     PESQUISA
                ====================================================== --}}

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

                    {{-- AÇÃO RÁPIDA PARA O.S. --}}

                    <div class="mt-2">
                        <div
                            id="builder_produto_acoes"
                        >
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                id="btn-abrir-produto-rapido"
                            >
                                <i class="bi bi-box-seam"></i>
                                Cadastrar novo produto
                            </button>
                        </div>

                        <div
                            id="builder_os_acoes"
                            class="d-none"
                        >
                            <button
                                type="button"
                                class="btn btn-outline-warning btn-sm"
                                id="btn-abrir-os-rapida"
                            >
                                <i class="bi bi-tools"></i>
                                Criar nova O.S.
                            </button>
                        </div>
                    </div>
                </div>

                {{-- =====================================================
                     RESULTADOS
                ====================================================== --}}

                <div
                    id="builder_resultados"
                    class="list-group mb-4"
                    aria-live="polite"
                ></div>

                {{-- =====================================================
                    CRIAÇÃO RÁPIDA DE PRODUTO
                ====================================================== --}}

                <div
                    id="builder_produto_rapido"
                    class="card border-primary mb-4 d-none"
                >
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div>
                            <i class="bi bi-box-seam"></i>
                            <strong>Novo Produto</strong>
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="btn-fechar-produto-rapido"
                            title="Fechar criação rápida"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                    <div class="card-body">

                        <div
                            id="builder_produto_rapido_alertas"
                            class="d-none mb-3"
                            aria-live="polite"
                        ></div>

                        <div class="alert alert-light border">
                            <i class="bi bi-info-circle me-1"></i>

                            O produto será automaticamente vinculado
                            ao veículo selecionado na Nota.
                        </div>

                        <div class="row g-3">

                            <div class="col-12 col-md-8">

                                <label
                                    for="builder_produto_nome"
                                    class="form-label"
                                >
                                    Nome
                                </label>

                                <input
                                    type="text"
                                    id="builder_produto_nome"
                                    class="form-control"
                                    maxlength="150"
                                    autocomplete="off"
                                    placeholder="Nome do produto"
                                >

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="builder_produto_marca"
                                    class="form-label"
                                >
                                    Marca
                                </label>

                                <select
                                    id="builder_produto_marca"
                                    class="form-select"
                                >
                                    <option value="">
                                        Carregando marcas...
                                    </option>
                                </select>

                            </div>

                            <div class="col-12 col-md-6">

                                <label
                                    for="builder_produto_codigo_fabricante"
                                    class="form-label"
                                >
                                    Código do fabricante
                                </label>

                                <input
                                    type="text"
                                    id="builder_produto_codigo_fabricante"
                                    class="form-control"
                                    autocomplete="off"
                                >

                            </div>

                            <div class="col-12 col-md-6">

                                <label
                                    for="builder_produto_codigo_barras"
                                    class="form-label"
                                >
                                    Código de barras
                                </label>

                                <input
                                    type="text"
                                    id="builder_produto_codigo_barras"
                                    class="form-control"
                                    autocomplete="off"
                                >

                            </div>

                            <div class="col-12">

                                <label
                                    for="builder_produto_descricao"
                                    class="form-label"
                                >
                                    Descrição
                                </label>

                                <textarea
                                    id="builder_produto_descricao"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Descrição do produto"
                                ></textarea>

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="builder_produto_preco"
                                    class="form-label"
                                >
                                    Preço unitário
                                </label>

                                <input
                                    type="number"
                                    id="builder_produto_preco"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="0.00"
                                >

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="builder_produto_quantidade"
                                    class="form-label"
                                >
                                    Estoque atual
                                </label>

                                <input
                                    type="number"
                                    id="builder_produto_quantidade"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    value="0"
                                >

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="builder_produto_estoque_minimo"
                                    class="form-label"
                                >
                                    Estoque mínimo
                                </label>

                                <input
                                    type="number"
                                    id="builder_produto_estoque_minimo"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    value="0"
                                >

                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="btn-cancelar-produto-rapido"
                            >
                                Cancelar
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btn-salvar-produto-rapido"
                            >
                                <i class="bi bi-plus-circle"></i>
                                Criar e adicionar à Nota
                            </button>

                        </div>

                    </div>

                </div>

                {{-- =====================================================
                     CRIAÇÃO RÁPIDA DE O.S.
                ====================================================== --}}

                <div
                    id="builder_os_rapida"
                    class="card border-warning mb-4 d-none"
                >
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div>
                            <i class="bi bi-tools"></i>
                            <strong>Nova Ordem de Serviço</strong>
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="btn-fechar-os-rapida"
                            title="Fechar criação rápida"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                    <div class="card-body">

                        <div
                            id="builder_os_rapida_alertas"
                            class="d-none mb-3"
                            aria-live="polite"
                        ></div>

                        <div class="alert alert-light border">
                            <i class="bi bi-info-circle me-1"></i>

                            A O.S. será vinculada automaticamente
                            ao cliente e ao veículo selecionados na Nota.
                        </div>

                        {{-- SETOR --}}

                        <div class="mb-3">
                            <label
                                for="builder_os_setor"
                                class="form-label"
                            >
                                Setor de serviço
                            </label>

                            <select
                                id="builder_os_setor"
                                class="form-select"
                            >
                                <option value="">
                                    Carregando setores...
                                </option>
                            </select>
                        </div>

                        {{-- DESCRIÇÃO --}}

                        <div class="mb-3">
                            <label
                                for="builder_os_descricao"
                                class="form-label"
                            >
                                Descrição
                            </label>

                            <textarea
                                id="builder_os_descricao"
                                class="form-control"
                                rows="3"
                                placeholder="Descreva o serviço..."
                            ></textarea>
                        </div>

                        {{-- VALOR --}}

                        <div class="mb-3">
                            <label
                                for="builder_os_valor"
                                class="form-label"
                            >
                                Valor
                            </label>

                            <input
                                type="number"
                                id="builder_os_valor"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="0.00"
                            >
                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="btn-cancelar-os-rapida"
                            >
                                <i class="bi bi-x-circle"></i>
                                Cancelar
                            </button>

                            <button
                                type="button"
                                class="btn btn-warning"
                                id="btn-salvar-os-rapida"
                            >
                                <i class="bi bi-plus-circle"></i>
                                Criar e adicionar à Nota
                            </button>

                        </div>

                    </div>
                </div>

                {{-- =====================================================
                     ITEM SELECIONADO
                ====================================================== --}}

                <input
                    type="hidden"
                    id="builder_item_id"
                    value=""
                >

                <div
                    id="builder_item_codigo_wrapper"
                    class="alert alert-light border d-none mb-3"
                >
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-upc-scan"></i>

                        <div>
                            <small class="text-muted d-block">
                                Código
                            </small>

                            <strong id="builder_item_codigo">
                                —
                            </strong>
                        </div>
                    </div>
                </div>

                {{-- =====================================================
                     DESCRIÇÃO
                ====================================================== --}}

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

                    {{-- QUANTIDADE --}}

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
                            min="1"
                            step="1"
                        >
                    </div>

                    {{-- VALOR UNITÁRIO --}}

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

                    {{-- DESCONTO --}}

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

                {{-- =====================================================
                     GARANTIA
                ====================================================== --}}

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

                {{-- =====================================================
                     PRÉVIA
                ====================================================== --}}

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

@vite([
    'resources/js/notaitem/os-rapida.js',
    'resources/js/notaitem/produto-rapido.js',
])
