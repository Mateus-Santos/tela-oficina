<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;

class EfetivarConciliacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'operacao_uuid' => ['required', 'uuid'],
            'confirmacao' => ['required', 'accepted'],
            'documento_confirmacao' => ['required', 'string', 'min:5', 'max:100'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
            'conta_pagar_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
