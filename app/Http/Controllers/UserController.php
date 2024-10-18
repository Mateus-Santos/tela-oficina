<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Http\Request;

class UserController extends Controller {

    public function index()
    {
        $users = User::all();
        return view('user.listaruser', compact('users'));
    }

    public function create()
    {
        return view('user.cadastrouser');
    }

    public function store(Request $request)
    {
        $user = new User();
        $onlyconsonants = str_replace(['-', ''], "", $request);
        $user->name = $request->input("nome");
        $user->email = $request->input("email");
        $user->data_nascimento = $request->input("data_nascimento");
        $user->telefone_1 = str_replace(['-', ' ', '(', ')'], "", $request->input("telefone_1"));
        $user->telefone_2 = str_replace(['-', ' ', '(', ')'], "", $request->input("telefone_2"));
        $user->cpf = str_replace(['.', '-'], "", $request->input("cpf"));;
        $user->rg = $request->input("rg");
        $user->save();
        return redirect()->route('user.index');
    }

    public function show(string $id)
    {
        $user = User::find($id);
        $enderecos = Endereco::where('id_user', $id)->get();
        return view('user.showuser', ['user' => $user, 'enderecos' => $enderecos]);
    }

    public function edit(string $id_user)
    {
        $user = User::where('id_user', $id_user)->first();
        return view('user.editaruser', array('user' => $user));
    }

    public function update(Request $request, string $id_user)
    {
        $user = User::where('id_user', $id_user)->first();

        $user->update([
            'id_user' => $id_user,
            'nome' => $request->nome,
            'data_nascimento' => $request->data_nascimento,
            'email' => $request->email,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone_1' => $request->telefone_1,
            'telefone_2' => $request->telefone_2,
            'status' => $request->id_cliente,
            'permitions' => $request->permitions,
        ]);
        
        return redirect()->route('users.index');
    }

    public function destroy(string $id_user)
    {
        $user = User::where('id_user', $id_user)->delete();
        return redirect()->route('users.index');
    }
}
