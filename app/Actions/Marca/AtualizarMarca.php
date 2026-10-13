<?php

namespace App\Actions\Marca;

use App\Models\Marca;
use App\Services\ImagemService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AtualizarMarca
{
    public function __construct(
        private readonly ImagemService $imagemService
    ) {}

    public function execute(
        Marca $marca,
        array $dados
    ): Marca {
        $logoAnterior =
            $marca->logo_path;

        $novoLogo = null;

        try {
            if (
                isset($dados['logo'])
                && $dados['logo']
                    instanceof UploadedFile
            ) {
                $novoLogo =
                    $this->imagemService
                        ->salvarMarca(
                            $dados['logo']
                        );
            }

            $logoPath =
                $marca->logo_path;

            if (
                ! empty(
                    $dados['remover_logo']
                )
            ) {
                $logoPath = null;
            }

            if ($novoLogo) {
                $logoPath =
                    $novoLogo;
            }

            $marca->update([
                'nome' => $dados['nome'],

                'logo_path' => $logoPath,

                'logo_url' => $dados['logo_url']
                    ?? null,

                'ativo' => $dados['ativo']
                    ?? false,
            ]);
        } catch (\Throwable $e) {
            if ($novoLogo) {
                Storage::disk('public')
                    ->delete($novoLogo);
            }

            throw $e;
        }

        if (
            $logoAnterior
            && $logoAnterior
                !== $marca->logo_path
            && Storage::disk('public')
                ->exists($logoAnterior)
        ) {
            Storage::disk('public')
                ->delete(
                    $logoAnterior
                );
        }

        return $marca->fresh();
    }
}
