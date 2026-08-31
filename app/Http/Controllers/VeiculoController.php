<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVeiculoRequest;
use App\Http\Requests\UpdateVeiculoRequest;
use App\Models\Montadora;
use App\Models\Veiculo;
use Illuminate\Http\Request;

class VeiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = Veiculo::with('montadora');

        if ($request->filled('veiculo')) {
            $query->where(
                'nome',
                'like',
                '%' . $request->input('veiculo') . '%'
            );
        }

        if ($request->filled('montadora')) {
            $query->where(
                'montadora_id',
                $request->input('montadora')
            );
        }

        $veiculos = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $montadoras = Montadora::orderBy('nome')->get();

        return view(
            'veiculo.listarveiculo',
            compact('veiculos', 'montadoras')
        );
    }

    public function porMontadora(int $id)
    {
        $veiculos = Veiculo::where('montadora_id', $id)
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return response()->json($veiculos);
    }

    public function create()
    {
        $montadoras = Montadora::orderBy('nome')->get();

        return view(
            'veiculo.cadastroveiculos',
            compact('montadoras')
        );
    }

    public function store(StoreVeiculoRequest $request)
    {
        Veiculo::create($request->validated());

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo cadastrado com sucesso.');
    }

    public function edit(Veiculo $veiculo)
    {
        $montadoras = Montadora::orderBy('nome')->get();

        return view(
            'veiculo.editarveiculos',
            compact('veiculo', 'montadoras')
        );
    }

    public function update(
        UpdateVeiculoRequest $request,
        Veiculo $veiculo
    ) {
        $veiculo->update($request->validated());

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo atualizado com sucesso.');
    }

    public function destroy(Veiculo $veiculo)
    {
        $veiculo->delete();

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo excluído com sucesso.');
    }
}
