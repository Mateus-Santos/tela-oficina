<?php
/**
 * Testa codecs com imagens geradas em memoria, sem acessar Laravel, banco ou uploads reais.
 * Este gate impede publicar imagens PHP incapazes de converter fotos para WebP.
 */
declare(strict_types=1);

try {
    if (!extension_loaded('imagick') || !class_exists(Imagick::class)) {
        throw new RuntimeException('Extensao Imagick ausente');
    }
    if (!extension_loaded('gd') || !function_exists('imagewebp')) {
        throw new RuntimeException('GD sem codificador WebP');
    }
    foreach (['PNG', 'JPEG', 'WEBP'] as $formato) {
        if (!in_array($formato, Imagick::queryFormats($formato), true)) {
            throw new RuntimeException("ImageMagick sem suporte a {$formato}");
        }
    }

    $gd = imagecreatetruecolor(5, 5);
    if ($gd === false) {
        throw new RuntimeException('GD nao gerou imagem de teste');
    }

    foreach ([
        'PNG' => fn () => imagepng($gd),
        'JPEG' => fn () => imagejpeg($gd, null, 85),
        'WEBP' => fn () => imagewebp($gd, null, 85),
    ] as $formato => $gerar) {
        ob_start();
        $ok = $gerar();
        $entrada = ob_get_clean();
        if (!$ok || !is_string($entrada) || $entrada === '') {
            throw new RuntimeException("Falha ao gerar {$formato} de teste");
        }

        $imagem = new Imagick();
        $imagem->readImageBlob($entrada);
        $imagem->setImageFormat('webp');
        $imagem->setImageCompressionQuality(82);
        $webp = $imagem->getImageBlob();
        $imagem->clear();
        $imagem->destroy();

        $verificacao = new Imagick();
        $verificacao->readImageBlob($webp);
        $valido = $verificacao->getImageFormat() === 'WEBP'
            && $verificacao->getImageWidth() === 5
            && $verificacao->getImageHeight() === 5;
        $verificacao->clear();
        $verificacao->destroy();
        if (!$valido) {
            throw new RuntimeException("Falha de conversao {$formato} -> WebP");
        }
    }

    echo "OK: conversao PNG/JPEG/WEBP -> WebP operacional\n";
} catch (Throwable $e) {
    fwrite(STDERR, "FALHA: pipeline de imagens: {$e->getMessage()}\n");
    exit(1);
}
