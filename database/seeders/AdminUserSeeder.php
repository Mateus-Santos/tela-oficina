<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pessoa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::where('email', env('ADMIN_EMAIL'))->first();

            $pessoa = Pessoa::updateOrCreate(
                ['id' => $user?->pessoa_id],
                [
                    'nome'            => env('ADMIN_NAME'),
                    'data_nascimento' => env('ADMIN_DATE'),
                    'telefone_1'      => env('ADMIN_TELL'),
                ]
            );

            User::updateOrCreate(
                ['email' => env('ADMIN_EMAIL')],
                [
                    'pessoa_id'   => $pessoa->id,
                    'password'    => Hash::make(env('ADMIN_PASSWORD')),
                    'colaborador' => intval(env('ADMIN_COLABORADOR')),
                    'permitions'  => intval(env('ADMIN_PERMITIONS')),
                ]
            );
        });
    }
}