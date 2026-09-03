<?php

namespace App\Actions\Anexo;

use App\Models\Anexo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class CriarAnexo
{
    public function execute(
        Model $anexavel,
        UploadedFile $arquivo,
        string $tipo,
        ?string $observacoes = null
    ): Anexo {
        $caminho = $arquivo->store('anexos', 'public');

        return $anexavel->anexos()->create([
            'tipo' => $tipo,
            'arquivo' => $caminho,
            'nome_original' => $arquivo->getClientOriginalName(),
            'mime_type' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
            'observacoes' => $observacoes,
        ]);
    }
}
