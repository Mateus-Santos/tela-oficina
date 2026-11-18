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
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.quantidade_conferida' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999.999'],
            'itens.*.quantidade_avariada' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999.999'],
            'itens.*.quantidade_aceita' => ['nullable', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999.999'],
            'itens.*.observacao_divergencia' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'itens.required' => 'Informe os itens da conferência.',
            'itens.*.quantidade_conferida.required' => 'Informe a quantidade física recebida, inclusive zero.',
            'itens.*.quantidade_conferida.min' => 'A quantidade recebida não pode ser negativa.',
            'itens.*.quantidade_avariada.min' => 'A quantidade avariada não pode ser negativa.',
            'itens.*.quantidade_aceita.min' => 'A quantidade aceita não pode ser negativa.',
        ];
    }
}
