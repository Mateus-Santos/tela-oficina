<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMontadoraRequest extends FormRequest
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
                Rule::unique('montadoras', 'nome')
                    ->ignore($this->route('montadora')),
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
