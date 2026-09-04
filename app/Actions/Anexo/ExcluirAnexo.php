<?php

namespace App\Actions\Anexo;

use App\Models\Anexo;
use Illuminate\Support\Facades\Storage;

class ExcluirAnexo
{
    public function execute(Anexo $anexo): void
    {
        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();
    }
}
