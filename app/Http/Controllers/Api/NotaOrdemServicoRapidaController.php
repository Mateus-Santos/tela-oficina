<?php

namespace App\Http\Controllers\Api;

use App\Actions\OrdemServico\CriarOrdemServico;
use App\Http\Controllers\Controller;
use App\Models\SetorServico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotaOrdemServicoRapidaController extends Controller
{
    public function setores(): JsonResponse
    {
        $setores =
            SetorServico::query()
                ->select([
                    'id',
                    'setor',
                ])
                ->orderBy('setor')
                ->get();

        return response()->json([
            'data' => $setores,
        ]);
    }

    public function store(
        Request $request,
        CriarOrdemServico $criarOrdemServico
    ): JsonResponse {
        $dados =
            $request->validate([
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

                'setor_servico_id' => [
                    'required',
                    'integer',
                    'exists:setor_servicos,id',
                ],

                'descricao' => [
                    'required',
                    'string',
                ],

                'valor' => [
                    'required',
                    'numeric',
                    'gte:0',
                ],
            ], [
                'cliente_id.required' => 'Selecione um cliente na Nota antes de criar a O.S.',

                'veiculo_cliente_id.required' => 'Selecione um veículo na Nota antes de criar a O.S.',

                'setor_servico_id.required' => 'Selecione o setor de serviço.',

                'descricao.required' => 'Informe a descrição da O.S.',

                'valor.required' => 'Informe o valor da O.S.',
            ]);

        $ordemServico =
            $criarOrdemServico->execute(
                $dados,
                $request->user()->id
            );

        return response()->json([
            'message' => "O.S. #{$ordemServico->id} criada com sucesso.",

            'data' => [
                'id' => $ordemServico->id,

                'cliente_id' => $ordemServico->cliente_id,

                'veiculo_cliente_id' => $ordemServico->veiculo_cliente_id,

                'descricao' => $ordemServico->descricao,

                'valor' => (float) $ordemServico->valor,

                'status' => $ordemServico->status,

                'disponivel' => true,

                'nota' => null,
            ],
        ], 201);
    }
}
