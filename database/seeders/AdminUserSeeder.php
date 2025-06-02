<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'name' => env('ADMIN_NAME'),
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'colaborador' => intval(env('ADMIN_COLABORADOR')),
                'permitions' => intval(env('ADMIN_PERMITIONS')),
                'data_nascimento' => env('ADMIN_DATE'),
                'telefone_1' => env('ADMIN_TELL'),
            ]
        );
    }
}
