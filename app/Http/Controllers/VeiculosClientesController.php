<?php

namespace App\Http\Controllers;

use App\Actions\VeiculosClientes\AtualizarVeiculoCliente;
use App\Actions\VeiculosClientes\CriarVeiculoCliente;
use App\Http\Requests\VeiculosClientes\StoreVeiculosClienteRequest;
use App\Http\Requests\VeiculosClientes\UpdateVeiculosClienteRequest;
use App\Models\Montadora;
use App\Models\VeiculosCliente;
use Illuminate\Http\Request;

class VeiculosClientesController extends Controller
{
    public function veiculosPorCliente($id)
    {
        $veiculos = VeiculosCliente::query()
            ->whereHas(
                'clientes',
                function ($query) use ($id) {
                    $query->where(
                        'clientes.id',
                        $id
                    );
                }
            )
            ->with([
                'veiculo.montadora',
            ])
            ->get();

        return response()->json($veiculos);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = VeiculosCliente::query()
            ->with([
                'veiculo.montadora',
                'clientes.pessoa',
            ]);

        if ($user->permitions == 2) {
            $clienteId = $user
                ->pessoa
                ?->cliente
                ?->id;

            if (!$clienteId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas(
                    'clientes',
                    function ($clientesQuery) use ($clienteId) {
                        $clientesQuery->where(
                            'clientes.id',
                            $clienteId
                        );
                    }
                );
            }
        }

        $query
            ->when(
                $request->filled('cliente'),
                function ($query) use ($request) {
                    $cliente = trim(
                        (string) $request->cliente
                    );

                    $query->whereHas(
                        'clientes.pessoa',
                        function ($pessoaQuery) use ($cliente) {
                            $pessoaQuery->where(
                                'nome',
                                'like',
                                "%{$cliente}%"
                            );
                        }
                    );
                }
            )
            ->when(
                $request->filled('placa'),
                function ($query) use ($request) {
                    $placa = strtoupper(
                        preg_replace(
                            '/[^A-Z0-9]/',
                            '',
                            $request->placa
                        )
                    );

                    $query->where(
                        'placa',
                        'like',
                        "%{$placa}%"
                    );
                }
            )
            ->when(
                $request->filled('veiculo'),
                function ($query) use ($request) {
                    $veiculo = trim(
                        (string) $request->veiculo
                    );

                    $query->whereHas(
                        'veiculo',
                        function ($veiculoQuery) use ($veiculo) {
                            $veiculoQuery->where(
                                'nome',
                                'like',
                                "%{$veiculo}%"
                            );
                        }
                    );
                }
            )
            ->when(
                $request->filled('montadora'),
                function ($query) use ($request) {
                    $query->whereHas(
                        'veiculo',
                        function ($veiculoQuery) use ($request) {
                            $veiculoQuery->where(
                                'montadora_id',
                                $request->montadora
                            );
                        }
                    );
                }
            )
            ->when(
                $request->filled('ano'),
                function ($query) use ($request) {
                    $query->where(
                        'ano',
                        $request->ano
                    );
                }
            )
            ->when(
                $request->filled('cor'),
                function ($query) use ($request) {
                    $cor = trim(
                        (string) $request->cor
                    );

                    $query->where(
                        'cor',
                        'like',
                        "%{$cor}%"
                    );
                }
            );

        $veiculosclientes = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $montadoras = Montadora::query()
            ->select([
                'id',
                'nome',
            ])
            ->orderBy('nome')
            ->get();

        return view(
            'veiculosclientes.listarveiculosclientes',
            compact(
                'veiculosclientes',
                'montadoras'
            )
        );
    }

    public function create()
    {
        return view(
            'veiculosclientes.cadastroveiculosclientes'
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
            'clientes.pessoa',
            'veiculo.montadora',
        ])->findOrFail($id);

        if ($userLogado->permitions != 1) {
            $clienteIdLogado = $userLogado
                ->pessoa
                ?->cliente
                ?->id;

            if (!$clienteIdLogado) {
                abort(
                    403,
                    'Ação não autorizada.'
                );
            }

            $possuiAcesso = $veiculoscliente
                ->clientes()
                ->where(
                    'clientes.id',
                    $clienteIdLogado
                )
                ->exists();

            if (!$possuiAcesso) {
                abort(
                    403,
                    'Ação não autorizada.'
                );
            }
        }

        return view(
            'veiculosclientes.editarveiculosclientes',
            compact(
                'veiculoscliente'
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
            $clienteId = $user
                ->pessoa
                ?->cliente
                ?->id;

            if (!$clienteId) {
                abort(
                    403,
                    'Ação não autorizada.'
                );
            }

            $query->whereHas(
                'clientes',
                function ($clientesQuery) use ($clienteId) {
                    $clientesQuery->where(
                        'clientes.id',
                        $clienteId
                    );
                }
            );
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
