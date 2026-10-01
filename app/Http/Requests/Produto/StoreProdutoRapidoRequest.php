<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdutoRapidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'nome' => $this->nome
                ? mb_strtoupper(
                    trim($this->nome)
                )
                : null,

            'descricao' => $this->descricao
                ? trim($this->descricao)
                : null,

            'codigo_fabricante' =>
                $this->codigo_fabricante
                    ? trim(
                        $this->codigo_fabricante
                    )
                    : null,

            'codigo_barras' =>
                $this->codigo_barras
                    ? trim(
                        $this->codigo_barras
                    )
                    : null,
        ];

        if ($this->filled('preco_uni')) {
            $preco =
                (string) $this->input(
                    'preco_uni'
                );

            if (str_contains($preco, ',')) {
                $preco =
                    str_replace(
                        '.',
                        '',
                        $preco
                    );

                $preco =
                    str_replace(
                        ',',
                        '.',
                        $preco
                    );
            }

            $dados['preco_uni'] =
                $preco;
        }

        $this->merge($dados);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],

            'veiculo_cliente_id' => [
                'required',
                'integer',
                'exists:veiculos_clientes,id',
            ],

            'nome' => [
                'required',
                'string',
                'max:150',
            ],

            'marca_id' => [
                'required',
                'integer',
                Rule::exists(
                    'marcas',
                    'id'
                )->where(
                    'ativo',
                    true
                ),
            ],

            'descricao' => [
                'required',
                'string',
            ],

            'codigo_fabricante' => [
                'required',
                'string',
                Rule::unique(
                    'produtos',
                    'codigo_fabricante'
                ),
            ],

            'codigo_barras' => [
                'nullable',
                'string',
                Rule::unique(
                    'produtos',
                    'codigo_barras'
                ),
            ],

            'preco_uni' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantidade' => [
                'required',
                'integer',
                'min:0',
            ],

            'estoque_minimo' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' =>
                'Selecione o cliente da Nota.',

            'veiculo_cliente_id.required' =>
                'Selecione o veículo da Nota.',

            'nome.required' =>
                'Informe o nome do produto.',

            'marca_id.required' =>
                'Selecione a marca do produto.',

            'marca_id.exists' =>
                'A marca selecionada não existe ou está inativa.',

            'descricao.required' =>
                'Informe a descrição do produto.',

            'codigo_fabricante.required' =>
                'Informe o código do fabricante.',

            'codigo_fabricante.unique' =>
                'Já existe um produto com este código de fabricante.',

            'codigo_barras.unique' =>
                'Já existe um produto com este código de barras.',

            'preco_uni.required' =>
                'Informe o preço unitário.',

            'preco_uni.numeric' =>
                'O preço informado é inválido.',

            'quantidade.required' =>
                'Informe a quantidade em estoque.',

            'quantidade.integer' =>
                'A quantidade deve ser um número inteiro.',

            'estoque_minimo.integer' =>
                'O estoque mínimo deve ser um número inteiro.',
        ];
    }
}
