<?php

namespace App\Services\Produto;

class ValidadorAplicacoes
{
    private const QUANTIDADE_COLUNAS = 10;

    public function validar(string $texto): array
    {
        if (trim($texto) === '') {
            return $this->erro(
                'Nenhum dado foi encontrado na área de transferência.'
            );
        }

        $linhas = $this->normalizarLinhas($texto);

        if (count($linhas) < 2) {
            return $this->erro(
                'A tabela precisa conter um cabeçalho e pelo menos uma aplicação.'
            );
        }

        $cabecalho = $this->separarColunas($linhas[0]);

        if (!$this->cabecalhoValido($cabecalho)) {
            return $this->erro(
                'O conteúdo copiado não possui o formato esperado de tabela de aplicações.'
            );
        }

        $linhasValidas = 0;

        foreach (array_slice($linhas, 1) as $indice => $linha) {
            $numeroLinha = $indice + 2;
            $colunas = $this->separarColunas($linha);

            if (count($colunas) !== self::QUANTIDADE_COLUNAS) {
                return $this->erro(
                    "A linha {$numeroLinha} possui "
                    . count($colunas)
                    . ' colunas. Eram esperadas '
                    . self::QUANTIDADE_COLUNAS
                    . '.'
                );
            }

            $montadora = $colunas[0];
            $veiculo = $colunas[1];

            if ($montadora === '' || $veiculo === '') {
                return $this->erro(
                    "A linha {$numeroLinha} possui montadora ou veículo vazio."
                );
            }

            if (
                !$this->textoEstruturalmenteValido($montadora)
                || !$this->textoEstruturalmenteValido($veiculo)
            ) {
                return $this->erro(
                    "A linha {$numeroLinha} possui uma montadora ou veículo inválido."
                );
            }

            $linhasValidas++;
        }

        if ($linhasValidas === 0) {
            return $this->erro(
                'Nenhuma aplicação válida foi encontrada.'
            );
        }

        return [
            'valido' => true,
            'mensagem' => 'Tabela de aplicações válida.',
            'linhas' => $linhasValidas,
        ];
    }

    private function normalizarLinhas(string $texto): array
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $linhas = explode("\n", $texto);

        /*
         * Preserva TABs existentes no começo/final da linha.
         *
         * Eles representam células vazias da tabela copiada.
         */
        return array_values(
            array_filter(
                $linhas,
                fn (string $linha) => trim($linha) !== ''
            )
        );
    }

    private function separarColunas(string $linha): array
    {
        return array_map(
            fn (string $coluna) => $this->normalizarTexto($coluna),
            explode("\t", $linha)
        );
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? $texto;

        return trim(
            preg_replace('/\s+/', ' ', $texto) ?? ''
        );
    }

    private function textoEstruturalmenteValido(string $texto): bool
    {
        if ($texto === '') {
            return false;
        }

        if (mb_strlen($texto) > 150) {
            return false;
        }

        if (preg_match('/^[\W_]+$/u', $texto)) {
            return false;
        }

        if (preg_match('/^[.,;:|\/\\\\]+/u', $texto)) {
            return false;
        }

        return true;
    }

    private function cabecalhoValido(array $cabecalho): bool
    {
        if (count($cabecalho) !== self::QUANTIDADE_COLUNAS) {
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

    private function erro(string $mensagem): array
    {
        return [
            'valido' => false,
            'mensagem' => $mensagem,
            'linhas' => 0,
        ];
    }
}
