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
        $user->cpf = str_replace(['.', '-'], "", $request->input("cpf"));
        $user->rg = $request->input("rg");
        $user->permitions = 2;
        $user->save();
        return redirect()->route('users.index');
    }

    public function show(string $id)
    {
        $user = User::find($id);
        $enderecos = Endereco::where('id_user', $id)->get();
        return view('user.showuser', ['user' => $user, 'enderecos' => $enderecos]);
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return view('user.editaruser', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        $request->merge([
            'name' => trim($request->input('name')),
            'email' => strtolower($request->input('email')),
            'telefone_1' => preg_replace('/\D/', '', $request->input('telefone_1')),
            'telefone_2' => preg_replace('/\D/', '', $request->input('telefone_2')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'cpf' => 'required|string|max:11|unique:users,cpf,' . $id,
            'rg' => 'nullable|string',
            'telefone_1' => 'required|string',
            'telefone_2' => 'nullable|string',
        ]);
        
        $user = User::findOrFail($id);
        $user->update($validated);
        
        return redirect()->route('users.index');
    }

    public function destroy(string $id_user)
    {
        $user = User::where('id_user', $id_user)->delete();
        return redirect()->route('users.index');
    }
}
