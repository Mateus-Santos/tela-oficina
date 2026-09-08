<?php

namespace App\Http\Requests\ContasPagar;

use Illuminate\Foundation\Http\FormRequest;

class StoreContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => ['nullable', 'exists:fornecedores,id'],
            'nota_id' => ['nullable', 'exists:notas,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'data_emissao' => ['required', 'date'],
            'data_vencimento' => ['required', 'date', 'after_or_equal:data_emissao'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fornecedor_id.exists' => 'O fornecedor informado não existe.',
            'nota_id.exists' => 'A nota informada não existe.',
            'descricao.required' => 'A descrição é obrigatória.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.numeric' => 'O valor deve ser numérico.',
            'valor.gt' => 'O valor deve ser maior que zero.',
            'data_emissao.required' => 'A data de emissão é obrigatória.',
            'data_vencimento.required' => 'A data de vencimento é obrigatória.',
            'data_vencimento.after_or_equal' => 'A data de vencimento não pode ser anterior à data de emissão.',
        ];
    }
}
