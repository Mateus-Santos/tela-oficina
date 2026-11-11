<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class ConverterLeadEmNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'precos' => ['required', 'array', 'min:1', 'max:10'],
            'precos.*' => ['required', 'numeric', 'gt:0', 'max:999999.99', 'decimal:0,2'],
            'confirmar_revisao' => ['required', 'accepted'],
        ];
    }
}
