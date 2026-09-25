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
                Rule::exists('notas', 'id')->where(fn ($query) => $query->where('status', 'Finalizado')),
                Rule::unique('contas_receber', 'nota_id'),
            ],

            'categoria_financeira_id' => [
                'required',
                'integer',
                Rule::exists('categorias_financeiras', 'id')->where(fn ($query) => $query
                    ->where('tipo', 'entrada')
                    ->where('ativo', true)),
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
                'date_format:Y-m-d',
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

            'nota_id.exists' => 'A nota informada não existe ou ainda não está finalizada.',
            'nota_id.unique' => 'A nota informada já possui uma conta a receber.',

            'categoria_financeira_id.required' => 'Informe a categoria financeira.',
            'categoria_financeira_id.exists' => 'A categoria financeira deve ser uma categoria de entrada ativa.',

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

            'data_emissao.date_format' => 'A data de emissão é inválida.',

            'parcelas.required' => 'Informe ao menos uma parcela.',
            'parcelas.array' => 'As parcelas informadas são inválidas.',
            'parcelas.min' => 'Informe ao menos uma parcela.',

            'parcelas.*.numero.required' => 'Informe o número de todas as parcelas.',
            'parcelas.*.numero.integer' => 'O número da parcela deve ser inteiro.',
            'parcelas.*.numero.min' => 'O número da parcela deve ser maior que zero.',
            'parcelas.*.numero.distinct' => 'Existem parcelas com números repetidos.',

            'parcelas.*.valor.required' => 'Informe o valor de todas as parcelas.',
            'parcelas.*.valor.numeric' => 'O valor da parcela deve ser numérico.',
            'parcelas.*.valor.gt' => 'O valor da parcela deve ser maior que zero.',
            'parcelas.*.valor.decimal' => 'O valor da parcela deve possuir no máximo duas casas decimais.',

            'parcelas.*.data_vencimento.required' => 'Informe o vencimento de todas as parcelas.',
            'parcelas.*.data_vencimento.date_format' => 'Existe uma data de vencimento inválida.',

            'observacoes.string' => 'As observações devem ser um texto.',
        ];
    }
}
