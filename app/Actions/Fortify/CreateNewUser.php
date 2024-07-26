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

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $input['cpf'] = str_replace(['.', '-'], "", $input['cpf']);
        $input['rg'] = str_replace(['-', ' ', '(', ')'], "", $input['rg']);
        $input['telefone_1'] = str_replace(['-', ' ', '(', ')'], "", $input['telefone_1']);
        $input['telefone_2'] = str_replace(['-', ' ', '(', ')'], "", $input['telefone_2']);

        $pessoa = Pessoa::create([
            'nome' => $input['name'],
            'data_nascimento' => $input['data_nascimento'],
            'email' => $input['email'],
            'cpf' => $input['cpf'],
            'rg' => $input['rg'],
            'telefone_1' => $input['telefone_1'],
            'telefone_2' => $input['telefone_2'],
        ]);

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        Cliente::create([
            'id_pessoa' => $pessoa->id_pessoa,
            'id_user' => $user->id,
            'pontos' => 0,
        ]);

        return $user;
    }
}
