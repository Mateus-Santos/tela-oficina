<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\SetorServico;
use App\Models\VeiculosCliente;
use Illuminate\Http\Request;

class OrdemServicoController extends Controller
{
    public function index(Request $request)
    {
        $query = OrdemServico::query()
            ->with([
                'cliente.pessoa',
                'veiculosCliente.clientes.pessoa',
                'veiculosCliente.veiculo.montadora',
                'setorServico',
            ]);

        if ($request->filled('id')) {
            $query->where(
                'id',
                $request->input('id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('cliente')) {
            $cliente = trim(
                (string) $request->input('cliente')
            );

            $query->whereHas(
                'cliente.pessoa',
                function ($pessoaQuery) use ($cliente) {
                    $pessoaQuery->where(
                        'nome',
                        'like',
                        "%{$cliente}%"
                    );
                }
            );
        }

        if ($request->filled('placa')) {
            $placa = strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $request->input('placa')
                )
            );

            $query->whereHas(
                'veiculosCliente',
                function ($veiculoClienteQuery) use ($placa) {
                    $veiculoClienteQuery->where(
                        'placa',
                        'like',
                        "%{$placa}%"
                    );
                }
            );
        }

        if ($request->filled('setor')) {
            $query->where(
                'setor_servico_id',
                $request->input('setor')
            );
        }

        if ($request->filled('descricao')) {
            $descricao = trim(
                (string) $request->input('descricao')
            );

            $query->where(
                'descricao',
                'like',
                "%{$descricao}%"
            );
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate(
                'data_abertura',
                '>=',
                $request->input('data_inicio')
            );
        }

        if ($request->filled('data_fim')) {
            $query->whereDate(
                'data_abertura',
                '<=',
                $request->input('data_fim')
            );
        }

        $ordemservicos = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $setorservicos = SetorServico::query()
            ->orderBy('setor')
            ->get();

        return view(
            'ordemservico.listar_os',
            compact(
                'ordemservicos',
                'setorservicos'
            )
        );
    }

    public function create()
    {
        $setorservicos = SetorServico::query()
            ->orderBy('setor')
            ->get();

        return view(
            'ordemservico.cadastro_os',
            compact('setorservicos')
        );
    }

    public function store(Request $request)
    {
        $dados = $request->validate(
            [
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
                    'nullable',
                    'string',
                ],

                'valor' => [
                    'required',
                ],
            ],
            [
                'cliente_id.required' =>
                    'Selecione o cliente responsável pela OS.',

                'cliente_id.integer' =>
                    'O cliente selecionado é inválido.',

                'cliente_id.exists' =>
                    'O cliente selecionado não existe.',

                'veiculo_cliente_id.required' =>
                    'Selecione o veículo.',

                'veiculo_cliente_id.integer' =>
                    'O veículo selecionado é inválido.',

                'veiculo_cliente_id.exists' =>
                    'O veículo selecionado não existe.',

                'setor_servico_id.required' =>
                    'Selecione o setor de serviço.',

                'setor_servico_id.integer' =>
                    'O setor selecionado é inválido.',

                'setor_servico_id.exists' =>
                    'O setor selecionado não existe.',

                'valor.required' =>
                    'Informe o valor da ordem de serviço.',
            ]
        );

        $veiculoCliente = VeiculosCliente::query()
            ->where(
                'id',
                $dados['veiculo_cliente_id']
            )
            ->whereHas(
                'clientes',
                function ($clientesQuery) use ($dados) {
                    $clientesQuery->where(
                        'clientes.id',
                        $dados['cliente_id']
                    );
                }
            )
            ->first();

        if (!$veiculoCliente) {
            return back()
                ->withInput()
                ->withErrors([
                    'veiculo_cliente_id' =>
                        'O veículo selecionado não está vinculado ao cliente informado.',
                ]);
        }

        $valor = str_replace(
            '.',
            '',
            (string) $dados['valor']
        );

        $valor = str_replace(
            ',',
            '.',
            $valor
        );

        $ordemservico = new OrdemServico();

        $ordemservico->data_abertura = now();

        $ordemservico->cliente_id =
            $dados['cliente_id'];

        $ordemservico->veiculo_cliente_id =
            $dados['veiculo_cliente_id'];

        $ordemservico->setor_servico_id =
            $dados['setor_servico_id'];

        $ordemservico->descricao =
            $dados['descricao'] ?? null;

        $ordemservico->valor = $valor;

        $ordemservico->save();

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                'Ordem de serviço cadastrada com sucesso!'
            );
    }

    public function edit(string $id)
    {
        //
    }

    public function update(
        Request $request,
        string $id
    ) {
        //
    }

    public function destroy(string $id)
    {
        $ordemservico = OrdemServico::findOrFail(
            $id
        );

        $ordemservico->delete();

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                'Ordem de serviço excluída com sucesso!'
            );
    }
}
