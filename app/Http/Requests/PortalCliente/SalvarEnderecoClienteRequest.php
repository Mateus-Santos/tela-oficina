<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarEnderecoClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCliente() === true
            && $this->user()?->pessoa_id !== null;
    }

    protected function prepareForValidation(): void
    {
        $dados = [];

        if (is_string($this->input('cep'))) {
            $cep = preg_replace('/\D+/', '', $this->input('cep'));

            $dados['cep'] = strlen($cep) === 8
                ? substr($cep, 0, 5).'-'.substr($cep, 5)
                : $cep;
        }

        if (is_string($this->input('estado'))) {
            $dados['estado'] = strtoupper(
                trim($this->input('estado'))
            );
        }

        foreach ([
            'cidade', 'bairro', 'rua',
            'numero', 'ponto_referencia'
        ] as $campo) {
            if (is_string($this->input($campo))) {
                $dados[$campo] = trim($this->input($campo));
            }
        }

        $this->merge($dados);
    }

    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'regex:/^\d{5}-\d{3}$/'],
            'estado' => [
                'required',
                'string',
                Rule::in([
                    'AC','AL','AP','AM','BA','CE','DF',
                    'ES','GO','MA','MT','MS','PA','PB',
                    'PR','PE','PI','RJ','RN','RS','RO',
                    'RR','SC','SP','SE','TO',
                ]),
            ],
            'cidade' => ['required', 'string', 'max:50'],
            'bairro' => ['required', 'string', 'max:50'],
            'rua' => ['required', 'string', 'max:100'],
            'numero' => ['required', 'string', 'max:20'],
            'ponto_referencia' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'cep.regex' => 'Informe um CEP válido com oito dígitos.',
            'estado.in' => 'Selecione uma UF válida.',
            'cidade.max' => 'A cidade pode ter no máximo 50 caracteres.',
            'bairro.max' => 'O bairro pode ter no máximo 50 caracteres.',
            'rua.max' => 'A rua pode ter no máximo 100 caracteres.',
            'numero.max' => 'O número pode ter no máximo 20 caracteres.',
            'ponto_referencia.max' => 'A referência pode ter no máximo 200 caracteres.',
        ];
    }
}
