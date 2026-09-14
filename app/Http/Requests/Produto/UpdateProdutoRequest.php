<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('preco_uni')) {
            $preco = $this->input('preco_uni');

            if (str_contains($preco, ',')) {
                $preco = str_replace('.', '', $preco);
                $preco = str_replace(',', '.', $preco);
            }

            $this->merge([
                'preco_uni' => $preco,
            ]);
        }
    }

    public function rules(): array
    {
        $produtoId = $this->route('produto');

        return [
            'nome' => [
                'required',
                'string',
                'max:150',
            ],

            'marca' => [
                'required',
                'string',
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
                'required',
                'integer',
                'min:0',
            ],

            'codigo_fabricante' => [
                'required',
                'string',
                Rule::unique('produtos', 'codigo_fabricante')
                    ->ignore($produtoId),
            ],

            'codigo_barras' => [
                'nullable',
                'string',
                Rule::unique('produtos', 'codigo_barras')
                    ->ignore($produtoId),
            ],

            'img' => [
                'nullable',
                'image',
                'max:2048',
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

            'marca.required' => 'A marca do produto é obrigatória.',
            'marca.string' => 'A marca do produto é inválida.',

            'descricao.required' => 'A descrição do produto é obrigatória.',
            'descricao.string' => 'A descrição do produto é inválida.',

            'preco_uni.required' => 'O preço unitário é obrigatório.',
            'preco_uni.numeric' => 'O preço unitário deve ser um valor numérico.',
            'preco_uni.min' => 'O preço unitário não pode ser negativo.',

            'quantidade.required' => 'A quantidade do produto é obrigatória.',
            'quantidade.integer' => 'A quantidade deve ser um número inteiro.',
            'quantidade.min' => 'A quantidade não pode ser negativa.',

            'codigo_fabricante.required' => 'O código do fabricante é obrigatório.',
            'codigo_fabricante.string' => 'O código do fabricante é inválido.',
            'codigo_fabricante.unique' => 'Já existe outro produto cadastrado com este código de fabricante.',

            'codigo_barras.string' => 'O código de barras é inválido.',
            'codigo_barras.unique' => 'Já existe outro produto cadastrado com este código de barras.',

            'img.image' => 'O arquivo informado deve ser uma imagem.',
            'img.max' => 'A imagem não pode ter mais de 2 MB.',

            'veiculos.required' => 'Selecione pelo menos um veículo.',
            'veiculos.array' => 'Os veículos selecionados são inválidos.',
            'veiculos.*.exists' => 'Um dos veículos selecionados não existe.',
        ];
    }
}
