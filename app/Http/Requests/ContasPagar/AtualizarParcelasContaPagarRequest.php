<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarParcelasContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parcelas' => [
                'required',
                'array',
                'min:1',
            ],
            'parcelas.*.id' => [
                'nullable',
                'integer',
                'distinct',
            ],
            'parcelas.*.valor' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'parcelas.*.data_vencimento' => [
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parcelas.required' => 'As parcelas são obrigatórias.',
            'parcelas.array' => 'As parcelas informadas são inválidas.',
            'parcelas.min' => 'A conta deve possuir pelo menos uma parcela.',
            'parcelas.*.id.integer' => 'O identificador da parcela é inválido.',
            'parcelas.*.id.distinct' => 'Uma parcela foi informada mais de uma vez.',
            'parcelas.*.valor.required' => 'O valor da parcela é obrigatório.',
            'parcelas.*.valor.numeric' => 'O valor da parcela deve ser numérico.',
            'parcelas.*.valor.gt' => 'O valor da parcela deve ser maior que zero.',
            'parcelas.*.data_vencimento.required' => 'A data de vencimento da parcela é obrigatória.',
            'parcelas.*.data_vencimento.date' => 'A data de vencimento da parcela deve ser válida.',
        ];
    }
}
