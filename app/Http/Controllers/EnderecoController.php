<?php

namespace App\Http\Controllers;
use App\Models\Pessoa;
use App\Models\Endereco;
use Illuminate\Http\Request;
use App\Http\Requests\EnderecoStoreRequest;

class EnderecoController extends Controller
{
    public function index()
    {
        $enderecos = Endereco::with(['pessoa:id_pessoa,nome'])->get();
        return view('listarendereco', compact('enderecos'));
    }

    public function create(string $id)
    {
        $pessoa = Pessoa::where('id_pessoa', $id)->first();
        return view('cadastroendereco', compact('pessoa'));
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
        $endereco->id_pessoa = $request->input("id_pessoa");
        $endereco->save();
        return redirect()->route('pessoas.index');
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
