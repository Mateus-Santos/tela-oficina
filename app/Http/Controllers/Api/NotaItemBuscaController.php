<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotaItemBuscaController extends Controller
{
    /**
     * Busca ordens de serviço para adicionar à nota.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo' => [
                'required',
                'string',
                Rule::in(['os']),
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

        $busca = trim($dados['q'] ?? '');
        $veiculoClienteId = $dados['veiculo_cliente_id'] ?? null;

        return $this->buscarOrdensServico(
            $busca,
            $veiculoClienteId
        );
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
