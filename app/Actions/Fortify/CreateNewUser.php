<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Pessoa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        //Tratando os dados importantes.
        $input['cpf'] = str_replace(['.', '-'], "", $input['cpf']);
        $input['rg'] = str_replace(['.', '-', ' ', '(', ')'], "", $input['rg']);
        $input['telefone_1'] = str_replace(['-', ' ', '(', ')'], "", $input['telefone_1']);
        $input['telefone_2'] = str_replace(['-', ' ', '(', ')'], "", $input['telefone_2']);

        //Realizando validações de dados
        $validator = Validator::make($input, [
            'cpf' => 'string|unique:pessoa,cpf|min:11',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'data_nascimento' => 'required|date',
            'rg' => 'string|min:9',
            'telefone_1' => 'required|string',
            'telefone_2' => 'string',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'Por favor, insira um endereço de email válido.',
            'email.unique' => 'Este endereço de email já está registrado.',
            'cpf.unique' => 'Este CPF já está registrado.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
        ]);

        // Verificar se a validação passou
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        //Realizando cadastro
        $validated = $validator->validated();

        $user = User::create([
            'nome' => $validated['name'],
            'data_nascimento' => $validated['data_nascimento'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'rg' => $validated['rg'],
            'telefone_1' => $validated['telefone_1'],
            'telefone_2' => $validated['telefone_2'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
        ]);

        Cliente::create([
            'id_user' => $user->id,
            'pontos' => 0,
        ]);

        return $user;
    }
}
