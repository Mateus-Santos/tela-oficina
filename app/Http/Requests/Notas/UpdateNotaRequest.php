<?php

namespace App\Http\Requests\Notas;

use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $itens = $this->input('itens');

        if (!is_array($itens)) {
            return;
        }

        foreach ($itens as &$item) {
            if (!is_array($item)) {
                continue;
            }

            /*
             * =====================================================
             * ID DO ITEM
             * =====================================================
             */
            if (
                array_key_exists('id', $item)
                && $item['id'] !== ''
                && $item['id'] !== null
            ) {
                $item['id'] =
                    (int) $item['id'];
            }

            /*
             * =====================================================
             * TIPO DO ITEM
             * =====================================================
             */
            if (array_key_exists('itemable_type', $item)) {
                $tipo = trim(
                    (string) $item['itemable_type']
                );

                while (str_contains($tipo, '\\\\')) {
                    $tipo = str_replace(
                        '\\\\',
                        '\\',
                        $tipo
                    );
                }

                if (
                    $tipo === 'produto'
                    || $tipo === 'Produto'
                    || $tipo === Produto::class
                ) {
                    $item['itemable_type'] =
                        Produto::class;
                } elseif (
                    $tipo === 'os'
                    || $tipo === 'OS'
                    || $tipo === 'OrdemServico'
                    || $tipo === OrdemServico::class
                ) {
                    $item['itemable_type'] =
                        OrdemServico::class;
                }
            }

            /*
             * =====================================================
             * VALOR UNITÁRIO
             * =====================================================
             */
            if (
                array_key_exists(
                    'valor_unitario',
                    $item
                )
            ) {
                $item['valor_unitario'] =
                    $this->normalizarNumero(
                        $item['valor_unitario']
                    );
            }

            /*
             * =====================================================
             * DESCONTO
             * =====================================================
             */
            if (
                array_key_exists(
                    'desconto',
                    $item
                )
            ) {
                $desconto =
                    $this->normalizarNumero(
                        $item['desconto']
                    );

                $item['desconto'] =
                    $desconto === ''
                        ? null
                        : $desconto;
            }

            /*
             * =====================================================
             * QUANTIDADE
             * =====================================================
             */
            if (
                array_key_exists(
                    'quantidade',
                    $item
                )
            ) {
                $quantidade = str_replace(
                    ',',
                    '.',
                    trim(
                        (string) $item['quantidade']
                    )
                );

                if (is_numeric($quantidade)) {
                    $quantidadeFloat =
                        (float) $quantidade;

                    if (
                        $quantidadeFloat >= 1
                        && floor($quantidadeFloat)
                            === $quantidadeFloat
                    ) {
                        $item['quantidade'] =
                            (int) $quantidadeFloat;
                    }
                }
            }

            /*
             * =====================================================
             * GARANTIA
             * =====================================================
             */
            if (
                array_key_exists(
                    'garantia_dias',
                    $item
                )
            ) {
                if (
                    $item['garantia_dias'] === ''
                    || $item['garantia_dias'] === null
                ) {
                    $item['garantia_dias'] =
                        null;
                } elseif (
                    is_numeric(
                        $item['garantia_dias']
                    )
                ) {
                    $item['garantia_dias'] =
                        (int) $item['garantia_dias'];
                }
            }
        }

        unset($item);

        $this->merge([
            'itens' => $itens,
        ]);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            'veiculo_cliente_id' => [
                'nullable',
                'integer',
                'exists:veiculos_clientes,id',
            ],

            'km' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'km_proxima_troca_oleo' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'itens' => [
                'required',
                'array',
                'min:1',
            ],

            'itens.*.id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:notas_itens,id',
            ],

            'itens.*.itemable_type' => [
                'required',
                'string',
                Rule::in([
                    Produto::class,
                    OrdemServico::class,
                ]),
            ],

            'itens.*.itemable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'itens.*.descricao' => [
                'required',
                'string',
                'max:250',
            ],

            'itens.*.quantidade' => [
                'required',
                'integer',
                'min:1',
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

            'itens.*.garantia_dias' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator) {
                $this->validarProdutosDuplicados(
                    $validator
                );

                $this->validarDescontos(
                    $validator
                );
            }
        );
    }

    public function messages(): array
    {
        return [
            'cliente_id.integer' =>
                'O cliente informado é inválido.',

            'cliente_id.exists' =>
                'O cliente informado não existe.',

            'veiculo_cliente_id.integer' =>
                'O veículo informado é inválido.',

            'veiculo_cliente_id.exists' =>
                'O veículo informado não existe.',

            'km.integer' =>
                'A quilometragem atual deve ser um número inteiro.',

            'km.min' =>
                'A quilometragem atual não pode ser negativa.',

            'km_proxima_troca_oleo.integer' =>
                'A quilometragem da próxima troca deve ser um número inteiro.',

            'km_proxima_troca_oleo.min' =>
                'A quilometragem da próxima troca não pode ser negativa.',

            'itens.required' =>
                'Adicione ao menos um item à Nota.',

            'itens.array' =>
                'Os itens informados são inválidos.',

            'itens.min' =>
                'Adicione ao menos um item à Nota.',

            'itens.*.id.integer' =>
                'Existe um item com identificador inválido.',

            'itens.*.id.min' =>
                'Existe um item com identificador inválido.',

            'itens.*.id.exists' =>
                'Existe um item que não foi encontrado.',

            'itens.*.itemable_type.required' =>
                'O tipo do item é obrigatório.',

            'itens.*.itemable_type.in' =>
                'Existe um item com tipo inválido.',

            'itens.*.itemable_id.required' =>
                'Existe um item sem produto ou Ordem de Serviço selecionada.',

            'itens.*.itemable_id.integer' =>
                'Existe um item com identificador inválido.',

            'itens.*.itemable_id.min' =>
                'Existe um item com identificador inválido.',

            'itens.*.descricao.required' =>
                'A descrição de todos os itens é obrigatória.',

            'itens.*.descricao.string' =>
                'Existe um item com descrição inválida.',

            'itens.*.descricao.max' =>
                'A descrição de um item não pode ultrapassar 250 caracteres.',

            'itens.*.quantidade.required' =>
                'Informe a quantidade de todos os itens.',

            'itens.*.quantidade.integer' =>
                'A quantidade dos itens deve ser um número inteiro.',

            'itens.*.quantidade.min' =>
                'A quantidade dos itens deve ser de pelo menos 1.',

            'itens.*.valor_unitario.required' =>
                'Informe o valor unitário de todos os itens.',

            'itens.*.valor_unitario.numeric' =>
                'Existe um item com valor unitário inválido.',

            'itens.*.valor_unitario.min' =>
                'O valor unitário não pode ser negativo.',

            'itens.*.desconto.numeric' =>
                'Existe um item com desconto inválido.',

            'itens.*.desconto.min' =>
                'O desconto não pode ser negativo.',

            'itens.*.garantia_dias.integer' =>
                'A garantia deve ser informada em dias inteiros.',

            'itens.*.garantia_dias.min' =>
                'A garantia não pode ser negativa.',
        ];
    }

    private function validarProdutosDuplicados(
        Validator $validator
    ): void {
        $itens =
            $this->input(
                'itens',
                []
            );

        if (!is_array($itens)) {
            return;
        }

        $produtosEncontrados = [];

        foreach (
            $itens as $index => $item
        ) {
            if (!is_array($item)) {
                continue;
            }

            $tipo =
                $item['itemable_type']
                ?? null;

            $itemId =
                $item['itemable_id']
                ?? null;

            if (
                $tipo !== Produto::class
                || !$itemId
            ) {
                continue;
            }

            $produtoId =
                (int) $itemId;

            if (
                isset(
                    $produtosEncontrados[
                        $produtoId
                    ]
                )
            ) {
                $primeiroIndex =
                    $produtosEncontrados[
                        $produtoId
                    ];

                $validator
                    ->errors()
                    ->add(
                        "itens.{$index}.itemable_id",
                        'O mesmo produto não pode ser adicionado mais de uma vez à Nota.'
                    );

                $validator
                    ->errors()
                    ->add(
                        "itens.{$primeiroIndex}.itemable_id",
                        'Este produto está duplicado na Nota.'
                    );

                continue;
            }

            $produtosEncontrados[
                $produtoId
            ] = $index;
        }
    }

    private function validarDescontos(
        Validator $validator
    ): void {
        $itens =
            $this->input(
                'itens',
                []
            );

        if (!is_array($itens)) {
            return;
        }

        foreach (
            $itens as $index => $item
        ) {
            if (!is_array($item)) {
                continue;
            }

            $quantidade =
                $item['quantidade']
                ?? null;

            $valorUnitario =
                $item['valor_unitario']
                ?? null;

            $desconto =
                $item['desconto']
                ?? 0;

            if (
                !is_numeric($quantidade)
                || !is_numeric($valorUnitario)
                || (
                    $desconto !== null
                    && !is_numeric($desconto)
                )
            ) {
                continue;
            }

            $subtotal =
                (float) $quantidade
                * (float) $valorUnitario;

            $desconto =
                (float) (
                    $desconto
                    ?? 0
                );

            if (
                $desconto
                > $subtotal
            ) {
                $validator
                    ->errors()
                    ->add(
                        "itens.{$index}.desconto",
                        'O desconto do item não pode ser maior que o valor total do item.'
                    );
            }
        }
    }

    private function normalizarNumero(
        mixed $valor
    ): string {
        if ($valor === null) {
            return '';
        }

        $valor = trim(
            (string) $valor
        );

        if ($valor === '') {
            return '';
        }

        $valor = str_replace(
            ' ',
            '',
            $valor
        );

        if (
            str_contains($valor, '.')
            && str_contains($valor, ',')
        ) {
            $valor = str_replace(
                '.',
                '',
                $valor
            );

            return str_replace(
                ',',
                '.',
                $valor
            );
        }

        if (
            str_contains(
                $valor,
                ','
            )
        ) {
            return str_replace(
                ',',
                '.',
                $valor
            );
        }

        return $valor;
    }
}
