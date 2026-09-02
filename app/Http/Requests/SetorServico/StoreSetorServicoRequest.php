<?php

namespace App\Http\Requests\SetorServico;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetorServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'setor' => [
                'required',
                'string',
                'max:255',
                'unique:setor_servicos,setor',
            ],

            'nivel' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'setor.required' => 'Informe o setor de serviço.',
            'setor.string' => 'O setor de serviço deve ser um texto.',
            'setor.max' => 'O setor de serviço não pode ter mais de 255 caracteres.',
            'setor.unique' => 'Este setor de serviço já está cadastrado.',

            'nivel.required' => 'Informe o nível do setor.',
            'nivel.string' => 'O nível deve ser um texto.',
            'nivel.max' => 'O nível não pode ter mais de 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'setor' => $this->transformarTexto($this->setor),
            'nivel' => $this->transformarTexto($this->nivel),
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
