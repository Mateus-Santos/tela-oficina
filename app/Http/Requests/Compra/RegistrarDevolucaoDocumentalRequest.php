<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarDevolucaoDocumentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'operacao_uuid' => ['required', 'uuid'],
            'referencia_documento' => ['required', 'string', 'max:80'],
            'chave_nfe' => ['nullable', 'regex:/\A[0-9]{44}\z/D'],
            'data_documento' => ['required', 'date_format:Y-m-d'],
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
            'confirmacao_fisica' => ['required', 'accepted'],
            'confirmacao_financeira' => ['required', 'accepted'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['required', 'array'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999.999'],
            'itens.*.modalidade' => ['required', Rule::in(['estoque', 'recusado'])],
        ];
    }
}
