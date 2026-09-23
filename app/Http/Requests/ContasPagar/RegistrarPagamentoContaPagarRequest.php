<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagamentoContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parcela_conta_pagar_id' => [
                'required',
                'integer',
                Rule::exists('parcelas_contas_pagar', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'conta_pagar_id',
                            $this->route('conta')->id
                        )
                    ),
            ],
            'valor' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'data_pagamento' => [
                'required',
                'date',
            ],
            'forma_pagamento_id' => [
                'required',
                Rule::exists('formas_pagamento', 'id')
                    ->where(
                        fn ($query) => $query->where('ativo', true)
                    ),
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
            'parcela_conta_pagar_id.required' => 'A parcela do pagamento é obrigatória.',
            'parcela_conta_pagar_id.integer' => 'A parcela selecionada é inválida.',
            'parcela_conta_pagar_id.exists' => 'A parcela selecionada não pertence a esta conta a pagar.',
            'valor.required' => 'O valor do pagamento é obrigatório.',
            'valor.numeric' => 'O valor do pagamento deve ser numérico.',
            'valor.gt' => 'O valor do pagamento deve ser maior que zero.',
            'data_pagamento.required' => 'A data do pagamento é obrigatória.',
            'data_pagamento.date' => 'A data do pagamento deve ser uma data válida.',
            'forma_pagamento_id.required' => 'A forma de pagamento é obrigatória.',
            'forma_pagamento_id.exists' => 'A forma de pagamento selecionada não está disponível.',
        ];
    }
}
