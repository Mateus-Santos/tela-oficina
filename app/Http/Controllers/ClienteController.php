<?php

namespace App\Http\Controllers;

use App\Actions\Clientes\AtualizarCliente;
use App\Actions\Clientes\CriarClienteCompleto;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    private function limparMascara(
        ?string $valor
    ): ?string {
        if (! $valor) {
            return null;
        }

        $valorLimpo =
            preg_replace(
                '/\D/',
                '',
                $valor
            );

        return $valorLimpo !== ''
            ? $valorLimpo
            : null;
    }

    public function index(Request $request)
    {
        $nome =
            trim(
                (string) $request->input(
                    'nome',
                    ''
                )
            );

        $cpf =
            $this->limparMascara(
                $request->input('cpf')
            );

        $telefone =
            $this->limparMascara(
                $request->input(
                    'telefone'
                )
            );

        $clientes =
            Cliente::query()
                ->with('pessoa')
                ->when(
                    $nome !== '',
                    function ($query) use ($nome) {
                        $query->whereHas(
                            'pessoa',
                            function (
                                $pessoaQuery
                            ) use ($nome) {
                                $pessoaQuery->where(
                                    'nome',
                                    'like',
                                    "%{$nome}%"
                                );
                            }
                        );
                    }
                )
                ->when(
                    $cpf,
                    function ($query) use ($cpf) {
                        $query->whereHas(
                            'pessoa',
                            function (
                                $pessoaQuery
                            ) use ($cpf) {
                                $pessoaQuery->where(
                                    'cpf',
                                    'like',
                                    "%{$cpf}%"
                                );
                            }
                        );
                    }
                )
                ->when(
                    $telefone,
                    function (
                        $query
                    ) use ($telefone) {
                        $query->whereHas(
                            'pessoa',
                            function (
                                $pessoaQuery
                            ) use ($telefone) {
                                $pessoaQuery
                                    ->where(
                                        'telefone_1',
                                        'like',
                                        "%{$telefone}%"
                                    )
                                    ->orWhere(
                                        'telefone_2',
                                        'like',
                                        "%{$telefone}%"
                                    );
                            }
                        );
                    }
                )
                ->latest('id')
                ->paginate(20)
                ->withQueryString();

        return view(
            'cliente.listarcliente',
            compact('clientes')
        );
    }

    public function create()
    {
        $pessoasSemCliente =
            Pessoa::query()
                ->doesntHave('cliente')
                ->orderBy('nome')
                ->get();

        return view(
            'cliente.cadastrocliente',
            compact(
                'pessoasSemCliente'
            )
        );
    }

    public function store(StoreClienteRequest $request, CriarClienteCompleto $criarCliente)
    {
        if ($request->filled('nome')) {
            $senhaGerada = $criarCliente->execute(
                $request->validated(),
                $request->boolean('criar_usuario')
            );

            if ($senhaGerada) {
                return redirect()
                    ->route(
                        'clientes.index'
                    )
                    ->with([
                        'success' => 'Cliente e usuário criados com sucesso!',

                        'senha_temporaria' => $senhaGerada,

                        'email_usuario' => $request->input(
                            'email'
                        ),
                    ]);
            }
        } else {
            Cliente::create([
                'pessoa_id' => $request->input(
                    'pessoa_id'
                ),

                'pontos' => $request->input(
                    'pontos'
                ) ?? 0,
            ]);
        }

        return redirect()
            ->route('clientes.index')
            ->with(
                'success',
                'Cliente cadastrado com sucesso!'
            );
    }

    public function show(string $id)
    {
        $cliente =
            Cliente::with('pessoa')
                ->findOrFail($id);

        return view(
            'cliente.showcliente',
            compact('cliente')
        );
    }

    public function edit(string $id)
    {
        $usuarioLogado =
            auth()->user();

        if ($usuarioLogado->isAdmin()) {
            $cliente =
                Cliente::with('pessoa')
                    ->findOrFail($id);

            $usuario =
                User::where(
                    'pessoa_id',
                    $cliente->pessoa_id
                )->first();
        } elseif (
            $usuarioLogado->isCliente()
        ) {
            $usuario =
                $usuarioLogado;

            $cliente =
                $usuarioLogado->cliente;

            if (! $cliente) {
                abort(
                    403,
                    'Usuário não possui cliente vinculado.'
                );
            }

            $cliente->load('pessoa');
        } else {
            abort(
                403,
                'Acesso não autorizado.'
            );
        }

        return view(
            'cliente.editarcliente',
            compact(
                'cliente',
                'usuario'
            )
        );
    }

    public function update(
        UpdateClienteRequest $request,
        string $id,
        AtualizarCliente $atualizarCliente
    ) {
        $usuarioLogado =
            $request->user();

        if ($usuarioLogado->isAdmin()) {
            $cliente =
                Cliente::with('pessoa')
                    ->findOrFail($id);
        } elseif (
            $usuarioLogado->isCliente()
        ) {
            $cliente =
                $usuarioLogado->cliente;

            if (! $cliente) {
                abort(
                    403,
                    'Usuário não possui cliente vinculado.'
                );
            }

            $cliente->load('pessoa');
        } else {
            abort(
                403,
                'Acesso não autorizado.'
            );
        }

        $usuario =
            User::where(
                'pessoa_id',
                $cliente->pessoa_id
            )->first();

        $atualizarCliente->execute(
            $cliente,
            $usuario,
            $request->validated()
        );

        return redirect()
            ->route(
                $usuarioLogado->isCliente()
                    ? 'perfil'
                    : 'clientes.index'
            )
            ->with(
                'success',
                'Cliente atualizado com sucesso!'
            );
    }

    public function destroy(string $id)
    {
        $excluido = DB::transaction(function () use ($id): bool {
            $cliente = Cliente::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if (DB::table('users')
                ->where('pessoa_id', $cliente->pessoa_id)
                ->exists()) {
                return false;
            }

            foreach ([
                'veiculos_clientes',
                'cliente_veiculo_cliente',
                'ordem_servicos',
                'notas',
                'contas_receber',
            ] as $tabela) {
                if (DB::table($tabela)
                    ->where('cliente_id', $cliente->id)
                    ->exists()) {
                    return false;
                }
            }

            return (bool) $cliente->delete();
        });

        if (! $excluido) {
            return redirect()
                ->route('clientes.index')
                ->with(
                    'error',
                    'Este cliente possui vínculos cadastrados e não pode ser excluído.'
                );
        }

        return redirect()
            ->route('clientes.index')
            ->with(
                'success',
                'Cliente excluído com sucesso!'
            );
    }
}
