<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;

class ListarProdutosDisponiveisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCliente() === true;
    }

    public function rules(): array
    {
        return [
            'busca' => [
                'nullable',
                'string',
                'max:80',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'busca.string' => 'Informe um texto válido para a busca.',
            'busca.max' => 'A busca deve ter no máximo 80 caracteres.',
        ];
    }
}
