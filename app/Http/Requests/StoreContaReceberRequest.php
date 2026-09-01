<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContaReceberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
                'required_without:nota_id',
            ],

            'nota_id' => [
                'nullable',
                'integer',
                'exists:notas,id',
            ],

            'categoria_financeira_id' => [
                'required',
                'integer',
                'exists:categorias_financeiras,id',
            ],

            'descricao' => [
                'required',
                'string',
                'max:255',
            ],

            'valor_original' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'desconto' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'juros' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'multa' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'data_emissao' => [
                'nullable',
                'date',
            ],

            'data_vencimento' => [
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
            'cliente_id.exists' => 'O cliente informado não existe.',
            'cliente_id.required_without' => 'Informe o cliente quando a conta não estiver vinculada a uma nota.',

            'nota_id.exists' => 'A nota informada não existe.',

            'categoria_financeira_id.required' => 'Informe a categoria financeira.',
            'categoria_financeira_id.exists' => 'A categoria financeira informada não existe.',

            'descricao.required' => 'Informe a descrição da conta.',
            'descricao.max' => 'A descrição não pode ultrapassar 255 caracteres.',

            'valor_original.required' => 'Informe o valor original.',
            'valor_original.numeric' => 'O valor original deve ser numérico.',
            'valor_original.gt' => 'O valor original deve ser maior que zero.',
            'valor_original.decimal' => 'O valor original deve possuir no máximo duas casas decimais.',

            'desconto.numeric' => 'O desconto deve ser numérico.',
            'desconto.min' => 'O desconto não pode ser negativo.',
            'desconto.decimal' => 'O desconto deve possuir no máximo duas casas decimais.',

            'juros.numeric' => 'Os juros devem ser numéricos.',
            'juros.min' => 'Os juros não podem ser negativos.',
            'juros.decimal' => 'Os juros devem possuir no máximo duas casas decimais.',

            'multa.numeric' => 'A multa deve ser numérica.',
            'multa.min' => 'A multa não pode ser negativa.',
            'multa.decimal' => 'A multa deve possuir no máximo duas casas decimais.',

            'data_emissao.date' => 'A data de emissão é inválida.',
            'data_vencimento.required' => 'Informe a data de vencimento.',
            'data_vencimento.date' => 'A data de vencimento é inválida.',

            'observacoes.string' => 'As observações devem ser um texto.',
        ];
    }
}
