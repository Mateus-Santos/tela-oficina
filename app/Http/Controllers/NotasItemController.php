<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotasItem;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Validation\Rule;

class NotasItemController extends Controller
{
    public function index()
    {
        $notas = Nota::with(['cliente.user', 'itens', 'veiculo'])->get();
        return view('notas_item.listar_notas_itens', compact('notas'));
    }

    public function create()
    {
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view('notas_item.cadastro_notas_itens', compact('ordemservicos', 'produtos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'veiculo_cliente_id'        => 'nullable|integer', // Opcional para balcão
            'itens'                     => 'required|array|min:1',
            'itens.*.itemable_type'     => ['required', 'string', \Illuminate\Validation\Rule::in(['App\Models\Produto', 'App\Models\OrdemServico'])],
            'itens.*.itemable_id'       => 'required|integer', 
            'itens.*.descricao'         => 'required|string|max:250',
            'itens.*.quantidade'        => 'required|integer|min:1',
            'itens.*.valor_unitario'    => 'required|numeric|min:0',
            'itens.*.desconto'          => 'nullable|numeric|min:0',
            'itens.*.garantia_dias'     => 'nullable|integer|min:0',
        ]);

        $itensEnviados = $request->input('itens');

        try {
            $nota = new Nota();

            $nota->cliente_id = $request->input('cliente_id') ?: null;
            $nota->veiculo_id = $request->input('veiculo_cliente_id') ?: null;
            $nota->tipo       = 'Venda';
            $nota->status     = 'Aberto';

            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $item) {
                $qtd = (int) $item['quantidade'];
                $vUnit = (float) $item['valor_unitario'];
                $desc = (float) ($item['desconto'] ?? 0);

                $subtotalGeral += ($qtd * $vUnit);
                $descontoGeral += $desc;
            }

            $nota->subtotal = $subtotalGeral;
            $nota->desconto = $descontoGeral;
            $nota->total    = $subtotalGeral - $descontoGeral;

            $nota->save();

            foreach ($itensEnviados as $dadosItem) {
                if ($dadosItem['itemable_type'] === 'App\Models\Produto') {
                    if (!Produto::where('id', $dadosItem['itemable_id'])->exists()) {
                        return redirect()->back()->withErrors(['itens' => 'Um dos produtos selecionados não existe.'])->withInput();
                    }
                } else if ($dadosItem['itemable_type'] === 'App\Models\OrdemServico') {
                    if (!OrdemServico::where('id', $dadosItem['itemable_id'])->exists()) {
                        return redirect()->back()->withErrors(['itens' => 'Uma das Ordens de Serviço é inválida.'])->withInput();
                    }
                }

                $item = new NotasItem();
                
                $item->nota_id = $nota->id; 
                
                $item->itemable_type = $dadosItem['itemable_type'];
                $item->itemable_id   = $dadosItem['itemable_id'];
                
                $item->descricao = $dadosItem['descricao'];
                $item->quantidade = (int) $dadosItem['quantidade'];
                
                $valorUnitario = (float) $dadosItem['valor_unitario'];
                $desconto = (float) ($dadosItem['desconto'] ?? 0);
                
                $item->valor_unitario = $valorUnitario;
                $item->desconto = $desconto;
                $item->valor_total = ($valorUnitario * $item->quantidade) - $desconto;

                if (!empty($dadosItem['garantia_dias'])) {
                    $item->garantia_dias = (int) $dadosItem['garantia_dias'];
                    $item->garantia_inicio = now()->format('Y-m-d');
                    $item->garantia_fim = now()->addDays($item->garantia_dias)->format('Y-m-d');
                }

                $item->save();
            }

            return redirect()->route('notasitem.index')
                            ->with('success', 'Nota Fiscal criada com ' . count($itensEnviados) . ' itens!');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['erro_banco' => 'Falha ao salvar a venda: ' . $e->getMessage()])
                            ->withInput();
        }
    }

    public function edit(string $id)
    {
        $item = NotasItem::findOrFail($id);
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();
        
        // CORREÇÃO: Removido o antigo $servicos que não existe mais e mantido a coerência com as OSs
        return view('notas_item.editar_notas_item', compact('item', 'ordemservicos', 'produtos'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'ordem_servico_id' => 'required|exists:ordem_servicos,id',
            'descricao'        => 'required|string|max:250',
            'quantidade'       => 'required|integer|min:1',
            'valor_unitario'   => 'required|numeric|min:0',
            'desconto'         => 'nullable|numeric|min:0',
        ]);

        $item = NotasItem::findOrFail($id);
        $item->ordem_servico_id = $request->input('ordem_servico_id');
        $item->descricao = $request->input('descricao');
        $item->quantidade = $request->input('quantidade');
        $item->valor_unitario = $request->input('valor_unitario');
        $item->desconto = $request->input('desconto', 0);
        $item->valor_total = ($item->valor_unitario * $item->quantidade) - $item->desconto;
        
        $item->garantia_dias = $request->input('garantia_dias');
        
        // Recalcula as datas de garantia caso os dias tenham mudado no update
        if ($item->garantia_dias) {
            $item->garantia_inicio = now()->format('Y-m-d');
            $item->garantia_fim = now()->addDays((int)$item->garantia_dias)->format('Y-m-d');
        } else {
            $item->garantia_inicio = null;
            $item->garantia_fim = null;
        }

        $item->update();

        return redirect()->route('notasitem.index')->with('success', 'Item atualizado!');
    }

    public function destroy(string $id)
    {
        NotasItem::findOrFail($id)->delete();
        return redirect()->route('notasitem.index')->with('success', 'Item removido!');
    }
}