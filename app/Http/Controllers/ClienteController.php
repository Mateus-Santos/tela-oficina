<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('pessoa')->get();
        return view('cliente.listarcliente', compact('clientes'));
    }

    public function create()
    {
        $pessoasSemCliente = Pessoa::doesntHave('cliente')->get();
        return view('cliente.cadastrocliente', compact('pessoasSemCliente'));
    }

    public function store(Request $request)
    {
        // Se preencheu o campo NOME, entende que é uma NOVA PESSOA
        if ($request->filled('nome')) {
            
            // Validações
            $request->validate([
                'nome'            => 'required|string|max:255',
                'email'           => 'nullable|required_if:criar_usuario,1|email|unique:users,email',
                'cpf'             => 'nullable|string|max:14|unique:pessoas,cpf',
                'rg'              => 'nullable|string|max:20',
                'data_nascimento' => 'nullable|date',
                'telefone_1'      => 'nullable|string|max:15',
                'telefone_2'      => 'nullable|string|max:15',
                'pontos'          => 'nullable|integer|min:0',
            ], [
                'email.required_if' => 'O e-mail é obrigatório para criar um usuário de acesso.'
            ]);

            $senhaGerada = null;

            // Executa em transação para garantir integridade (se falhar em um, desfaz tudo)
            DB::transaction(function () use ($request, &$senhaGerada) {
                
                // 1. Cria o registro na tabela PESSOAS
                $pessoa = Pessoa::create([
                    'nome'            => $request->input('nome'),
                    'cpf'             => $request->input('cpf'),
                    'rg'              => $request->input('rg'),
                    'data_nascimento' => $request->input('data_nascimento'),
                    'telefone_1'      => $request->input('telefone_1'),
                    'telefone_2'      => $request->input('telefone_2'),
                ]);

                // 2. Cria o registro na tabela CLIENTES
                Cliente::create([
                    'pessoa_id' => $pessoa->id,
                    'pontos'    => $request->input('pontos', 0),
                ]);

                // 3. Se o switch de "criar_usuario" estiver marcado e houver e-mail, cria o USER
                if ($request->has('criar_usuario') && $request->filled('email')) {
                    
                    // Gera uma senha aleatória de 8 caracteres
                    $senhaGerada = Str::random(8);

                    User::create([
                        'name'      => $pessoa->nome,
                        'email'     => $request->input('email'),
                        'password'  => Hash::make($senhaGerada),
                        'pessoa_id' => $pessoa->id,
                    ]);
                }
            });

            // Se gerou um usuário, retorna exibindo a senha temporária gerada
            if ($senhaGerada) {
                return redirect()->route('clientes.index')->with([
                    'success'          => 'Cliente e usuário criados com sucesso!',
                    'senha_temporaria' => $senhaGerada,
                    'email_usuario'    => $request->input('email')
                ]);
            }

        } else {
            // Se selecionou uma Pessoa já existente no select
            $request->validate([
                'pessoa_id' => 'required|exists:pessoas,id|unique:clientes,pessoa_id',
                'pontos'    => 'nullable|integer|min:0',
            ]);

            Cliente::create([
                'pessoa_id' => $request->input('pessoa_id'),
                'pontos'    => $request->input('pontos', 0),
            ]);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(string $id)
    {
        $cliente = Cliente::with('pessoa')->findOrFail($id);
        return view('cliente.showcliente', compact('cliente'));
    }

    public function edit(string $id)
    {
        $cliente = Cliente::with('pessoa')->findOrFail($id);
        return view('cliente.editarcliente', compact('cliente'));
    }

    public function update(Request $request, string $id)
    {
        $cliente = Cliente::with('pessoa')->findOrFail($id);

        $request->validate([
            'nome'            => 'required|string|max:255',
            'cpf'             => 'nullable|string|max:14|unique:pessoas,cpf,' . $cliente->pessoa_id,
            'rg'              => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'telefone_1'      => 'nullable|string|max:15',
            'telefone_2'      => 'nullable|string|max:15',
            'pontos'          => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $cliente) {
            $cliente->pessoa->update([
                'nome'            => $request->input('nome'),
                'cpf'             => $request->input('cpf'),
                'rg'              => $request->input('rg'),
                'data_nascimento' => $request->input('data_nascimento'),
                'telefone_1'      => $request->input('telefone_1'),
                'telefone_2'      => $request->input('telefone_2'),
            ]);

            $cliente->update([
                'pontos' => $request->input('pontos'),
            ]);
        });

        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(string $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente excluído com sucesso!');
    }
}