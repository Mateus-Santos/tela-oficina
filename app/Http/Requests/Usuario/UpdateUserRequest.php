<?php

namespace App\Http\Requests\Usuario;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        $userId =
            $user instanceof User
                ? $user->id
                : $user;

        $pessoaId =
            $user instanceof User
                ? $user->pessoa_id
                : User::query()
                    ->findOrFail($userId)
                    ->pessoa_id;

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

                Rule::unique(
                    'users',
                    'email'
                )->ignore($userId),
            ],

            'cpf' => [
                'nullable',
                'string',
                'size:11',

                Rule::unique(
                    'pessoas',
                    'cpf'
                )->ignore($pessoaId),
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
