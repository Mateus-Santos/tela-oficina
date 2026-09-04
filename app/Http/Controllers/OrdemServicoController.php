<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServico;
use App\Models\SetorServico;

class OrdemServicoController extends Controller
{

    public function index(Request $request)
    {
        $query = OrdemServico::with([
            'veiculosCliente.cliente.pessoa',
            'veiculosCliente.veiculo.montadora',
            'setorServico',
        ]);

        // Filtro por ID da OS
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filtro por cliente
        if ($request->filled('cliente')) {
            $cliente = $request->input('cliente');

            $query->whereHas(
                'veiculosCliente.cliente.pessoa',
                function ($q) use ($cliente) {
                    $q->where('nome', 'like', '%' . $cliente . '%');
                }
            );
        }

        // Filtro por placa
        if ($request->filled('placa')) {
            $placa = $request->input('placa');

            $query->whereHas(
                'veiculosCliente',
                function ($q) use ($placa) {
                    $q->where('placa', 'like', '%' . $placa . '%');
                }
            );
        }

        // Filtro por setor
        if ($request->filled('setor')) {
            $query->where('setor_servico_id', $request->input('setor'));
        }

        // Filtro por descrição
        if ($request->filled('descricao')) {
            $query->where(
                'descricao',
                'like',
                '%' . $request->input('descricao') . '%'
            );
        }

        // Filtro por data inicial
        if ($request->filled('data_inicio')) {
            $query->whereDate(
                'data_abertura',
                '>=',
                $request->input('data_inicio')
            );
        }

        // Filtro por data final
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

        $setorservicos = SetorServico::orderBy('setor')->get();

        return view(
            'ordemservico.listar_os',
            compact('ordemservicos', 'setorservicos')
        );
    }

    public function create()
    {
        $setorservicos = SetorServico::all();
        return view('ordemservico.cadastro_os', compact('setorservicos'));
    }

    public function store(Request $request)
    {
        $valor = str_replace('.', '', $request->valor);
        $valor = str_replace(',', '.', $valor);
        $ordemservico = new ordemservico();
        $ordemservico->data_abertura = now();
        $ordemservico->setor_servico_id = $request->input("setor_servico_id");
        $ordemservico->veiculo_cliente_id = $request->input("veiculo_cliente_id");
        $ordemservico->descricao = $request->input("descricao");
        $ordemservico->valor = $valor;
        $ordemservico->save();
        return redirect()->route('ordemservicos.index');
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        $ordemservico = OrdemServico::where('id', $id)->delete();
        return redirect()->route('ordemservicos.index');
    }
}
