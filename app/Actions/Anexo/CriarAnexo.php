<?php

namespace App\Actions\Anexo;

use App\Models\Anexo;
use App\Models\AnexoVinculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CriarAnexo
{
    public function execute(
        Model $anexavel,
        UploadedFile $arquivo,
        string $tipo,
        ?string $observacoes = null
    ): Anexo {
        $caminho = $arquivo->store('anexos', 'public');

        try {
            return DB::transaction(function () use (
                $anexavel,
                $arquivo,
                $caminho,
                $tipo,
                $observacoes
            ) {
                $anexo = Anexo::create([
                    'arquivo' => $caminho,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'mime_type' => $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                ]);

                AnexoVinculo::create([
                    'anexo_id' => $anexo->id,
                    'vinculavel_type' => $anexavel::class,
                    'vinculavel_id' => $anexavel->getKey(),
                    'tipo' => $tipo,
                    'observacoes' => $observacoes,
                ]);

                return $anexo;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($caminho);

            throw $exception;
        }
    }
}
