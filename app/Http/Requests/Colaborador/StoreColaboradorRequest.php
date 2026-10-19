<?php

namespace App\Http\Requests\Colaborador;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColaboradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'id_user' => [
                'required',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'perfil',
                        '!=',
                        User::PERFIL_ADMIN
                    )
                ),

                Rule::unique(
                    'colaboradores',
                    'id_user'
                ),
            ],

            'chave_pix' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'colaboradores',
                    'chave_pix'
                ),
            ],

            'conta_banco' => [
                'required',
                'string',
                'max:30',
            ],
        ];
    }
}
