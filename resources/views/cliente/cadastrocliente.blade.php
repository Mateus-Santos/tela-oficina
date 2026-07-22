@extends('layouts.layout')

@section('content')

<section class="container cadastro py-4">
    <h1 class="mb-4">CADASTRO DE CLIENTE</h1>

    {{-- Exibição de erros de validação --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clientes.store') }}" method="POST">
        @csrf

        {{-- Selecionar pessoa existente (opcional) --}}
        @if(isset($pessoasSemCliente) && $pessoasSemCliente->count() > 0)
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white fw-bold">
                    Opção A: Transformar uma Pessoa já cadastrada em Cliente
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="pessoa_id" class="form-label">Selecione uma Pessoa cadastrada:</label>
                            <select name="pessoa_id" id="pessoa_id" class="form-select">
                                <option value="">-- Ou preencha os dados de uma NOVA PESSOA abaixo --</option>
                                @foreach($pessoasSemCliente as $pessoa)
                                    <option value="{{ $pessoa->id }}" {{ old('pessoa_id') == $pessoa->id ? 'selected' : '' }}>
                                        {{ $pessoa->nome }} (CPF: {{ $pessoa->cpf ?? 'Sem CPF' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form para Nova Pessoa --}}
        <div class="card mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                Dados Pessoais (Nova Pessoa)
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex: João Silva">
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="cliente@email.com">
                    </div>

                    <div class="col-md-3">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00">
                    </div>

                    <div class="col-md-3">
                        <label for="rg" class="form-label">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg" value="{{ old('rg') }}" placeholder="00.000.000-0">
                    </div>

                    <div class="col-md-3">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="{{ old('data_nascimento') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="telefone_1" class="form-label">Telefone Principal</label>
                        <input type="text" class="form-control" id="telefone_1" name="telefone_1" value="{{ old('telefone_1') }}" placeholder="(00) 00000-0000">
                    </div>

                    <div class="col-md-4">
                        <label for="telefone_2" class="form-label">Telefone Secundário</label>
                        <input type="text" class="form-control" id="telefone_2" name="telefone_2" value="{{ old('telefone_2') }}" placeholder="(00) 00000-0000">
                    </div>

                    {{-- Switch para geração de usuário e senha --}}
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="criar_usuario" name="criar_usuario" value="1" {{ old('criar_usuario') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="criar_usuario">
                                Criar conta de acesso ao sistema com senha gerada automaticamente? (Requer preenchimento do e-mail)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dados do Cliente (Pontos) --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white fw-bold">
                Dados do Cliente
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="pontos" class="form-label">Pontos Iniciais (Fidelidade)</label>
                        <input type="number" class="form-control" id="pontos" name="pontos" value="{{ old('pontos', 0) }}" min="0">
                    </div>
                </div>
            </div>
        </div>

        {{-- Confirmação e Submissão --}}
        <div class="row mb-3">
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmacao" required>
                    <label class="form-check-label" for="confirmacao">
                        Confirmo que todas as informações adicionadas são verdadeiras.
                    </label>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col text-center">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle"></i> Cadastrar Cliente
                </button>
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-lg ms-2">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </form>
</section>

@endsection