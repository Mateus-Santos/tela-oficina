<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotaItemBuscaController extends Controller
{
    /**
     * Busca produtos ou ordens de serviço para adicionar à nota.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo' => [
                'required',
                'string',
                Rule::in(['produto', 'os']),
            ],
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'veiculo_cliente_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $tipo = $dados['tipo'];
        $busca = trim($dados['q'] ?? '');
        $veiculoClienteId = $dados['veiculo_cliente_id'] ?? null;

        if ($tipo === 'produto') {
            return $this->buscarProdutos($busca);
        }

        return $this->buscarOrdensServico(
            $busca,
            $veiculoClienteId
        );
    }

    /**
     * Busca produtos por nome, descrição,
     * código de barras ou código do fabricante.
     */
    private function buscarProdutos(string $busca): JsonResponse
    {
        if ($busca === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $produtos = Produto::query()
            ->select([
                'id',
                'nome',
                'descricao',
                'preco_uni',
                'codigo_fabricante',
                'codigo_barras',
            ])
            ->where(function ($query) use ($busca) {
                $query
                    ->where('nome', 'like', "%{$busca}%")
                    ->orWhere(
                        'descricao',
                        'like',
                        "%{$busca}%"
                    )
                    ->orWhere(
                        'codigo_fabricante',
                        'like',
                        "%{$busca}%"
                    )
                    ->orWhere(
                        'codigo_barras',
                        'like',
                        "%{$busca}%"
                    );
            })
            ->orderBy('nome')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $produtos->map(function ($produto) {
                return [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'descricao' => $produto->descricao,
                    'preco' => (float) $produto->preco_uni,
                    'codigo_fabricante' =>
                        $produto->codigo_fabricante,
                    'codigo_barras' =>
                        $produto->codigo_barras,
                ];
            })->values(),
        ]);
    }

    /**
     * Busca ordens de serviço por ID ou descrição.
     *
     * Quando um veículo é informado, somente as O.S.
     * daquele veículo serão retornadas.
     */
    private function buscarOrdensServico(
        string $busca,
        ?int $veiculoClienteId
    ): JsonResponse {
        $ordensServico = OrdemServico::query()
            ->select([
                'id',
                'veiculo_cliente_id',
                'status',
                'descricao',
                'valor',
            ])
            ->when(
                $veiculoClienteId,
                function ($query) use ($veiculoClienteId) {
                    $query->where(
                        'veiculo_cliente_id',
                        $veiculoClienteId
                    );
                }
            )
            ->when(
                $busca !== '',
                function ($query) use ($busca) {
                    $query->where(function ($query) use ($busca) {
                        $query->where(
                            'descricao',
                            'like',
                            "%{$busca}%"
                        );

                        if (ctype_digit($busca)) {
                            $query->orWhere(
                                'id',
                                (int) $busca
                            );
                        }
                    });
                }
            )
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $ordensServico->map(function ($ordemServico) {
                return [
                    'id' => $ordemServico->id,
                    'descricao' => $ordemServico->descricao,
                    'valor' => (float) $ordemServico->valor,
                    'status' => $ordemServico->status,
                    'veiculo_cliente_id' =>
                        $ordemServico->veiculo_cliente_id,
                ];
            })->values(),
        ]);
    }
}
