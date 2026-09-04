<?php

namespace App\Http\Requests\Fornecedor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do fornecedor é obrigatório.',
            'nome.string' => 'O nome do fornecedor é inválido.',
            'nome.max' => 'O nome do fornecedor não pode ter mais de 255 caracteres.',
            'cnpj.string' => 'O CNPJ informado é inválido.',
            'cnpj.max' => 'O CNPJ não pode ter mais de 18 caracteres.',
            'telefone.string' => 'O telefone informado é inválido.',
            'telefone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'email.email' => 'O e-mail informado é inválido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
        ];
    }
}
