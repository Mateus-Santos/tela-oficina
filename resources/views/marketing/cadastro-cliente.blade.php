@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height:70vh;max-width:680px">
    <h1 class="h3 mb-3">Criar minha conta de cliente</h1>
    <p class="text-secondary">O cadastro é opcional. Primeiro confirmaremos seu e-mail. A conta não dá acesso a solicitações de outras pessoas.</p>
    @if (session('marketing_cadastro_enviado'))
        <div class="alert alert-success" role="status">{{ session('marketing_cadastro_enviado') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Revise os dados do cadastro.</div>
    @endif
    <form method="POST" action="{{ route('marketing.cadastro.store') }}">
        @csrf
        <div class="visually-hidden" aria-hidden="true">
            <label for="cadastro-website">Não preencha este campo</label>
            <input id="cadastro-website" name="website" type="text" tabindex="-1" autocomplete="off" value="">
        </div>
        <div class="mb-3">
            <label for="cadastro-nome" class="form-label">Nome *</label>
            <input id="cadastro-nome" name="nome" class="form-control @error('nome') is-invalid @enderror" maxlength="120" autocomplete="name" value="{{ old('nome') }}" required>
            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="cadastro-email" class="form-label">E-mail *</label>
            <input id="cadastro-email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" maxlength="255" autocomplete="email" value="{{ old('email') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="cadastro-whatsapp" class="form-label">WhatsApp *</label>
            <input id="cadastro-whatsapp" name="whatsapp" type="tel" class="form-control @error('whatsapp') is-invalid @enderror" maxlength="25" autocomplete="tel" value="{{ old('whatsapp') }}" required>
            @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="cadastro-senha" class="form-label">Senha *</label>
                <input id="cadastro-senha" name="senha" type="password" class="form-control @error('senha') is-invalid @enderror" minlength="10" autocomplete="new-password" required>
                @error('senha')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <label for="cadastro-confirmacao" class="form-label">Confirmar senha *</label>
                <input id="cadastro-confirmacao" name="senha_confirmation" type="password" class="form-control" minlength="10" autocomplete="new-password" required>
            </div>
        </div>
        <p class="small text-secondary">Use pelo menos 10 caracteres, com letras maiúsculas, minúsculas e números. Não informe CPF ou dados do veículo neste formulário.</p>
        <div class="form-check mb-3">
            <input id="cadastro-privacidade" name="aceite_privacidade" type="checkbox" value="1" class="form-check-input @error('aceite_privacidade') is-invalid @enderror" {{ old('aceite_privacidade') ? 'checked' : '' }} required>
            <label for="cadastro-privacidade" class="form-check-label">Autorizo o uso dos dados para criar minha conta e receber a confirmação por e-mail. *</label>
            @error('aceite_privacidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-warning">Enviar confirmação de e-mail</button>
        <a href="{{ route('login') }}" class="btn btn-link">Já tenho conta</a>
    </form>
</main>
@endsection
