<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstornarRecebimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'O motivo do estorno é obrigatório.',
            'motivo.string' => 'O motivo do estorno deve ser um texto.',
            'motivo.min' => 'O motivo do estorno deve possuir pelo menos 3 caracteres.',
            'motivo.max' => 'O motivo do estorno não pode ultrapassar 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'motivo' => 'motivo do estorno',
        ];
    }
}
