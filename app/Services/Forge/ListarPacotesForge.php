<?php

namespace App\Services\Forge;

final class ListarPacotesForge
{
    private const LIMITE = 30;

    /**
     * Le apenas nomes de pacotes e a presenca do manifesto.
     * Nunca considera um pacote validado nem le os CSVs.
     *
     * @return array{disponivel: bool, total: int, pacotes: list<array{nome: string, manifesto_presente: bool}>}
     */
    public function listar(string $raiz): array
    {
        $vazio = [
            'disponivel' => false,
            'total' => 0,
            'pacotes' => [],
        ];

        if (is_link($raiz) || !is_dir($raiz)) {
            return $vazio;
        }

        $real = realpath($raiz);
        if ($real === false) {
            return $vazio;
        }

        $entradas = @scandir($real);
        if ($entradas === false) {
            return $vazio;
        }

        $pacotes = [];
        foreach ($entradas as $nome) {
            if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $nome)) {
                continue;
            }

            $diretorio = $real.DIRECTORY_SEPARATOR.$nome;
            if (is_link($diretorio) || !is_dir($diretorio)) {
                continue;
            }

            $caminhoReal = realpath($diretorio);
            if ($caminhoReal === false || dirname($caminhoReal) !== $real) {
                continue;
            }

            $manifesto = $diretorio.'/manifesto.json';
            $bytes = !is_link($manifesto) && is_file($manifesto)
                ? @filesize($manifesto)
                : false;

            $pacotes[] = [
                'nome' => $nome,
                'manifesto_presente' => $bytes !== false
                    && $bytes <= 262144
                    && is_readable($manifesto),
            ];
        }

        usort(
            $pacotes,
            static fn (array $a, array $b): int => strcmp($b['nome'], $a['nome'])
        );

        return [
            'disponivel' => true,
            'total' => count($pacotes),
            'pacotes' => array_slice($pacotes, 0, self::LIMITE),
        ];
    }
}
