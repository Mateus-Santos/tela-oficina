<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
            ],

            'montadora_id' => [
                'required',
                'integer',
                'exists:montadoras,id',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'veículo',
            'montadora_id' => 'montadora',
        ];
    }
}
