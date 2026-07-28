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
    /**
     * Remove todos os caracteres não numéricos.
     */
    private function limparMascara(?string $valor): ?string
    {
        return $valor ? preg_replace('/\D/', '', $valor) : null;
    }

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

            // Limpa as máscaras antes de validar e salvar
            $cpfLimpo       = $this->limparMascara($request->input('cpf'));
            $rgLimpo        = $this->limparMascara($request->input('rg'));
            $telefone1Limpo = $this->limparMascara($request->input('telefone_1'));
            $telefone2Limpo = $this->limparMascara($request->input('telefone_2'));

            // Mescla os campos limpos na request para validação
            $request->merge([
                'cpf'        => $cpfLimpo,
                'rg'         => $rgLimpo,
                'telefone_1' => $telefone1Limpo,
                'telefone_2' => $telefone2Limpo,
            ]);

            // Validações com os campos já sem máscara
            $request->validate([
                'nome'            => 'required|string|max:255',
                'email'           => 'nullable|required_if:criar_usuario,1|email|unique:users,email',
                'cpf'             => 'nullable|string|size:11|unique:pessoas,cpf',
                'rg'              => 'nullable|string|max:14',
                'data_nascimento' => 'nullable|date',
                'telefone_1'      => 'nullable|string|min:10|max:11',
                'telefone_2'      => 'nullable|string|min:10|max:11',
                'pontos'          => 'nullable|integer|min:0',
            ], [
                'nome.required'     => 'O campo nome é obrigatório.',
                'email.required_if' => 'O e-mail é obrigatório para criar um usuário de acesso.',
                'email.email'       => 'Informe um endereço de e-mail válido.',
                'email.unique'      => 'Este e-mail já está em uso.',
                'cpf.unique'        => 'Este CPF já está cadastrado.',
                'cpf.size'          => 'O CPF deve possuir exatamente 11 dígitos.',
                'rg.max'            => 'O campo RG não pode ter mais que 14 dígitos.',
                'telefone_1.min'    => 'O Telefone Principal deve ter pelo menos 10 dígitos (DDD + número).',
                'telefone_1.max'    => 'O Telefone Principal não pode ter mais que 11 dígitos.',
                'telefone_2.min'    => 'O Telefone Secundário deve ter pelo menos 10 dígitos (DDD + número).',
                'telefone_2.max'    => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
            ]);

            $senhaGerada = null;

            // Executa em transação para garantir integridade
            DB::transaction(function () use ($request, $cpfLimpo, $rgLimpo, $telefone1Limpo, $telefone2Limpo, &$senhaGerada) {

                // 1. Cria o registro na tabela PESSOAS (Apenas com dígitos)
                $pessoa = Pessoa::create([
                    'nome'            => $request->input('nome'),
                    'cpf'             => $cpfLimpo,
                    'rg'              => $rgLimpo,
                    'data_nascimento' => $request->input('data_nascimento'),
                    'telefone_1'      => $telefone1Limpo,
                    'telefone_2'      => $telefone2Limpo,
                ]);

                // 2. Cria o registro na tabela CLIENTES
                Cliente::create([
                    'pessoa_id' => $pessoa->id,
                    'pontos'    => $request->input('pontos', 0),
                ]);

                // 3. Se o switch de "criar_usuario" estiver marcado e houver e-mail, cria o USER
                if ($request->has('criar_usuario') && $request->filled('email')) {

                    $senhaGerada = Str::random(8);

                    User::create([
                        'name'      => $pessoa->nome,
                        'email'     => $request->input('email'),
                        'password'  => Hash::make($senhaGerada),
                        'pessoa_id' => $pessoa->id,
                    ]);
                }
            });

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
            ], [
                'pessoa_id.required' => 'Selecione uma pessoa da lista ou preencha os dados de uma nova pessoa.',
                'pessoa_id.unique'   => 'Esta pessoa já é um cliente cadastrado.',
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

        $cpfLimpo       = $this->limparMascara($request->input('cpf'));
        $rgLimpo        = $this->limparMascara($request->input('rg'));
        $telefone1Limpo = $this->limparMascara($request->input('telefone_1'));
        $telefone2Limpo = $this->limparMascara($request->input('telefone_2'));

        $request->merge([
            'cpf'        => $cpfLimpo,
            'rg'         => $rgLimpo,
            'telefone_1' => $telefone1Limpo,
            'telefone_2' => $telefone2Limpo,
        ]);

        $request->validate([
            'nome'            => 'required|string|max:255',
            'cpf'             => 'nullable|string|size:11|unique:pessoas,cpf,' . $cliente->pessoa_id,
            'rg'              => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'telefone_1'      => 'nullable|string|min:10|max:11',
            'telefone_2'      => 'nullable|string|min:10|max:11',
            'pontos'          => 'required|integer|min:0',
        ], [
            'nome.required'  => 'O campo nome é obrigatório.',
            'cpf.unique'     => 'Este CPF já pertence a outra pessoa.',
            'cpf.size'       => 'O CPF deve possuir exatamente 11 dígitos.',
            'rg.max'         => 'O campo RG não pode ter mais que 14 dígitos.',
            'telefone_1.min' => 'O Telefone Principal deve ter pelo menos 10 dígitos.',
            'telefone_1.max' => 'O Telefone Principal não pode ter mais que 11 dígitos.',
            'telefone_2.min' => 'O Telefone Secundário deve ter pelo menos 10 dígitos.',
            'telefone_2.max' => 'O Telefone Secundário não pode ter mais que 11 dígitos.',
        ]);

        DB::transaction(function () use ($request, $cliente, $cpfLimpo, $rgLimpo, $telefone1Limpo, $telefone2Limpo) {
            $cliente->pessoa->update([
                'nome'            => $request->input('nome'),
                'cpf'             => $cpfLimpo,
                'rg'              => $rgLimpo,
                'data_nascimento' => $request->input('data_nascimento'),
                'telefone_1'      => $telefone1Limpo,
                'telefone_2'      => $telefone2Limpo,
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