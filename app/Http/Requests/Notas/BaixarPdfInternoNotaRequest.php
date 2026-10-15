<?php

namespace App\Http\Requests\Notas;

use Illuminate\Foundation\Http\FormRequest;

class BaixarPdfInternoNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin()
            ?? false;
    }

    public function rules(): array
    {
        return [
            'senha' => [
                'required',
                'string',
                'min:4',
                'max:64',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'senha.required' => 'Informe uma senha para proteger o PDF interno.',

            'senha.min' => 'A senha deve possuir pelo menos 4 caracteres.',

            'senha.max' => 'A senha pode possuir no máximo 64 caracteres.',
        ];
    }
}
