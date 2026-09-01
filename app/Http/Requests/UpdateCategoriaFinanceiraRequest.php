<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaFinanceiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $categoria = $this->route('categoriaFinanceira');

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias_financeiras', 'nome')
                    ->where(function ($query) {
                        return $query->where(
                            'tipo',
                            $this->input('tipo')
                        );
                    })
                    ->ignore($categoria),
            ],

            'tipo' => [
                'required',
                Rule::in(['entrada', 'saida']),
            ],

            'ativo' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da categoria.',
            'nome.string' => 'O nome da categoria deve ser um texto.',
            'nome.max' => 'O nome da categoria não pode ultrapassar 255 caracteres.',
            'nome.unique' => 'Já existe uma categoria com este nome para este tipo.',

            'tipo.required' => 'Informe o tipo da categoria.',
            'tipo.in' => 'O tipo da categoria deve ser entrada ou saída.',

            'ativo.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
        ];
    }
}
