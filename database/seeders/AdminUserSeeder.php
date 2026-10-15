<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(
            function () {
                $user =
                    User::where(
                        'email',
                        env(
                            'ADMIN_EMAIL'
                        )
                    )->first();

                $pessoa =
                    Pessoa::updateOrCreate(
                        [
                            'id' => $user
                                ?->pessoa_id,
                        ],
                        [
                            'nome' => env(
                                'ADMIN_NAME'
                            ),

                            'data_nascimento' => env(
                                'ADMIN_DATE'
                            ),

                            'telefone_1' => env(
                                'ADMIN_TELL'
                            ),
                        ]
                    );

                User::updateOrCreate(
                    [
                        'email' => env(
                            'ADMIN_EMAIL'
                        ),
                    ],
                    [
                        'pessoa_id' => $pessoa->id,

                        'password' => Hash::make(
                            env(
                                'ADMIN_PASSWORD'
                            )
                        ),

                        'perfil' => User::PERFIL_ADMIN,

                        'status' => true,
                    ]
                );
            }
        );
    }
}
