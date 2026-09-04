<div class="row g-3">

    <div class="col-12">
        <label for="nome" class="form-label">
            <i class="bi bi-building"></i>
            Nome do fornecedor *
        </label>

        <input
            type="text"
            name="nome"
            id="nome"
            class="form-control"
            value="{{ old('nome', $fornecedor->nome ?? '') }}"
            maxlength="255"
            required
            autofocus
        >
    </div>

    <div class="col-12 col-md-6">
        <label for="cnpj" class="form-label">
            <i class="bi bi-card-text"></i>
            CNPJ
        </label>

        <input
            type="text"
            name="cnpj"
            id="cnpj"
            class="form-control"
            value="{{ old('cnpj', $fornecedor->cnpj ?? '') }}"
            maxlength="18"
            placeholder="00.000.000/0000-00"
        >
    </div>

    <div class="col-12 col-md-6">
        <label for="telefone" class="form-label">
            <i class="bi bi-telephone"></i>
            Telefone
        </label>

        <input
            type="text"
            name="telefone"
            id="telefone"
            class="form-control"
            value="{{ old('telefone', $fornecedor->telefone ?? '') }}"
            maxlength="20"
            placeholder="(00) 00000-0000"
        >
    </div>

    <div class="col-12">
        <label for="email" class="form-label">
            <i class="bi bi-envelope"></i>
            E-mail
        </label>

        <input
            type="email"
            name="email"
            id="email"
            class="form-control"
            value="{{ old('email', $fornecedor->email ?? '') }}"
            maxlength="255"
            placeholder="fornecedor@empresa.com"
        >
    </div>

</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a
        href="{{ isset($fornecedor) ? route('fornecedores.show', $fornecedor) : route('fornecedores.index') }}"
        class="btn btn-secondary"
    >
        <i class="bi bi-x-lg"></i>
        Cancelar
    </a>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>
        {{ isset($fornecedor) ? 'Atualizar Fornecedor' : 'Cadastrar Fornecedor' }}
    </button>
</div>
