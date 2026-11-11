<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

final class VincularClienteLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'protocolo_confirmacao' => ['required', 'string', 'max:40'],
            'confirmar_identidade' => ['required', 'accepted'],
        ];
    }
}
