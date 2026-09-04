<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMontadoraRequest extends FormRequest
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
                'unique:montadoras,nome',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome da montadora',
        ];
    }
}
