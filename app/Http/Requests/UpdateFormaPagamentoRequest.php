<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormaPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $formaPagamento = $this->route('formaPagamento');

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('formas_pagamento', 'nome')
                    ->ignore($formaPagamento),
            ],

            'ativo' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da forma de pagamento.',
            'nome.string' => 'O nome da forma de pagamento deve ser um texto.',
            'nome.max' => 'O nome da forma de pagamento não pode ultrapassar 255 caracteres.',
            'nome.unique' => 'Esta forma de pagamento já está cadastrada.',
            'ativo.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
        ];
    }
}
