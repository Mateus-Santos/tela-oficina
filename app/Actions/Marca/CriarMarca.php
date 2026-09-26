<?php

namespace App\Actions\Marca;

use App\Models\Marca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CriarMarca
{
    public function execute(array $dados): Marca
    {
        $logoPath = null;

        if (
            isset($dados['logo'])
            && $dados['logo'] instanceof UploadedFile
        ) {
            $logoPath = $dados['logo']->store('marcas', 'public');
        }

        try {
            return Marca::create([
                'nome' => $dados['nome'],
                'logo_path' => $logoPath,
                'logo_url' => $dados['logo_url'] ?? null,
                'ativo' => $dados['ativo'] ?? true,
            ]);
        } catch (\Throwable $e) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $e;
        }
    }
}
