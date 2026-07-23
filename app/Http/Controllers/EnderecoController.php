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
        $enderecos = Endereco::with(['pessoa'])->get();
        return view('endereco.listarendereco', compact('enderecos'));
    }

    public function create(string $id)
    {
        $user = User::find($id);
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
        $endereco->pessoa_id = $request->input("pessoa_id");
        $endereco->save();
        return redirect()->route('users.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $enderecos = Endereco::with('pessoa')->findOrFail($id);
        return view('endereco.editarendereco', compact('enderecos'));
    }

    public function update(Request $request, string $id)
    {
        $endereco = Endereco::findOrFail($id);

        // Atualiza o registro com os novos dados vindos da view
        $endereco->update([
            'cep'              => $request->input('cep'),
            'cidade'           => $request->input('city'),
            'bairro'           => $request->input('neighborhood'),
            'estado'           => $request->input('region'),
            'rua'              => $request->input('address'),
            'numero'           => $request->input('numero'),
            'ponto_referencia' => $request->input('ponto_referencia'),
        ]);

        return redirect()->route('users.index')->with('success', 'Endereço atualizado com sucesso!');
    }

    public function destroy(string $id)
    {
        $endereco = Endereco::findOrFail($id);
        $endereco->delete();

        return redirect()->route('users.index')->with('success', 'Endereço excluído com sucesso!');
    }
}