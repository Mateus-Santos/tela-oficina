<?php

namespace App\Actions\Anexo;

use App\Models\Anexo;
use App\Models\AnexoVinculo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AtualizarAnexo
{
    public function execute(
        AnexoVinculo $vinculo,
        string $tipo,
        ?string $observacoes = null,
        ?UploadedFile $arquivo = null
    ): AnexoVinculo {
        $novoCaminho = null;
        $caminhoAnterior = null;

        if ($arquivo) {
            $novoCaminho = $arquivo->store(
                'anexos',
                'private'
            );
        }

        try {
            DB::transaction(
                function () use (
                    $vinculo,
                    $tipo,
                    $observacoes,
                    $arquivo,
                    $novoCaminho,
                    &$caminhoAnterior
                ) {
                    $vinculo = AnexoVinculo::query()
                        ->lockForUpdate()
                        ->with('anexo')
                        ->findOrFail($vinculo->id);

                    $vinculo->update([
                        'tipo' => $tipo,
                        'observacoes' => $observacoes,
                    ]);

                    if (!$arquivo) {
                        return;
                    }

                    $anexo = $vinculo->anexo;

                    $possuiOutrosVinculos = $anexo
                        ->vinculos()
                        ->whereKeyNot($vinculo->id)
                        ->exists();

                    if ($possuiOutrosVinculos) {
                        $novoAnexo = Anexo::create([
                            'arquivo' => $novoCaminho,
                            'nome_original' => $arquivo
                                ->getClientOriginalName(),
                            'mime_type' => $arquivo
                                ->getMimeType(),
                            'tamanho' => $arquivo
                                ->getSize(),
                        ]);

                        $vinculo->update([
                            'anexo_id' => $novoAnexo->id,
                        ]);

                        return;
                    }

                    $caminhoAnterior = $anexo->arquivo;

                    $anexo->update([
                        'arquivo' => $novoCaminho,
                        'nome_original' => $arquivo
                            ->getClientOriginalName(),
                        'mime_type' => $arquivo
                            ->getMimeType(),
                        'tamanho' => $arquivo
                            ->getSize(),
                    ]);
                }
            );
        } catch (Throwable $exception) {
            if ($novoCaminho) {
                Storage::disk('private')
                    ->delete($novoCaminho);
            }

            throw $exception;
        }

        if (
            $caminhoAnterior
            && $caminhoAnterior !== $novoCaminho
        ) {
            Storage::disk('private')
                ->delete($caminhoAnterior);
        }

        return $vinculo->fresh([
            'anexo',
            'vinculavel',
        ]);
    }
}
