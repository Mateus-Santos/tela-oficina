<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use RuntimeException;
use Throwable;

class ImagemService
{
    public function salvarProduto(
        UploadedFile $arquivo
    ): string {
        return $this->processar(
            arquivo: $arquivo,
            diretorio: 'produtos',
            larguraMaxima: 1200,
            alturaMaxima: 1200,
            qualidade: 82
        );
    }

    public function salvarMarca(
        UploadedFile $arquivo
    ): string {
        return $this->processar(
            arquivo: $arquivo,
            diretorio: 'marcas',
            larguraMaxima: 800,
            alturaMaxima: 800,
            qualidade: 88
        );
    }

    private function processar(
        UploadedFile $arquivo,
        string $diretorio,
        int $larguraMaxima,
        int $alturaMaxima,
        int $qualidade
    ): string {
        $caminhoTemporario =
            $arquivo->getRealPath();

        if (
            ! $caminhoTemporario
            || ! is_file($caminhoTemporario)
        ) {
            throw new RuntimeException(
                'Não foi possível acessar a imagem enviada.'
            );
        }

        $nomeArquivo =
            Str::uuid()->toString().'.webp';

        $caminhoRelativo =
            $diretorio.'/'.$nomeArquivo;

        Storage::disk('public')
            ->makeDirectory($diretorio);

        $caminhoDestino =
            Storage::disk('public')
                ->path($caminhoRelativo);

        $imagem = new Imagick;

        try {
            $imagem->readImage(
                $caminhoTemporario
            );

            /*
             * Para GIFs ou formatos com múltiplos frames,
             * utilizamos somente o primeiro frame.
             */
            if ($imagem->getNumberImages() > 1) {
                $imagem->setIteratorIndex(0);
            }

            /*
             * Corrige fotos de celular que armazenam a
             * orientação apenas nos metadados EXIF.
             */
            if (
                method_exists(
                    $imagem,
                    'autoOrientImage'
                )
            ) {
                $imagem->autoOrientImage();
            }

            /*
             * Reduz proporcionalmente, sem ampliar imagens
             * que já sejam menores que o limite.
             */
            $larguraAtual =
                $imagem->getImageWidth();

            $alturaAtual =
                $imagem->getImageHeight();

            if (
                $larguraAtual > $larguraMaxima
                || $alturaAtual > $alturaMaxima
            ) {
                $imagem->thumbnailImage(
                    $larguraMaxima,
                    $alturaMaxima,
                    true,
                    true
                );
            }

            /*
             * Remove EXIF, GPS e outros metadados que não
             * são necessários para o sistema.
             */
            $imagem->stripImage();

            $imagem->setImageFormat(
                'webp'
            );

            $imagem->setImageCompressionQuality(
                $qualidade
            );

            $imagem->setOption(
                'webp:method',
                '6'
            );

            if (
                ! $imagem->writeImage(
                    $caminhoDestino
                )
            ) {
                throw new RuntimeException(
                    'Não foi possível salvar a imagem processada.'
                );
            }
        } catch (Throwable $e) {
            Storage::disk('public')
                ->delete($caminhoRelativo);

            throw new RuntimeException(
                'Não foi possível processar a imagem enviada.',
                previous: $e
            );
        } finally {
            $imagem->clear();
            $imagem->destroy();
        }

        return $caminhoRelativo;
    }
}
