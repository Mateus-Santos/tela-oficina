<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => [
                'required',
                'integer',
                'exists:fornecedores,id',
            ],

            'numero_nf' => [
                'required',
                'string',
                'max:255',
            ],

            'serie_nf' => [
                'nullable',
                'string',
                'max:50',
            ],

            'chave_nf' => [
                'nullable',
                'string',
                'size:44',
                'regex:/^[0-9]{44}$/',
                Rule::unique('compras', 'chave_nf'),
            ],

            'data_emissao' => [
                'nullable',
                'date',
            ],

            'data_entrada' => [
                'required',
                'date',
            ],

            /*
             * O valor será recalculado pela Action com base nos itens.
             * Mantemos a validação porque o campo faz parte da estrutura
             * recebida pelo formulário, mas o backend não confia nele.
             */
            'valor_produtos' => [
                'required',
                'numeric',
                'min:0',
            ],

            'desconto' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'frete' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'outras_despesas' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
             * Também é calculado pela Action.
             */
            'valor_total' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'observacoes' => [
                'nullable',
                'string',
            ],

            'itens' => [
                'required',
                'array',
                'min:1',
            ],

            'itens.*.produto_id' => [
                'required',
                'integer',
                'exists:produtos,id',
            ],

            'itens.*.descricao' => [
                'required',
                'string',
                'max:255',
            ],

            'itens.*.quantidade' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'itens.*.quantidade_conferida' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'itens.*.valor_unitario' => [
                'required',
                'numeric',
                'min:0',
            ],

            'itens.*.desconto' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
             * Também será recalculado pela Action.
             */
            'itens.*.valor_total' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fornecedor_id.required' => 'O fornecedor é obrigatório.',
            'fornecedor_id.integer' => 'O fornecedor informado é inválido.',
            'fornecedor_id.exists' => 'O fornecedor selecionado não existe.',

            'numero_nf.required' => 'O número da nota fiscal é obrigatório.',
            'numero_nf.string' => 'O número da nota fiscal é inválido.',
            'numero_nf.max' => 'O número da nota fiscal não pode ter mais de 255 caracteres.',

            'serie_nf.string' => 'A série da nota fiscal é inválida.',
            'serie_nf.max' => 'A série da nota fiscal não pode ter mais de 50 caracteres.',

            'chave_nf.string' => 'A chave da nota fiscal é inválida.',
            'chave_nf.size' => 'A chave da nota fiscal deve possuir exatamente 44 caracteres.',
            'chave_nf.regex' => 'A chave da nota fiscal deve conter somente números.',
            'chave_nf.unique' => 'Esta chave de nota fiscal já está cadastrada.',

            'data_emissao.date' => 'A data de emissão informada é inválida.',

            'data_entrada.required' => 'A data de entrada é obrigatória.',
            'data_entrada.date' => 'A data de entrada informada é inválida.',

            'valor_produtos.required' => 'O valor dos produtos é obrigatório.',
            'valor_produtos.numeric' => 'O valor dos produtos deve ser numérico.',
            'valor_produtos.min' => 'O valor dos produtos não pode ser negativo.',

            'desconto.numeric' => 'O desconto deve ser numérico.',
            'desconto.min' => 'O desconto não pode ser negativo.',

            'frete.numeric' => 'O frete deve ser numérico.',
            'frete.min' => 'O frete não pode ser negativo.',

            'outras_despesas.numeric' => 'O valor das outras despesas deve ser numérico.',
            'outras_despesas.min' => 'O valor das outras despesas não pode ser negativo.',

            'valor_total.numeric' => 'O valor total deve ser numérico.',
            'valor_total.min' => 'O valor total não pode ser negativo.',

            'observacoes.string' => 'As observações informadas são inválidas.',

            'itens.required' => 'É necessário informar pelo menos um item.',
            'itens.array' => 'Os itens informados são inválidos.',
            'itens.min' => 'É necessário informar pelo menos um item.',

            'itens.*.produto_id.required' => 'O produto é obrigatório.',
            'itens.*.produto_id.integer' => 'O produto informado é inválido.',
            'itens.*.produto_id.exists' => 'O produto selecionado não existe.',

            'itens.*.descricao.required' => 'A descrição do item é obrigatória.',
            'itens.*.descricao.string' => 'A descrição do item é inválida.',
            'itens.*.descricao.max' => 'A descrição do item não pode ter mais de 255 caracteres.',

            'itens.*.quantidade.required' => 'A quantidade do item é obrigatória.',
            'itens.*.quantidade.numeric' => 'A quantidade deve ser numérica.',
            'itens.*.quantidade.gt' => 'A quantidade deve ser maior que zero.',

            'itens.*.quantidade_conferida.numeric' => 'A quantidade conferida deve ser numérica.',
            'itens.*.quantidade_conferida.gte' => 'A quantidade conferida não pode ser negativa.',

            'itens.*.valor_unitario.required' => 'O valor unitário é obrigatório.',
            'itens.*.valor_unitario.numeric' => 'O valor unitário deve ser numérico.',
            'itens.*.valor_unitario.min' => 'O valor unitário não pode ser negativo.',

            'itens.*.desconto.numeric' => 'O desconto do item deve ser numérico.',
            'itens.*.desconto.min' => 'O desconto do item não pode ser negativo.',

            'itens.*.valor_total.required' => 'O valor total do item é obrigatório.',
            'itens.*.valor_total.numeric' => 'O valor total do item deve ser numérico.',
            'itens.*.valor_total.min' => 'O valor total do item não pode ser negativo.',
        ];
    }
}
