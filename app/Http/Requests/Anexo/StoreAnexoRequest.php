<?php

namespace App\Http\Requests\Anexo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivo' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,xml'],
            'tipo' => [
                'required',
                'string',
                'max:50',
                Rule::in([
                    'nf',
                    'nf_xml',
                    'foto',
                    'comprovante',
                    'boleto',
                    'contrato',
                    'orcamento',
                    'conta_luz',
                    'conta_agua',
                    'conta_telefone',
                    'recibo',
                    'outro',
                ]),
            ],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.required' => 'O arquivo é obrigatório.',
            'arquivo.file' => 'O arquivo enviado é inválido.',
            'arquivo.max' => 'O arquivo não pode ter mais de 20 MB.',
            'arquivo.mimes' => 'O arquivo deve ser PDF, JPG, JPEG, PNG, WEBP ou XML.',
            'tipo.required' => 'O tipo do anexo é obrigatório.',
            'tipo.in' => 'O tipo de anexo informado é inválido.',
            'observacoes.string' => 'As observações informadas são inválidas.',
        ];
    }
}
