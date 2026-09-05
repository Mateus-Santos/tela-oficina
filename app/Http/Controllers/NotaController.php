<?php

namespace App\Http\Controllers;

use App\Actions\Notas\FinalizarNota;
use App\Models\Nota;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use InvalidArgumentException;

class NotaController extends Controller
{
    public function finalizar(string $id, FinalizarNota $finalizarNota)
    {
        $nota = Nota::findOrFail($id);

        try {
            $finalizarNota->execute($nota);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withErrors([
                    'finalizacao' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('notas.show', $nota->id)
            ->with('success', "Nota #{$nota->id} finalizada com sucesso!");
    }

    public function gerarpdf(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculosCliente',
            'itens.itemable',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.nota', compact('nota'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("nota-{$nota->id}.pdf");
    }

    public function index(Request $request)
    {
        $notas = Nota::with([
            'cliente.pessoa',
            'veiculosCliente',
            'itens',
        ])
            ->filtro($request->all())
            ->orderBy('created_at', 'desc')
            ->get();

        return view(
            'notas_item.listar_notas_itens',
            compact('notas')
        );
    }

    public function destroy(string $id)
    {
        $nota = Nota::findOrFail($id);

        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route('notas.show', $nota->id)
                ->withErrors([
                    'nota' => 'Notas finalizadas ou canceladas não podem ser excluídas.',
                ]);
        }

        $nota->delete();

        return redirect()
            ->route('notasitem.index')
            ->with('success', 'Nota removida!');
    }

    public function show(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculosCliente',
            'itens.itemable',
        ])->findOrFail($id);

        $itens = $nota->itens;

        $valorTotal = $itens->sum(function ($item) {
            return ($item->quantidade * $item->valor_unitario) - $item->desconto;
        });

        return view(
            'notas_item.show_notas_itens',
            compact('nota', 'itens', 'valorTotal')
        );
    }
}
