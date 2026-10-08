<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    private function limparMascara(?string $valor): ?string
    {
        if (! $valor) {
            return null;
        }

        $valorLimpo = preg_replace('/\D/', '', $valor);

        return $valorLimpo !== ''
            ? $valorLimpo
            : null;
    }

    public function index(Request $request)
    {
        $nome = trim((string) $request->input('nome', ''));

        $cpf = $this->limparMascara(
            $request->input('cpf')
        );

        $telefone = $this->limparMascara(
            $request->input('telefone')
        );

        $clientes = Cliente::query()
            ->with('pessoa')
            ->when(
                $nome !== '',
                function ($query) use ($nome) {
                    $query->whereHas(
                        'pessoa',
                        function ($pessoaQuery) use ($nome) {
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
                        function ($pessoaQuery) use ($cpf) {
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
                function ($query) use ($telefone) {
                    $query->whereHas(
                        'pessoa',
                        function ($pessoaQuery) use ($telefone) {
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
        $pessoasSemCliente = Pessoa::query()
            ->doesntHave('cliente')
            ->orderBy('nome')
            ->get();

        return view(
            'cliente.cadastrocliente',
            compact('pessoasSemCliente')
        );
    }

    public function store(Request $request)
    {
        if ($request->filled('nome')) {
            $cpfLimpo = $this->limparMascara(
                $request->input('cpf')
            );

            $rgLimpo = $this->limparMascara(
                $request->input('rg')
            );

            $telefone1Limpo = $this->limparMascara(
                $request->input('telefone_1')
            );

            $telefone2Limpo = $this->limparMascara(
                $request->input('telefone_2')
            );

            $request->merge([
                'cpf' => $cpfLimpo,
                'rg' => $rgLimpo,
                'telefone_1' => $telefone1Limpo,
                'telefone_2' => $telefone2Limpo,
            ]);

            $request->validate(
                [
                    'nome' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'email' => [
                        'nullable',
                        'required_if:criar_usuario,1',
                        'email',
                        'unique:users,email',
                    ],

                    'cpf' => [
                        'nullable',
                        'string',
                        'size:11',
                        'unique:pessoas,cpf',
                    ],

                    'rg' => [
                        'nullable',
                        'string',
                        'max:14',
                    ],

                    'data_nascimento' => [
                        'nullable',
                        'date',
                    ],

                    'telefone_1' => [
                        'nullable',
                        'string',
                        'min:10',
                        'max:11',
                    ],

                    'telefone_2' => [
                        'nullable',
                        'string',
                        'min:10',
                        'max:11',
                    ],

                    'pontos' => [
                        'nullable',
                        'integer',
                        'min:0',
                    ],
                ],
                [
                    'nome.required' => 'O campo nome é obrigatório.',

                    'email.required_if' => 'O e-mail é obrigatório para criar um usuário de acesso.',

                    'email.email' => 'Informe um endereço de e-mail válido.',

                    'email.unique' => 'Este e-mail já está em uso.',

                    'cpf.unique' => 'Este CPF já está cadastrado.',

                    'cpf.size' => 'O CPF deve possuir exatamente 11 dígitos.',

                    'rg.max' => 'O campo RG não pode ter mais que 14 dígitos.',

                    'telefone_1.min' => 'O Telefone Principal deve ter pelo menos 10 dígitos (DDD + número).',

                    'telefone_1.max' => 'O Telefone Principal não pode ter mais que 11 dígitos.',

                    'telefone_2.min' => 'O Telefone Secundário deve ter pelo menos 10 dígitos (DDD + número).',

                    'telefone_2.max' => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
                ]
            );

            $senhaGerada = null;

            DB::transaction(
                function () use (
                    $request,
                    $cpfLimpo,
                    $rgLimpo,
                    $telefone1Limpo,
                    $telefone2Limpo,
                    &$senhaGerada
                ) {
                    $pessoa = Pessoa::create([
                        'nome' => $request->input('nome'),
                        'cpf' => $cpfLimpo,
                        'rg' => $rgLimpo,
                        'data_nascimento' => $request->input('data_nascimento'),
                        'telefone_1' => $telefone1Limpo,
                        'telefone_2' => $telefone2Limpo,
                    ]);

                    Cliente::create([
                        'pessoa_id' => $pessoa->id,
                        'pontos' => $request->input('pontos') ?? 0,
                    ]);

                    if (
                        $request->boolean('criar_usuario')
                        && $request->filled('email')
                    ) {
                        $senhaGerada = Str::random(8);

                        User::create([
                            'email' => $request->input('email'),
                            'password' => Hash::make(
                                $senhaGerada
                            ),
                            'pessoa_id' => $pessoa->id,
                        ]);
                    }
                }
            );

            if ($senhaGerada) {
                return redirect()
                    ->route('clientes.index')
                    ->with([
                        'success' => 'Cliente e usuário criados com sucesso!',

                        'senha_temporaria' => $senhaGerada,

                        'email_usuario' => $request->input('email'),
                    ]);
            }
        } else {
            $request->validate(
                [
                    'pessoa_id' => [
                        'required',
                        'exists:pessoas,id',
                        'unique:clientes,pessoa_id',
                    ],

                    'pontos' => [
                        'nullable',
                        'integer',
                        'min:0',
                    ],
                ],
                [
                    'pessoa_id.required' => 'Selecione uma pessoa da lista ou preencha os dados de uma nova pessoa.',

                    'pessoa_id.unique' => 'Esta pessoa já é um cliente cadastrado.',
                ]
            );

            Cliente::create([
                'pessoa_id' => $request->input('pessoa_id'),

                'pontos' => $request->input('pontos') ?? 0,
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
        $cliente = Cliente::with('pessoa')
            ->findOrFail($id);

        return view(
            'cliente.showcliente',
            compact('cliente')
        );
    }

    public function edit(string $id)
    {
        $usuarioLogado = auth()->user();

        if ($usuarioLogado->permitions === 1) {
            $cliente = Cliente::with('pessoa')
                ->findOrFail($id);

            $usuario = User::where(
                'pessoa_id',
                $cliente->pessoa_id
            )->first();
        } else {
            $usuario = $usuarioLogado;

            $cliente = Cliente::with('pessoa')
                ->where(
                    'pessoa_id',
                    $usuarioLogado->pessoa_id
                )
                ->firstOrFail();
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
        Request $request,
        string $id
    ) {
        $cliente = Cliente::with('pessoa')
            ->findOrFail($id);

        $usuario = User::where(
            'pessoa_id',
            $cliente->pessoa_id
        )->first();

        $cpfLimpo = $this->limparMascara(
            $request->input('cpf')
        );

        $rgLimpo = $this->limparMascara(
            $request->input('rg')
        );

        $telefone1Limpo = $this->limparMascara(
            $request->input('telefone_1')
        );

        $telefone2Limpo = $this->limparMascara(
            $request->input('telefone_2')
        );

        $request->merge([
            'cpf' => $cpfLimpo,
            'rg' => $rgLimpo,
            'telefone_1' => $telefone1Limpo,
            'telefone_2' => $telefone2Limpo,
        ]);

        $regras = [
            'nome' => [
                'required',
                'string',
                'max:255',
            ],

            'cpf' => [
                'nullable',
                'string',
                'size:11',

                Rule::unique(
                    'pessoas',
                    'cpf'
                )->ignore(
                    $cliente->pessoa_id
                ),
            ],

            'rg' => [
                'nullable',
                'string',
                'max:14',
            ],

            'data_nascimento' => [
                'nullable',
                'date',
            ],

            'telefone_1' => [
                'nullable',
                'string',
                'min:10',
                'max:11',
            ],

            'telefone_2' => [
                'nullable',
                'string',
                'min:10',
                'max:11',
            ],

            'pontos' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];

        if ($usuario) {
            $regras['email'] = [
                'required',
                'email',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $usuario->id
                ),
            ];
        }

        $request->validate(
            $regras,
            [
                'nome.required' => 'O campo nome é obrigatório.',

                'email.required' => 'O e-mail é obrigatório para usuários de acesso.',

                'email.email' => 'Informe um endereço de e-mail válido.',

                'email.unique' => 'Este e-mail já está em uso.',

                'cpf.unique' => 'Este CPF já pertence a outra pessoa.',

                'cpf.size' => 'O CPF deve possuir exatamente 11 dígitos.',

                'rg.max' => 'O campo RG não pode ter mais que 14 dígitos.',

                'telefone_1.min' => 'O Telefone Principal deve ter pelo menos 10 dígitos.',

                'telefone_1.max' => 'O Telefone Principal não pode ter mais que 11 dígitos.',

                'telefone_2.min' => 'O Telefone Secundário deve ter pelo menos 10 dígitos.',

                'telefone_2.max' => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
            ]
        );

        DB::transaction(
            function () use (
                $request,
                $cliente,
                $usuario,
                $cpfLimpo,
                $rgLimpo,
                $telefone1Limpo,
                $telefone2Limpo
            ) {
                $cliente->pessoa->update([
                    'nome' => $request->input('nome'),
                    'cpf' => $cpfLimpo,
                    'rg' => $rgLimpo,
                    'data_nascimento' => $request->input('data_nascimento'),
                    'telefone_1' => $telefone1Limpo,
                    'telefone_2' => $telefone2Limpo,
                ]);

                $cliente->update([
                    'pontos' => $request->input('pontos') ?? 0,
                ]);

                if ($usuario) {
                    $usuario->update([
                        'email' => $request->input('email'),
                    ]);
                }
            }
        );

        return redirect()
            ->route('clientes.index')
            ->with(
                'success',
                'Cliente atualizado com sucesso!'
            );
    }

    public function destroy(string $id)
    {
        $cliente = Cliente::findOrFail($id);

        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with(
                'success',
                'Cliente excluído com sucesso!'
            );
    }
}
