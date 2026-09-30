<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotaItemBuscaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo' => [
                'required',
                'string',
                Rule::in([
                    'produto',
                    'os',
                ]),
            ],

            'q' => [
                'nullable',
                'string',
                'max:100',
            ],

            'cliente_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:clientes,id',
            ],

            'veiculo_cliente_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:veiculos_clientes,id',
            ],
        ]);

        $busca = trim(
            $dados['q'] ?? ''
        );

        $clienteId =
            isset($dados['cliente_id'])
                ? (int) $dados['cliente_id']
                : null;

        $veiculoClienteId =
            isset($dados['veiculo_cliente_id'])
                ? (int) $dados['veiculo_cliente_id']
                : null;

        if ($dados['tipo'] === 'produto') {
            return $this->buscarProdutos(
                $busca
            );
        }

        return $this->buscarOrdensServico(
            $busca,
            $clienteId,
            $veiculoClienteId
        );
    }

    /**
     * Busca produtos disponíveis para inclusão na Nota.
     */
    private function buscarProdutos(
        string $busca
    ): JsonResponse {
        if ($busca === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $produtos = Produto::query()
            ->with([
                'marcaRelacionada:id,nome',
            ])
            ->select([
                'id',
                'nome',
                'descricao',
                'quantidade',
                'estoque_minimo',
                'preco_uni',
                'codigo_fabricante',
                'codigo_barras',
                'marca_id',
            ])
            ->where(
                'status',
                true
            )
            ->where(
                function ($query) use ($busca) {
                    $query
                        ->where(
                            'nome',
                            'like',
                            "%{$busca}%"
                        )
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
                }
            )
            ->orderBy('nome')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $produtos
                ->map(
                    function ($produto) {
                        return [
                            'id' =>
                                $produto->id,

                            'nome' =>
                                $produto->nome,

                            'descricao' =>
                                $produto->descricao,

                            'preco' =>
                                (float) $produto->preco_uni,

                            'codigo_fabricante' =>
                                $produto->codigo_fabricante,

                            'codigo_barras' =>
                                $produto->codigo_barras,

                            'marca' =>
                                $produto
                                    ->marcaRelacionada
                                    ?->nome,

                            'estoque_atual' =>
                                (float) $produto->quantidade,

                            'estoque_minimo' =>
                                (float) $produto->estoque_minimo,

                            'disponivel' =>
                                (float) $produto->quantidade > 0,
                        ];
                    }
                )
                ->values(),
        ]);
    }

    /**
     * Busca Ordens de Serviço para inclusão na Nota.
     *
     * Regras:
     *
     * - Com cliente + veículo:
     *   restringe pelos dois.
     *
     * - Somente com cliente:
     *   restringe pelo cliente.
     *
     * - Somente com veículo:
     *   restringe pelo veículo.
     *
     * - Sem cliente e veículo:
     *   busca normalmente.
     */
    private function buscarOrdensServico(
        string $busca,
        ?int $clienteId,
        ?int $veiculoClienteId
    ): JsonResponse {
        $ordensServico =
            OrdemServico::query()
                ->select([
                    'id',
                    'cliente_id',
                    'veiculo_cliente_id',
                    'status',
                    'descricao',
                    'valor',
                    'created_at',
                ])

                /*
                 * Filtra pelo cliente responsável
                 * quando a Nota já possui cliente.
                 */
                ->when(
                    $clienteId,
                    function ($query) use ($clienteId) {
                        $query->where(
                            'cliente_id',
                            $clienteId
                        );
                    }
                )

                /*
                 * Filtra pelo veículo quando a Nota
                 * já possui veículo selecionado.
                 */
                ->when(
                    $veiculoClienteId,
                    function ($query) use ($veiculoClienteId) {
                        $query->where(
                            'veiculo_cliente_id',
                            $veiculoClienteId
                        );
                    }
                )

                /*
                 * Busca por descrição ou número da O.S.
                 */
                ->when(
                    $busca !== '',
                    function ($query) use ($busca) {
                        $query->where(
                            function ($query) use ($busca) {
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
                            }
                        );
                    }
                )

                ->orderByDesc('created_at')
                ->limit(15)
                ->get();

        $ordemServicoIds =
            $ordensServico->pluck('id');

        /*
         * Busca eventuais vínculos das O.S.
         * encontradas com itens de Notas.
         */
        $vinculos =
            NotasItem::query()
                ->select([
                    'id',
                    'nota_id',
                    'itemable_id',
                ])
                ->with([
                    'nota:id,cliente_id,veiculo_cliente_id,tipo,status',
                ])
                ->where(
                    'itemable_type',
                    OrdemServico::class
                )
                ->whereIn(
                    'itemable_id',
                    $ordemServicoIds
                )
                ->get()
                ->keyBy(
                    'itemable_id'
                );

        return response()->json([
            'data' => $ordensServico
                ->map(
                    function ($ordemServico) use ($vinculos) {
                        $vinculo =
                            $vinculos->get(
                                $ordemServico->id
                            );

                        $nota =
                            $vinculo?->nota;

                        return [
                            'id' =>
                                $ordemServico->id,

                            'cliente_id' =>
                                $ordemServico->cliente_id,

                            'veiculo_cliente_id' =>
                                $ordemServico->veiculo_cliente_id,

                            'descricao' =>
                                $ordemServico->descricao,

                            'valor' =>
                                (float) $ordemServico->valor,

                            'status' =>
                                $ordemServico->status,

                            'disponivel' =>
                                !$vinculo,

                            'nota' =>
                                $nota
                                    ? [
                                        'id' =>
                                            $nota->id,

                                        'tipo' =>
                                            $nota->tipo,

                                        'status' =>
                                            $nota->status,
                                    ]
                                    : null,
                        ];
                    }
                )
                ->values(),
        ]);
    }
}
