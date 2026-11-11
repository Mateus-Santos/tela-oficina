<?php

namespace App\Http\Requests\Clientes;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->somenteDigitos($this->input('cpf')),
            'rg' => $this->somenteDigitos($this->input('rg')),
            'telefone_1' => $this->somenteDigitos($this->input('telefone_1')),
            'telefone_2' => $this->somenteDigitos($this->input('telefone_2')),
        ]);
    }

    public function rules(): array
    {
        $cliente = Cliente::findOrFail($this->route('cliente'));

        $usuario = User::where(
            'pessoa_id',
            $cliente->pessoa_id
        )->first();

        $regras = [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => [
                'nullable',
                'string',
                'size:11',
                Rule::unique('pessoas', 'cpf')->ignore($cliente->pessoa_id),
            ],
            'rg' => ['nullable', 'string', 'max:14'],
            'data_nascimento' => ['nullable', 'date'],
            'telefone_1' => ['nullable', 'string', 'min:10', 'max:11'],
            'telefone_2' => ['nullable', 'string', 'min:10', 'max:11'],
            'pontos' => ['nullable', 'integer', 'min:0'],
        ];

        if ($usuario) {
            $regras['email'] = [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ];
        }

        return $regras;
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório para usuários de acesso.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'cpf.unique' => 'Este CPF já pertence a outra pessoa.',
            'cpf.size' => 'O CPF deve possuir exatamente 11 dígitos.',
            'rg.max' => 'O campo RG não pode ter mais que 14 dígitos.',
            'telefone_1.min' => 'O Telefone Principal deve ter pelo menos 10 dígitos.',
            'telefone_1.max' => 'O Telefone Principal não pode ter mais que 11 dígitos.',
            'telefone_2.min' => 'O Telefone Secundário deve ter pelo menos 10 dígitos.',
            'telefone_2.max' => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
        ];
    }

    private function somenteDigitos(mixed $valor): mixed
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (! is_string($valor)) {
            return $valor;
        }

        $limpo = preg_replace('/\D/', '', $valor);

        return $limpo !== '' ? $limpo : null;
    }
}
