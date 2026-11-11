<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CadastrarClienteMarketingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
        if (is_string($this->input('whatsapp'))) {
            $this->merge(['whatsapp' => preg_replace('/\D+/', '', $this->input('whatsapp'))]);
        }
        if (is_string($this->input('nome'))) {
            $this->merge(['nome' => trim($this->input('nome'))]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'whatsapp' => ['required', 'regex:/^[0-9]{10,13}$/'],
            'senha' => ['required', 'string', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'aceite_privacidade' => ['required', 'accepted'],
            'website' => ['prohibited'],
        ];
    }
}
