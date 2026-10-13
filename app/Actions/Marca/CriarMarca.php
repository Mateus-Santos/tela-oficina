<?php

namespace App\Actions\Marca;

use App\Models\Marca;
use App\Services\ImagemService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CriarMarca
{
    public function __construct(
        private readonly ImagemService $imagemService
    ) {}

    public function execute(
        array $dados
    ): Marca {
        $logoPath = null;

        try {
            if (
                isset($dados['logo'])
                && $dados['logo']
                    instanceof UploadedFile
            ) {
                $logoPath =
                    $this->imagemService
                        ->salvarMarca(
                            $dados['logo']
                        );
            }

            return Marca::create([
                'nome' => $dados['nome'],

                'logo_path' => $logoPath,

                'logo_url' => $dados['logo_url']
                    ?? null,

                'ativo' => $dados['ativo']
                    ?? true,
            ]);
        } catch (\Throwable $e) {
            if ($logoPath) {
                Storage::disk('public')
                    ->delete($logoPath);
            }

            throw $e;
        }
    }
}
