<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'cpf' => [
                'nullable',
                'string',
                'size:11',
                'unique:pessoas,cpf',
            ],

            'rg' => [
                'nullable',
                'string',
                'max:14',
            ],

            'data_nascimento' => [
                'nullable',
                'date',
            ],

            'telefone_1' => [
                'nullable',
                'string',
                'min:10',
                'max:11',
            ],

            'telefone_2' => [
                'nullable',
                'string',
                'min:10',
                'max:11',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->somenteNumeros(
                $this->input('cpf')
            ),

            'rg' => $this->somenteNumeros(
                $this->input('rg')
            ),

            'telefone_1' => $this->somenteNumeros(
                $this->input('telefone_1')
            ),

            'telefone_2' => $this->somenteNumeros(
                $this->input('telefone_2')
            ),
        ]);
    }

    private function somenteNumeros(
        ?string $valor
    ): ?string {
        if (! $valor) {
            return null;
        }

        $valor = preg_replace(
            '/\D/',
            '',
            $valor
        );

        return $valor !== ''
            ? $valor
            : null;
    }
}
