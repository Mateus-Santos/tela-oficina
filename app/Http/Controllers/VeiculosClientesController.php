<?php

namespace App\Http\Controllers;

use App\Actions\VeiculosClientes\AtualizarVeiculoCliente;
use App\Actions\VeiculosClientes\CriarVeiculoCliente;
use App\Http\Requests\VeiculosClientes\StoreVeiculosClienteRequest;
use App\Http\Requests\VeiculosClientes\UpdateVeiculosClienteRequest;
use App\Models\Cliente;
use App\Models\Montadora;
use App\Models\VeiculosCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VeiculosClientesController extends Controller
{
    public function veiculosPorCliente($id)
    {
        $veiculos = VeiculosCliente::where('cliente_id', $id)
            ->with(['veiculo.montadora'])
            ->get();

        return response()->json($veiculos);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = VeiculosCliente::with([
            'veiculo.montadora',
            'cliente.pessoa'
        ]);

        if ($user->permitions == 2) {
            $clienteId = $user->pessoa?->cliente?->id;

            if (!$clienteId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('cliente_id', $clienteId);
            }
        }

        $query
            ->when($request->filled('cliente'), function ($query) use ($request) {
                $query->whereHas('cliente.pessoa', function ($query) use ($request) {
                    $query->where(
                        'nome',
                        'like',
                        '%' . $request->cliente . '%'
                    );
                });
            })
            ->when($request->filled('placa'), function ($query) use ($request) {
                $placa = strtoupper(
                    preg_replace('/[^A-Z0-9]/', '', $request->placa)
                );

                $query->where('placa', 'like', '%' . $placa . '%');
            })
            ->when($request->filled('veiculo'), function ($query) use ($request) {
                $query->whereHas('veiculo', function ($query) use ($request) {
                    $query->where(
                        'nome',
                        'like',
                        '%' . $request->veiculo . '%'
                    );
                });
            })
            ->when($request->filled('montadora'), function ($query) use ($request) {
                $query->whereHas('veiculo', function ($query) use ($request) {
                    $query->where(
                        'montadora_id',
                        $request->montadora
                    );
                });
            })
            ->when($request->filled('ano'), function ($query) use ($request) {
                $query->where('ano', $request->ano);
            })
            ->when($request->filled('cor'), function ($query) use ($request) {
                $query->where(
                    'cor',
                    'like',
                    '%' . $request->cor . '%'
                );
            });

        $veiculosclientes = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $montadoras = Montadora::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return view(
            'veiculosclientes.listarveiculosclientes',
            compact('veiculosclientes', 'montadoras')
        );
    }

    public function create()
    {
        $montadoras = Montadora::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $userLogado = Auth::user();

        if ($userLogado->permitions == 1) {
            $clientes = Cliente::with('pessoa')
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $clientes = Cliente::whereHas('pessoa', function ($query) use ($userLogado) {
                $query->where('user_id', $userLogado->id);
            })
                ->with('pessoa')
                ->get();
        }

        return view(
            'veiculosclientes.cadastroveiculosclientes',
            compact('clientes', 'montadoras')
        );
    }

    public function store(
        StoreVeiculosClienteRequest $request,
        CriarVeiculoCliente $criarVeiculoCliente
    ) {
        $dados = $request->validated();

        $criarVeiculoCliente->execute(
            $dados,
            auth()->user()
        );

        return redirect()
            ->route('veiculosclientes.index')
            ->with(
                'success',
                'Veículo cadastrado com sucesso!'
            );
    }

    public function edit(string $id)
    {
        $userLogado = auth()->user();

        $veiculoscliente = VeiculosCliente::with([
            'cliente.pessoa',
            'veiculo.montadora'
        ])->findOrFail($id);

        if ($userLogado->permitions != 1) {
            $clienteIdLogado = $userLogado->pessoa?->cliente?->id;

            if (
                !$clienteIdLogado ||
                $veiculoscliente->cliente_id !== $clienteIdLogado
            ) {
                abort(403, 'Ação não autorizada.');
            }
        }

        $montadoras = Montadora::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $clientes = collect();

        if ($userLogado->permitions == 1) {
            $clientes = Cliente::with('pessoa')
                ->orderBy('id', 'desc')
                ->get();
        }

        return view(
            'veiculosclientes.editarveiculosclientes',
            compact(
                'veiculoscliente',
                'montadoras',
                'clientes'
            )
        );
    }

    public function update(
        UpdateVeiculosClienteRequest $request,
        VeiculosCliente $veiculoscliente,
        AtualizarVeiculoCliente $atualizarVeiculoCliente
    ) {
        $dados = $request->validated();

        $atualizarVeiculoCliente->execute(
            $veiculoscliente,
            $dados,
            auth()->user()
        );

        return redirect()
            ->route('veiculosclientes.index')
            ->with(
                'success',
                'Veículo atualizado com sucesso!'
            );
    }

    public function destroy(string $id)
    {
        $user = auth()->user();

        $query = VeiculosCliente::query()
            ->where('id', $id);

        if ($user->permitions != 1) {
            $clienteId = $user->pessoa?->cliente?->id;

            if (!$clienteId) {
                abort(403, 'Ação não autorizada.');
            }

            $query->where('cliente_id', $clienteId);
        }

        $veiculoCliente = $query->firstOrFail();

        $veiculoCliente->delete();

        return redirect()
            ->route('veiculosclientes.index')
            ->with(
                'success',
                'Veículo excluído com sucesso!'
            );
    }
}
