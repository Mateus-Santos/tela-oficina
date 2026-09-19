<?php

namespace App\Services\Produto;

class ParserAplicacoes
{
    private const COLUNAS_ESPERADAS = [
        'montadora',
        'veiculo',
        'mes_ano',
        'modelo',
        'motor',
        'comp',
        'valvulas',
        'cilindrada',
        'combustivel',
        'complemento',
    ];

    public function processar(string $texto): array
    {
        $linhas = $this->normalizarLinhas($texto);

        if (count($linhas) < 2) {
            return [];
        }

        $cabecalho = $this->separarColunas($linhas[0]);

        if (!$this->cabecalhoValido($cabecalho)) {
            return [];
        }

        $aplicacoes = [];

        foreach (array_slice($linhas, 1) as $linha) {
            $colunas = $this->separarColunas($linha);

            if (count($colunas) !== count(self::COLUNAS_ESPERADAS)) {
                continue;
            }

            $aplicacao = $this->montarAplicacao($colunas);

            if (
                $aplicacao['montadora'] === ''
                || $aplicacao['veiculo'] === ''
            ) {
                continue;
            }

            $aplicacoes[] = $aplicacao;
        }

        return $aplicacoes;
    }

    private function normalizarLinhas(string $texto): array
    {
        $texto = str_replace(
            ["\r\n", "\r"],
            "\n",
            $texto
        );

        $linhas = explode("\n", $texto);

        return array_values(
            array_filter(
                array_map(
                    fn (string $linha) => trim($linha),
                    $linhas
                ),
                fn (string $linha) => $linha !== ''
            )
        );
    }

    private function separarColunas(string $linha): array
    {
        return array_map(
            fn (string $coluna) => trim($coluna),
            explode("\t", $linha)
        );
    }

    private function montarAplicacao(array $colunas): array
    {
        $aplicacao = [];

        foreach (self::COLUNAS_ESPERADAS as $indice => $campo) {
            $aplicacao[$campo] = $this->normalizarTexto(
                $colunas[$indice] ?? ''
            );
        }

        return $aplicacao;
    }

    private function normalizarTexto(string $texto): string
    {
        return trim(
            preg_replace('/\s+/', ' ', $texto) ?? ''
        );
    }

    private function cabecalhoValido(array $cabecalho): bool
    {
        if (count($cabecalho) !== count(self::COLUNAS_ESPERADAS)) {
            return false;
        }

        $cabecalhoNormalizado = array_map(
            fn (string $coluna) => mb_strtolower(
                $this->normalizarTexto($coluna)
            ),
            $cabecalho
        );

        $cabecalhoEsperado = [
            'montadora',
            'veículo',
            'mês/ano',
            'modelo',
            'motor',
            'comp',
            'vál.',
            'cil.',
            'combustível',
            'complemento',
        ];

        return $cabecalhoNormalizado === $cabecalhoEsperado;
    }
}
