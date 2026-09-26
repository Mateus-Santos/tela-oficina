<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'nome' => $this->nome
                ? mb_strtoupper(trim($this->nome))
                : null,

            'descricao' => $this->descricao
                ? trim($this->descricao)
                : null,

            'codigo_fabricante' => $this->codigo_fabricante
                ? trim($this->codigo_fabricante)
                : null,

            'codigo_barras' => $this->codigo_barras
                ? trim($this->codigo_barras)
                : null,

            'ncm' => $this->ncm
                ? trim($this->ncm)
                : null,

            'cest' => $this->cest
                ? trim($this->cest)
                : null,

            'ex_tipi' => $this->ex_tipi
                ? trim($this->ex_tipi)
                : null,

            'unidade_comercial' => $this->unidade_comercial
                ? trim($this->unidade_comercial)
                : null,

            'unidade_tributavel' => $this->unidade_tributavel
                ? trim($this->unidade_tributavel)
                : null,
        ];

        if ($this->filled('preco_uni')) {
            $preco = $this->input('preco_uni');

            if (str_contains($preco, ',')) {
                $preco = str_replace('.', '', $preco);
                $preco = str_replace(',', '.', $preco);
            }

            $dados['preco_uni'] = $preco;
        }

        $this->merge($dados);
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:150',
            ],

            'marca_id' => [
                'required',
                'integer',
                Rule::exists('marcas', 'id')
                    ->where('ativo', true),
            ],

            'descricao' => [
                'required',
                'string',
            ],

            'preco_uni' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantidade' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'codigo_fabricante' => [
                'required',
                'string',
                Rule::unique('produtos', 'codigo_fabricante'),
            ],

            'codigo_barras' => [
                'nullable',
                'string',
                Rule::unique('produtos', 'codigo_barras'),
            ],

            'img' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'ncm' => [
                'nullable',
                'string',
                'max:20',
            ],

            'cest' => [
                'nullable',
                'string',
                'max:20',
            ],

            'ex_tipi' => [
                'nullable',
                'string',
                'max:20',
            ],

            'origem_mercadoria' => [
                'nullable',
                'integer',
            ],

            'unidade_comercial' => [
                'nullable',
                'string',
                'max:10',
            ],

            'unidade_tributavel' => [
                'nullable',
                'string',
                'max:10',
            ],

            'fator_conversao' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'peso_liquido' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'peso_bruto' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'veiculos' => [
                'required',
                'array',
            ],

            'veiculos.*' => [
                'exists:veiculos,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.string' => 'O nome do produto é inválido.',
            'nome.max' => 'O nome do produto não pode ter mais de 150 caracteres.',

            'marca_id.required' => 'A marca do produto é obrigatória.',
            'marca_id.integer' => 'A marca selecionada é inválida.',
            'marca_id.exists' => 'A marca selecionada não existe ou está inativa.',

            'descricao.required' => 'A descrição do produto é obrigatória.',
            'descricao.string' => 'A descrição do produto é inválida.',

            'preco_uni.required' => 'O preço unitário é obrigatório.',
            'preco_uni.numeric' => 'O preço unitário deve ser um valor numérico.',
            'preco_uni.min' => 'O preço unitário não pode ser negativo.',

            'quantidade.integer' => 'A quantidade deve ser um número inteiro.',
            'quantidade.min' => 'A quantidade não pode ser negativa.',

            'codigo_fabricante.required' => 'O código do fabricante é obrigatório.',
            'codigo_fabricante.string' => 'O código do fabricante é inválido.',
            'codigo_fabricante.unique' => 'Já existe um produto cadastrado com este código de fabricante.',

            'codigo_barras.string' => 'O código de barras é inválido.',
            'codigo_barras.unique' => 'Já existe um produto cadastrado com este código de barras.',

            'img.image' => 'O arquivo informado deve ser uma imagem.',
            'img.max' => 'A imagem não pode ter mais de 2 MB.',

            'ncm.string' => 'O NCM informado é inválido.',
            'ncm.max' => 'O NCM não pode ultrapassar 20 caracteres.',

            'cest.string' => 'O CEST informado é inválido.',
            'cest.max' => 'O CEST não pode ultrapassar 20 caracteres.',

            'ex_tipi.string' => 'O EX-TIPI informado é inválido.',
            'ex_tipi.max' => 'O EX-TIPI não pode ultrapassar 20 caracteres.',

            'origem_mercadoria.integer' => 'A origem da mercadoria é inválida.',

            'unidade_comercial.string' => 'A unidade comercial é inválida.',
            'unidade_comercial.max' => 'A unidade comercial não pode ultrapassar 10 caracteres.',

            'unidade_tributavel.string' => 'A unidade tributável é inválida.',
            'unidade_tributavel.max' => 'A unidade tributável não pode ultrapassar 10 caracteres.',

            'fator_conversao.numeric' => 'O fator de conversão deve ser um valor numérico.',
            'fator_conversao.min' => 'O fator de conversão não pode ser negativo.',

            'peso_liquido.numeric' => 'O peso líquido deve ser um valor numérico.',
            'peso_liquido.min' => 'O peso líquido não pode ser negativo.',

            'peso_bruto.numeric' => 'O peso bruto deve ser um valor numérico.',
            'peso_bruto.min' => 'O peso bruto não pode ser negativo.',

            'veiculos.required' => 'Selecione pelo menos um veículo.',
            'veiculos.array' => 'Os veículos selecionados são inválidos.',
            'veiculos.*.exists' => 'Um dos veículos selecionados não existe.',
        ];
    }
}
