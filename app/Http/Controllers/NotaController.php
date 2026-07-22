<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Nota;

class NotaController extends Controller
{
    public function gerarpdf($id)
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
}