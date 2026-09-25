<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'categoria_financeira_id' => [
                'required',
                'integer',
                Rule::exists(
                    'categorias_financeiras',
                    'id'
                )->where(function ($query) {
                    $query
                        ->where('tipo', 'entrada')
                        ->where('ativo', true);
                }),
            ],

            'parcelas' => [
                'required',
                'array',
                'min:1',
            ],

            'parcelas.*.numero' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],

            'parcelas.*.valor' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'parcelas.*.data_vencimento' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_financeira_id.required' => 'Informe a categoria financeira.',
            'categoria_financeira_id.exists' => 'A categoria financeira informada não existe, está inativa ou não é uma categoria de entrada.',

            'parcelas.required' => 'Informe ao menos uma parcela.',
            'parcelas.array' => 'As parcelas informadas são inválidas.',
            'parcelas.min' => 'Informe ao menos uma parcela.',

            'parcelas.*.numero.required' => 'Informe o número da parcela.',
            'parcelas.*.numero.integer' => 'O número da parcela deve ser um número inteiro.',
            'parcelas.*.numero.min' => 'O número da parcela deve ser maior que zero.',
            'parcelas.*.numero.distinct' => 'Existem números de parcelas duplicados.',

            'parcelas.*.valor.required' => 'Informe o valor da parcela.',
            'parcelas.*.valor.numeric' => 'O valor da parcela deve ser numérico.',
            'parcelas.*.valor.gt' => 'O valor da parcela deve ser maior que zero.',
            'parcelas.*.valor.decimal' => 'O valor da parcela deve possuir no máximo duas casas decimais.',

            'parcelas.*.data_vencimento.required' => 'Informe a data de vencimento da parcela.',
            'parcelas.*.data_vencimento.date_format' => 'A data de vencimento da parcela é inválida.',
        ];
    }
}
