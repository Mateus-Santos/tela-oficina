<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('nome')) {
            return;
        }

        $this->merge([
            'cpf' => $this->somenteDigitos($this->input('cpf')),
            'rg' => $this->somenteDigitos($this->input('rg')),
            'telefone_1' => $this->somenteDigitos($this->input('telefone_1')),
            'telefone_2' => $this->somenteDigitos($this->input('telefone_2')),
        ]);
    }

    public function rules(): array
    {
        if (! $this->filled('nome')) {
            return [
                'pessoa_id' => ['required', 'exists:pessoas,id', 'unique:clientes,pessoa_id'],
                'pontos' => ['nullable', 'integer', 'min:0'],
            ];
        }

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_if:criar_usuario,1', 'email', 'unique:users,email'],
            'cpf' => ['nullable', 'string', 'size:11', 'unique:pessoas,cpf'],
            'rg' => ['nullable', 'string', 'max:14'],
            'data_nascimento' => ['nullable', 'date'],
            'telefone_1' => ['nullable', 'string', 'min:10', 'max:11'],
            'telefone_2' => ['nullable', 'string', 'min:10', 'max:11'],
            'pontos' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'email.required_if' => 'O e-mail é obrigatório para criar um usuário de acesso.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cpf.size' => 'O CPF deve possuir exatamente 11 dígitos.',
            'rg.max' => 'O campo RG não pode ter mais que 14 dígitos.',
            'telefone_1.min' => 'O Telefone Principal deve ter pelo menos 10 dígitos (DDD + número).',
            'telefone_1.max' => 'O Telefone Principal não pode ter mais que 11 dígitos.',
            'telefone_2.min' => 'O Telefone Secundário deve ter pelo menos 10 dígitos (DDD + número).',
            'telefone_2.max' => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
            'pessoa_id.required' => 'Selecione uma pessoa da lista ou preencha os dados de uma nova pessoa.',
            'pessoa_id.unique' => 'Esta pessoa já é um cliente cadastrado.',
        ];
    }

    private function somenteDigitos(mixed $valor): mixed
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (! is_string($valor)) {
            return $valor;
        }

        $limpo = preg_replace('/\D/', '', $valor);

        return $limpo !== '' ? $limpo : null;
    }
}
