<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagamentoContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
            'data_pagamento' => ['required', 'date'],
            'forma_pagamento' => ['required', 'string', 'max:100'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'O valor do pagamento é obrigatório.',
            'valor.numeric' => 'O valor do pagamento deve ser numérico.',
            'valor.gt' => 'O valor do pagamento deve ser maior que zero.',
            'data_pagamento.required' => 'A data do pagamento é obrigatória.',
            'forma_pagamento.required' => 'A forma de pagamento é obrigatória.',
        ];
    }
}
