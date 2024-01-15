<?php

namespace App\Http\Controllers;
use App\Models\Pessoa;
use App\Models\Endereco;
use Illuminate\Http\Request;

class EnderecoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $enderecos = Endereco::with(['pessoa:id_pessoa,nome'])->get();
        return view('listarendereco', compact('enderecos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pessoas = Pessoa::all();
        return view('cadastroendereco', compact('pessoas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $endereco = new Endereco();
        $endereco->cep = $request->input("cep");
        $endereco->cidade = $request->input("cidade");
        $endereco->bairro = $request->input("bairro");
        $endereco->estado = $request->input("estado");
        $endereco->rua = $request->input("rua");
        $endereco->endereco = $request->input("endereco");
        $endereco->numero = $request->input("numero");
        $endereco->ponto_referencia = $request->input("ponto_referencia");
        $endereco->id_pessoa = $request->input("id_pessoa");
        $endereco->save();
        return redirect()->route('enderecos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('editarendereco', ['endereco' -> $endereco]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
