<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => [
                'nullable',
                'exists:fornecedores,id',
            ],
            'nota_id' => [
                'nullable',
                'exists:notas,id',
            ],
            'categoria_financeira_id' => [
                'nullable',
                Rule::exists('categorias_financeiras', 'id')
                    ->where(fn ($query) => $query
                        ->where('tipo', 'saida')
                        ->where('ativo', true)),
            ],
            'forma_pagamento_id' => [
                'nullable',
                Rule::exists('formas_pagamento', 'id')
                    ->where(fn ($query) => $query->where('ativo', true)),
            ],
            'descricao' => [
                'required',
                'string',
                'max:255',
            ],
            'valor' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'data_emissao' => [
                'required',
                'date',
            ],
            'data_vencimento' => [
                'required',
                'date',
                'after_or_equal:data_emissao',
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
            'fornecedor_id.exists' => 'O fornecedor informado não existe.',
            'nota_id.exists' => 'A nota informada não existe.',
            'categoria_financeira_id.exists' =>
                'A categoria financeira selecionada deve ser uma categoria de saída ativa.',
            'forma_pagamento_id.exists' =>
                'A forma de pagamento selecionada não está disponível.',
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max' => 'A descrição não pode ter mais de 255 caracteres.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.numeric' => 'O valor deve ser numérico.',
            'valor.gt' => 'O valor deve ser maior que zero.',
            'data_emissao.required' => 'A data de emissão é obrigatória.',
            'data_emissao.date' => 'A data de emissão deve ser uma data válida.',
            'data_vencimento.required' => 'A data de vencimento é obrigatória.',
            'data_vencimento.date' => 'A data de vencimento deve ser uma data válida.',
            'data_vencimento.after_or_equal' =>
                'A data de vencimento não pode ser anterior à data de emissão.',
        ];
    }
}
