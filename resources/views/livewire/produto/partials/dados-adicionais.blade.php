{{-- =========================================================
     INFORMAÇÕES ADICIONAIS
     CADASTRO E EDIÇÃO
     ========================================================= --}}
<dialog
    id="dialog-dados-adicionais-produto"
    class="produto-dialog"
    wire:ignore.self
>

    <div class="produto-dialog__cabecalho">

        <div>

            <h5 class="mb-1 fw-bold">
                <i class="bi bi-sliders me-1"></i>
                Informações adicionais
            </h5>

            <div class="small text-dark fw-semibold">
                Dados fiscais, comerciais e de controle de estoque.
            </div>

        </div>

        <button
            type="button"
            class="btn-close"
            id="btn-fechar-dados-adicionais-produto"
            aria-label="Fechar"
        ></button>

    </div>

    <div class="produto-dialog__conteudo">

        <div class="border-start border-4 border-primary bg-white rounded-end px-3 py-2 mb-4 text-dark fw-medium">

            <i class="bi bi-info-circle-fill text-primary me-1"></i>

            Estes campos são opcionais. Preencha quando possuir as informações fiscais ou comerciais do produto.

        </div>

        <div class="row g-3">

            {{-- ESTOQUE MÍNIMO --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="estoque_minimo"
                >
                    Estoque mínimo
                </label>

                <input
                    type="number"
                    class="form-control @error('estoque_minimo') is-invalid @enderror"
                    id="estoque_minimo"
                    name="estoque_minimo"
                    min="0"
                    value="{{ old(
                        'estoque_minimo',
                        isset($produto)
                            ? $produto->estoque_minimo
                            : 0
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Quantidade mínima antes de o estoque ser considerado baixo.
                </div>

                @error('estoque_minimo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- NCM --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="ncm"
                >
                    NCM
                </label>

                <input
                    type="text"
                    class="form-control @error('ncm') is-invalid @enderror"
                    id="ncm"
                    name="ncm"
                    maxlength="8"
                    value="{{ old(
                        'ncm',
                        isset($produto)
                            ? $produto->ncm
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Código fiscal de classificação do produto.
                </div>

                @error('ncm')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- CEST --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="cest"
                >
                    CEST
                </label>

                <input
                    type="text"
                    class="form-control @error('cest') is-invalid @enderror"
                    id="cest"
                    name="cest"
                    maxlength="20"
                    value="{{ old(
                        'cest',
                        isset($produto)
                            ? $produto->cest
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Código usado quando houver substituição tributária.
                </div>

                @error('cest')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- EX-TIPI --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="ex_tipi"
                >
                    EX-TIPI
                </label>

                <input
                    type="text"
                    class="form-control @error('ex_tipi') is-invalid @enderror"
                    id="ex_tipi"
                    name="ex_tipi"
                    maxlength="20"
                    value="{{ old(
                        'ex_tipi',
                        isset($produto)
                            ? $produto->ex_tipi
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Exceção da TIPI vinculada ao NCM, quando aplicável.
                </div>

                @error('ex_tipi')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- ORIGEM --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="origem_mercadoria"
                >
                    Origem da mercadoria
                </label>

                <select
                    class="form-select @error('origem_mercadoria') is-invalid @enderror"
                    id="origem_mercadoria"
                    name="origem_mercadoria"
                >

                    <option value="">
                        Não informada
                    </option>

                    @foreach([
                        0 => '0 - Nacional',
                        1 => '1 - Estrangeira - Importação direta',
                        2 => '2 - Estrangeira - Mercado interno',
                        3 => '3 - Nacional com conteúdo importado superior a 40%',
                        4 => '4 - Nacional conforme processos produtivos básicos',
                        5 => '5 - Nacional com conteúdo importado até 40%',
                        6 => '6 - Estrangeira - Importação direta sem similar nacional',
                        7 => '7 - Estrangeira - Mercado interno sem similar nacional',
                        8 => '8 - Nacional com conteúdo importado superior a 70%',
                    ] as $codigo => $descricaoOrigem)

                        <option
                            value="{{ $codigo }}"
                            @selected(
                                (string) old(
                                    'origem_mercadoria',
                                    isset($produto)
                                        ? $produto->origem_mercadoria
                                        : ''
                                ) === (string) $codigo
                            )
                        >
                            {{ $descricaoOrigem }}
                        </option>

                    @endforeach

                </select>

                <div class="small text-dark fw-medium mt-1">
                    Origem fiscal utilizada na tributação da mercadoria.
                </div>

                @error('origem_mercadoria')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- UNIDADE COMERCIAL --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="unidade_comercial"
                >
                    Unidade comercial
                </label>

                <input
                    type="text"
                    class="form-control @error('unidade_comercial') is-invalid @enderror"
                    id="unidade_comercial"
                    name="unidade_comercial"
                    maxlength="10"
                    placeholder="Ex.: UN"
                    value="{{ old(
                        'unidade_comercial',
                        isset($produto)
                            ? $produto->unidade_comercial
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Unidade utilizada na venda: UN, CX, PC, KIT etc.
                </div>

                @error('unidade_comercial')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- UNIDADE TRIBUTÁVEL --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="unidade_tributavel"
                >
                    Unidade tributável
                </label>

                <input
                    type="text"
                    class="form-control @error('unidade_tributavel') is-invalid @enderror"
                    id="unidade_tributavel"
                    name="unidade_tributavel"
                    maxlength="10"
                    placeholder="Ex.: UN"
                    value="{{ old(
                        'unidade_tributavel',
                        isset($produto)
                            ? $produto->unidade_tributavel
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Unidade considerada para fins tributários.
                </div>

                @error('unidade_tributavel')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- FATOR DE CONVERSÃO --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="fator_conversao"
                >
                    Fator de conversão
                </label>

                <input
                    type="number"
                    class="form-control @error('fator_conversao') is-invalid @enderror"
                    id="fator_conversao"
                    name="fator_conversao"
                    min="0"
                    step="0.000001"
                    value="{{ old(
                        'fator_conversao',
                        isset($produto)
                            ? $produto->fator_conversao
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Relação entre a unidade comercial e a unidade tributável.
                </div>

                @error('fator_conversao')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- PESO LÍQUIDO --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="peso_liquido"
                >
                    Peso líquido (kg)
                </label>

                <input
                    type="number"
                    class="form-control @error('peso_liquido') is-invalid @enderror"
                    id="peso_liquido"
                    name="peso_liquido"
                    min="0"
                    step="0.001"
                    value="{{ old(
                        'peso_liquido',
                        isset($produto)
                            ? $produto->peso_liquido
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Peso do produto sem considerar a embalagem.
                </div>

                @error('peso_liquido')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- PESO BRUTO --}}
            <div class="col-md-4">

                <label
                    class="form-label"
                    for="peso_bruto"
                >
                    Peso bruto (kg)
                </label>

                <input
                    type="number"
                    class="form-control @error('peso_bruto') is-invalid @enderror"
                    id="peso_bruto"
                    name="peso_bruto"
                    min="0"
                    step="0.001"
                    value="{{ old(
                        'peso_bruto',
                        isset($produto)
                            ? $produto->peso_bruto
                            : ''
                    ) }}"
                >

                <div class="small text-dark fw-medium mt-1">
                    Peso total considerando produto e embalagem.
                </div>

                @error('peso_bruto')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>

        @if(
            $errors->has('estoque_minimo')
            || $errors->has('ncm')
            || $errors->has('cest')
            || $errors->has('ex_tipi')
            || $errors->has('origem_mercadoria')
            || $errors->has('unidade_comercial')
            || $errors->has('unidade_tributavel')
            || $errors->has('fator_conversao')
            || $errors->has('peso_liquido')
            || $errors->has('peso_bruto')
        )

            <div class="alert alert-danger mt-4 mb-0 fw-semibold">

                <i class="bi bi-exclamation-triangle-fill me-1"></i>

                Existem informações adicionais inválidas.
                Revise os campos destacados antes de salvar.

            </div>

        @endif

    </div>

    <div class="produto-dialog__rodape">

        <button
            type="button"
            class="btn btn-primary fw-bold"
            id="btn-concluir-dados-adicionais-produto"
        >
            <i class="bi bi-check-lg me-1"></i>
            Concluir
        </button>

    </div>

</dialog>
