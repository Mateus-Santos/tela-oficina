<?php

namespace App\Http\Controllers\Api;

use App\Actions\Produto\CriarProduto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Produto\StoreProdutoRapidoRequest;
use App\Models\Marca;
use App\Models\VeiculosCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NotaProdutoRapidoController extends Controller
{
    public function marcas(): JsonResponse
    {
        $marcas =
            Marca::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->where(
                    'ativo',
                    true
                )
                ->orderBy('nome')
                ->get();

        return response()->json([
            'data' => $marcas,
        ]);
    }

    public function store(
        StoreProdutoRapidoRequest $request,
        CriarProduto $criarProduto
    ): JsonResponse {
        $dados =
            $request->validated();

        $veiculoCliente =
            VeiculosCliente::query()
                ->with(
                    'veiculo.montadora'
                )
                ->whereKey(
                    $dados[
                        'veiculo_cliente_id'
                    ]
                )
                ->whereHas(
                    'clientes',
                    function ($query) use ($dados) {
                        $query->where(
                            'clientes.id',
                            $dados[
                                'cliente_id'
                            ]
                        );
                    }
                )
                ->first();

        if (!$veiculoCliente) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' =>
                    'O veículo selecionado não está vinculado ao cliente da Nota.',
            ]);
        }

        if (!$veiculoCliente->veiculo_id) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' =>
                    'O veículo selecionado não possui um modelo de veículo válido.',
            ]);
        }

        $produto =
            $criarProduto->execute([
                'nome' =>
                    $dados['nome'],

                'marca_id' =>
                    $dados['marca_id'],

                'descricao' =>
                    $dados['descricao'],

                'preco_uni' =>
                    $dados['preco_uni'],

                'quantidade' =>
                    $dados['quantidade'],

                'estoque_minimo' =>
                    $dados[
                        'estoque_minimo'
                    ] ?? 0,

                'status' =>
                    true,

                'codigo_fabricante' =>
                    $dados[
                        'codigo_fabricante'
                    ],

                'codigo_barras' =>
                    $dados[
                        'codigo_barras'
                    ] ?? null,

                'veiculos' => [
                    $veiculoCliente
                        ->veiculo_id,
                ],
            ]);

        return response()->json([
            'message' =>
                "Produto #{$produto->id} criado com sucesso.",

            'data' => [
                'id' =>
                    $produto->id,

                'nome' =>
                    $produto->nome,

                'descricao' =>
                    $produto->descricao,

                'codigo' =>
                    $produto
                        ->codigo_fabricante
                    ?: $produto
                        ->codigo_barras,

                'codigo_fabricante' =>
                    $produto
                        ->codigo_fabricante,

                'codigo_barras' =>
                    $produto
                        ->codigo_barras,

                'preco_uni' =>
                    (float) $produto
                        ->preco_uni,

                'quantidade' =>
                    (float) $produto
                        ->quantidade,

                'estoque_minimo' =>
                    (float) $produto
                        ->estoque_minimo,

                'marca' =>
                    $produto
                        ->marcaRelacionada
                        ?->nome,

                'status' =>
                    (bool) $produto
                        ->status,
            ],
        ], 201);
    }
}
