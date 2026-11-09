<?php

namespace App\Http\Controllers;

use App\Actions\VeiculosClientes\AtualizarVeiculoCliente;
use App\Actions\VeiculosClientes\CriarVeiculoCliente;
use App\Http\Requests\VeiculosClientes\StoreVeiculosClienteRequest;
use App\Http\Requests\VeiculosClientes\UpdateVeiculosClienteRequest;
use App\Models\Montadora;
use App\Models\VeiculosCliente;
use App\Services\PortalCliente\ConsultarVeiculosCliente;
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
        $user = $request->user();

        if ($user->isCliente()) {
            $placa = strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/i',
                    '',
                    (string) $request->query('placa', '')
                )
            );

            $placa = substr($placa, 0, 7);

            $query = app(ConsultarVeiculosCliente::class)
                ->consulta($user);

            if ($placa !== '') {
                $query->where('placa', 'like', '%'.$placa.'%');
            }

            $veiculosclientes = $query
                ->orderBy('placa')
                ->orderBy('id')
                ->paginate(12)
                ->withQueryString();

            return view(
                'portal-cliente.veiculos.index',
                compact('veiculosclientes', 'placa')
            );
        }

        $query = VeiculosCliente::query()
            ->with([
                'veiculo.montadora',
                'clientes.pessoa',
            ]);

        if ($user->isCliente()) {
            $clienteId = $user
                ->cliente
                ?->id;

            if (! $clienteId) {
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
        } elseif (! $user->isAdmin()) {
            abort(
                403,
                'Acesso não autorizado.'
            );
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

    public function showCliente(
        Request $request,
        int $veiculo,
        ConsultarVeiculosCliente $consulta
    ) {
        abort_unless(
            $request->user()?->isCliente() === true,
            403
        );

        $veiculoCliente = $consulta
            ->consulta($request->user())
            ->whereKey($veiculo)
            ->firstOrFail();

        return view(
            'portal-cliente.veiculos.show',
            compact('veiculoCliente')
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
            $request->user()
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
        $usuario = auth()->user();

        $veiculoscliente = VeiculosCliente::query()
            ->findOrFail($id);

        if ($usuario->isCliente()) {
            $clienteId = $usuario->cliente?->id;

            abort_unless($clienteId, 403, 'Ação não autorizada.');

            $pertence = $veiculoscliente
                ->clientes()
                ->where('clientes.id', $clienteId)
                ->exists();

            abort_unless($pertence, 403, 'Ação não autorizada.');

            // Compartilhados só podem ser modificados pela oficina.
            abort_if(
                $veiculoscliente->clientes()->count() !== 1,
                403,
                'Alterações em veículo compartilhado devem ser feitas pela oficina.'
            );
        } else {
            abort_unless(
                $usuario->isAdmin(),
                403,
                'Acesso não autorizado.'
            );
        }

        $veiculoscliente->load([
            'clientes.pessoa',
            'veiculo.montadora',
        ]);

        return view(
            'veiculosclientes.editarveiculosclientes',
            compact('veiculoscliente')
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
            $request->user()
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

        if ($user->isCliente()) {
            $clienteId = $user
                ->cliente
                ?->id;

            if (! $clienteId) {
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
        } elseif (! $user->isAdmin()) {
            abort(
                403,
                'Acesso não autorizado.'
            );
        }

        if ($user->isCliente()) {
            // Mantém 404 para veículo de terceiro.
            $query->firstOrFail();

            abort(
                403,
                'Solicite a exclusão do veículo à oficina.'
            );
        }

        $veiculoCliente = $query
            ->firstOrFail();

        $veiculoCliente->delete();

        return redirect()
            ->route('veiculosclientes.index')
            ->with(
                'success',
                'Veículo excluído com sucesso!'
            );
    }
}
