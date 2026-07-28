<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Nota;

class NotaController extends Controller
{
    public function gerarpdf(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculosCliente',
            'itens.itemable'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.nota', compact('nota'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("nota-{$nota->id}.pdf");
    }

    public function index(Request $request)
    {
        $notas = Nota::with(['cliente', 'veiculosCliente'])
            ->filtro($request->all())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('notas_item.listar_notas_itens', compact('notas'));
    }

    public function destroy(string $id)
    {
        Nota::findOrFail($id)->delete();
        return redirect()->route('notasitem.index')->with('success', 'Nota removida!');
    }

    public function show(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculoscliente',
            'notasitem.itemable'
        ])->findOrFail($id);

        $itens = $nota->notasitem;

        $valorTotal = $itens->sum(function ($item) {
            return $item->quantidade * $item->valor_unitario;
        });

        return view('notas_item.show_notas_itens', compact('nota', 'itens', 'valorTotal'));
    }
}
