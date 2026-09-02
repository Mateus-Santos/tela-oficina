<?php

namespace App\Http\Requests\VeiculosClientes;

use App\Models\VeiculosCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVeiculosClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = auth()->user();
        $veiculoClienteId = $this->route('veiculoscliente');

        return [
            'placa' => [
                'required',
                'string',
                'max:7',
                'regex:/^[A-Z]{3}[0-9]{4}$|^[A-Z]{3}[0-9][A-Z][0-9]{2}$/',
                Rule::unique('veiculos_clientes', 'placa')
                    ->ignore($veiculoClienteId, 'id'),
            ],

            'ano' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],

            'cor' => [
                'nullable',
                'string',
                'max:20',
            ],

            'veiculo_id' => [
                'required',
                'integer',
                'exists:veiculos,id',
            ],

            'id_cliente' => $user->permitions == 1
                ? [
                    'required',
                    'integer',
                    'exists:clientes,id',
                ]
                : [
                    'nullable',
                ],
        ];
    }

    public function messages(): array
    {
        return [
            'placa.required' => 'Informe a placa do veículo.',
            'placa.string' => 'A placa deve ser um texto.',
            'placa.max' => 'A placa deve possuir no máximo 7 caracteres.',
            'placa.regex' => 'Informe uma placa válida. Exemplos: ABC1234 ou ABC1D23.',
            'placa.unique' => 'Já existe outro veículo cadastrado com esta placa.',

            'ano.required' => 'Informe o ano do veículo.',
            'ano.integer' => 'O ano deve ser um número inteiro.',
            'ano.min' => 'Informe um ano válido.',
            'ano.max' => 'O ano informado não pode ser superior ao próximo ano.',

            'cor.string' => 'A cor deve ser um texto.',
            'cor.max' => 'A cor não pode possuir mais de 20 caracteres.',

            'veiculo_id.required' => 'Selecione o veículo.',
            'veiculo_id.integer' => 'O veículo selecionado é inválido.',
            'veiculo_id.exists' => 'O veículo selecionado não existe.',

            'id_cliente.required' => 'Selecione o cliente.',
            'id_cliente.integer' => 'O cliente selecionado é inválido.',
            'id_cliente.exists' => 'O cliente selecionado não existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $placa = strtoupper(
            preg_replace('/[^A-Z0-9]/', '', $this->placa ?? '')
        );

        $this->merge([
            'placa' => $placa,
            'cor' => $this->transformarTexto($this->cor),
        ]);
    }

    private function transformarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $valor));
    }
}
