<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecebimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'conta_receber_id' => [
                'required',
                'integer',
                'exists:contas_receber,id',
            ],

            'forma_pagamento_id' => [
                'required',
                'integer',
                'exists:formas_pagamento,id',
            ],

            'valor' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'data_pagamento' => [
                'required',
                'date',
            ],

            'observacoes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'conta_receber_id.required' => 'Informe a conta a receber.',
            'conta_receber_id.exists' => 'A conta a receber informada não existe.',

            'forma_pagamento_id.required' => 'Informe a forma de pagamento.',
            'forma_pagamento_id.exists' => 'A forma de pagamento informada não existe.',

            'valor.required' => 'Informe o valor do recebimento.',
            'valor.numeric' => 'O valor do recebimento deve ser numérico.',
            'valor.gt' => 'O valor do recebimento deve ser maior que zero.',
            'valor.decimal' => 'O valor do recebimento deve possuir no máximo duas casas decimais.',

            'data_pagamento.required' => 'Informe a data do pagamento.',
            'data_pagamento.date' => 'A data do pagamento é inválida.',

            'observacoes.string' => 'As observações devem ser um texto.',
        ];
    }
}
