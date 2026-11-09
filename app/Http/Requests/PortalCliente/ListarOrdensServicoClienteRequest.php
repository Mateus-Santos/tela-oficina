<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;

class ListarOrdensServicoClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCliente() === true;
    }

    public function rules(): array
    {
        return [
            'veiculo' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'status' => [
                'nullable',
                'string',
                'in:aberta,finalizada,cancelada',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'veiculo.integer' => 'Selecione um veículo válido.',
            'veiculo.min' => 'Selecione um veículo válido.',
            'status.in' => 'Selecione uma situação válida.',
        ];
    }
}
