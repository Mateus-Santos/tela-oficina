<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;

class AprovarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gerar_conta' => [
                'required',
                'boolean',
            ],

            'parcelas' => [
                'required_if:gerar_conta,1',
                'array',
                'min:1',
            ],

            'parcelas.*.numero' => [
                'required_if:gerar_conta,1',
                'integer',
                'min:1',
            ],

            'parcelas.*.valor' => [
                'required_if:gerar_conta,1',
                'numeric',
                'gt:0',
            ],

            'parcelas.*.data_vencimento' => [
                'required_if:gerar_conta,1',
                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'gerar_conta.required' => 'Informe se deseja gerar uma conta a pagar.',
            'gerar_conta.boolean' => 'A opção de geração da conta é inválida.',

            'parcelas.required_if' => 'Informe as parcelas da conta a pagar.',
            'parcelas.array' => 'As parcelas informadas são inválidas.',
            'parcelas.min' => 'Informe pelo menos uma parcela.',

            'parcelas.*.numero.required_if' => 'O número da parcela é obrigatório.',
            'parcelas.*.numero.integer' => 'O número da parcela deve ser inteiro.',
            'parcelas.*.numero.min' => 'O número da parcela deve ser maior que zero.',

            'parcelas.*.valor.required_if' => 'O valor da parcela é obrigatório.',
            'parcelas.*.valor.numeric' => 'O valor da parcela deve ser numérico.',
            'parcelas.*.valor.gt' => 'O valor da parcela deve ser maior que zero.',

            'parcelas.*.data_vencimento.required_if' => 'A data de vencimento da parcela é obrigatória.',
            'parcelas.*.data_vencimento.date_format' => 'A data de vencimento deve estar no formato YYYY-MM-DD.',
        ];
    }
}
