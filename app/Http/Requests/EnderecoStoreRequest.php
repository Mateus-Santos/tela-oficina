<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnderecoStoreRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cep' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'numero' => 'required|integer',
            'ponto_referencia' => 'required|string|max:255',
            'id_pessoa' => 'required|integer|exists:pessoa,id_pessoa',
        ];
    }

    public function messages()
    {
        return [
            'cep.required' => 'O campo CEP é obrigatório.',
            'cep.string' => 'O campo CEP deve ser uma string.',
            'cep.max' => 'O campo CEP não pode ter mais de 10 caracteres.',
            'city.required' => 'O campo cidade é obrigatório.',
            'neighborhood.required' => 'O campo bairro é obrigatório.',
            'region.required' => 'O campo estado é obrigatório.',
            'address.required' => 'O campo rua é obrigatório.',
            'numero.integer' => 'O campo número deve ser um número inteiro.',
            'id_pessoa.required' => 'O campo ID da pessoa é obrigatório.',
            'id_pessoa.exists' => 'O ID da pessoa fornecido não existe.',
        ];
    }
}
