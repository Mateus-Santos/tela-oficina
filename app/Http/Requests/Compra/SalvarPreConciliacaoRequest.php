<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarPreConciliacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tratamento_proposto' => ['required', Rule::in(['abatimento', 'credito', 'reembolso', 'sem_ajuste'])],
            'valor_proposto' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999.99'],
            'referencia_documento' => ['nullable', 'string', 'max:100'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
