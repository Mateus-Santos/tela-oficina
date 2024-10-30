<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Http\Request;
use App\Http\Requests\EnderecoStoreRequest;

class EnderecoController extends Controller
{
    public function index()
    {
        $enderecos = Endereco::with(['user:id_user,nome'])->get();
        return view('endereco.listarendereco', compact('enderecos'));
    }

    public function create(string $id)
    {
        $user = User::where('id_user', $id)->first();
        return view('endereco.cadastroendereco', compact('user'));
    }

    public function store(EnderecoStoreRequest $request)
    {
        $endereco = new Endereco();
        $endereco->cep = $request->input("cep");
        $endereco->cidade = $request->input("city");
        $endereco->bairro = $request->input("neighborhood");
        $endereco->estado = $request->input("region");
        $endereco->rua = $request->input("address");
        $endereco->numero = $request->input("numero");
        $endereco->ponto_referencia = $request->input("ponto_referencia");
        $endereco->id_user = $request->input("id_user");
        $endereco->save();
        return redirect()->route('users.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        return view('editarendereco', ['endereco' -> $endereco]);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
