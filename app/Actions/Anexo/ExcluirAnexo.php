<?php

namespace App\Actions\Anexo;

use App\Models\AnexoVinculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExcluirAnexo
{
    public function execute(AnexoVinculo $vinculo): void
    {
        DB::transaction(function () use ($vinculo) {
            $anexo = $vinculo->anexo;
            $vinculo->delete();

            if ($anexo->vinculos()->exists()) {
                return;
            }

            if (
                $anexo->arquivo
                && Storage::disk('public')->exists($anexo->arquivo)
            ) {
                Storage::disk('public')->delete($anexo->arquivo);
            }

            $anexo->delete();
        });
    }
}
