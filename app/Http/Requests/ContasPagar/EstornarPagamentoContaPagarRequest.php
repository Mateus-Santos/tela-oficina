<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;

class EstornarPagamentoContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'O motivo do estorno é obrigatório.',
            'motivo.max' => 'O motivo do estorno não pode ultrapassar 1000 caracteres.',
        ];
    }
}
