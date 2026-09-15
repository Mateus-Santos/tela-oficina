<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;

class ConferirCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'itens' => [
                'required',
                'array',
                'min:1',
            ],
            'itens.*.quantidade_conferida' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'itens.required' => 'É necessário informar os itens da conferência.',
            'itens.array' => 'Os itens informados são inválidos.',
            'itens.min' => 'É necessário informar pelo menos um item para conferência.',
            'itens.*.quantidade_conferida.required' => 'A quantidade recebida é obrigatória.',
            'itens.*.quantidade_conferida.numeric' => 'A quantidade recebida deve ser numérica.',
            'itens.*.quantidade_conferida.gt' => 'A quantidade recebida deve ser maior que zero.',
        ];
    }
}
