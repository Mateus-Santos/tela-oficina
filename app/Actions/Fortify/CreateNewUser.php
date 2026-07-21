<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Pessoa;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        // Tratamento dos campos
        $input['cpf'] = preg_replace('/\D/', '', $input['cpf'] ?? '');
        $input['rg'] = preg_replace('/\D/', '', $input['rg'] ?? '');
        $input['telefone_1'] = preg_replace('/\D/', '', $input['telefone_1'] ?? '');
        $input['telefone_2'] = preg_replace('/\D/', '', $input['telefone_2'] ?? '');

        Validator::make($input, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => $this->passwordRules(),
            'cpf' => 'nullable|string|size:11|unique:pessoas,cpf',
            'rg' => 'nullable|string',
            'data_nascimento' => 'required|date',
            'telefone_1' => 'required|string',
            'telefone_2' => 'nullable|string',
        ])->validate();

        // Usamos Transaction para garantir integridade (se falhar em um, cancela tudo)
        return DB::transaction(function () use ($input) {
            $pessoa = Pessoa::create([
                'nome' => $input['name'],
                'cpf' => $input['cpf'] ?: null,
                'rg' => $input['rg'] ?: null,
                'data_nascimento' => $input['data_nascimento'],
                'telefone_1' => $input['telefone_1'],
                'telefone_2' => $input['telefone_2'],
            ]);

            $user = User::create([
                'pessoa_id' => $pessoa->id,
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'permitions' => 2,
            ]);

            Cliente::create([
                'pessoa_id' => $pessoa->id,
                'pontos' => 0,
            ]);

            return $user;
        });
    }
}