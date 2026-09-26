<?php

namespace App\Http\Requests\Marca;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'logo_url' => $this->filled('logo_url')
                ? trim((string) $this->input('logo_url'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:150',
                Rule::unique('marcas', 'nome'),
            ],

            'logo' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'logo_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'ativo' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da marca é obrigatório.',
            'nome.string' => 'O nome da marca é inválido.',
            'nome.max' => 'O nome da marca não pode ter mais de 150 caracteres.',
            'nome.unique' => 'Já existe uma marca cadastrada com este nome.',

            'logo.image' => 'O arquivo da logo deve ser uma imagem.',
            'logo.max' => 'A logo não pode ter mais de 2 MB.',

            'logo_url.url' => 'A URL da logo deve ser uma URL válida.',
            'logo_url.max' => 'A URL da logo não pode ter mais de 2048 caracteres.',

            'ativo.boolean' => 'O status da marca é inválido.',
        ];
    }
}
