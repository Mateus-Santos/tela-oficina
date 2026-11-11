<?php

namespace App\Services\Forge;

use RuntimeException;

/**
 * Armazena comprovantes historicos de validacao, sem vincular ou importar dados.
 * Um relatorio nunca substitui revalidacao no momento da importacao.
 */
final class RelatoriosValidacaoForge
{
    private const FORMATO = 'forge-validacao-relatorio-v1';

    /** @param array<string, mixed>|null $validacao */
    public function registrar(string $raiz, string $pacote, ?array $validacao): array
    {
        $this->validarNome($pacote);
        $diretorio = $this->raizSegura($raiz);

        if ($validacao !== null) {
            $hash = $validacao['manifesto_sha256'] ?? null;

            if (!is_string($hash) ||
                !preg_match('/\A[a-f0-9]{64}\z/D', $hash)) {
                throw new RuntimeException('SHA-256 da validacao invalido.');
            }

            foreach (['arquivos', 'relacoes', 'alertas'] as $campo) {
                if (array_key_exists($campo, $validacao) &&
                    !is_array($validacao[$campo])) {
                    throw new RuntimeException('Estrutura da validacao invalida.');
                }
            }

            $manifesto = $diretorio.'/'.$pacote.'/manifesto.json';

            if (is_link($manifesto) ||
                !is_file($manifesto) ||
                !is_readable($manifesto)) {
                throw new RuntimeException('Manifesto ausente ou inseguro.');
            }

            $tamanho = @filesize($manifesto);
            $calculado = ($tamanho !== false && $tamanho <= 262144)
                ? @hash_file('sha256', $manifesto)
                : false;

            if (!is_string($calculado) ||
                !hash_equals($hash, $calculado)) {
                throw new RuntimeException('Manifesto divergente da validacao.');
            }
        }

        $relatorio = [
            'formato' => self::FORMATO,
            'pacote' => $pacote,
            'verificado_em' => gmdate('Y-m-d\TH:i:s\Z'),
            'status' => $validacao === null ? 'reprovado' : 'aprovado',
            'manifesto_sha256' => $validacao['manifesto_sha256'] ?? null,
            'arquivos' => $validacao['arquivos'] ?? [],
            'relacoes' => $validacao['relacoes'] ?? [],
            'alertas' => $validacao['alertas'] ?? [],
        ];
        $json = json_encode(
            $relatorio,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        if (strlen($json) > 32768) {
            throw new RuntimeException('Relatorio excede limite de tamanho.');
        }

        $pai = $this->diretorioPrivado($diretorio.'/.validacoes');
        $destino = $this->diretorioPrivado($pai.'/'.$pacote);
        $tempo = microtime(true);
        $prefixo = gmdate('Ymd\THis', (int) $tempo).'-'.sprintf(
            '%06d', (int) (($tempo - floor($tempo)) * 1000000)
        );
        $arquivo = $destino.'/'.$prefixo.'-'.bin2hex(random_bytes(8)).'.json';
        $temporario = $destino.'/.escrevendo-'.bin2hex(random_bytes(8));

        try {
            $escrito = @file_put_contents($temporario, $json, LOCK_EX);
            if ($escrito === false || $escrito !== strlen($json) || !@chmod($temporario, 0600)) {
                throw new RuntimeException('Falha ao escrever relatorio privado.');
            }
            if (!@rename($temporario, $arquivo)) {
                throw new RuntimeException('Falha ao publicar relatorio atomico.');
            }
        } finally {
            if (is_file($temporario) || is_link($temporario)) {
                @unlink($temporario);
            }
        }
        return $relatorio;
    }

    /** @return array<string, mixed>|null */
    public function ultimo(string $raiz, string $pacote): ?array
    {
        if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $pacote) ||
            is_link($raiz) || !is_dir($raiz)) {
            return null;
        }
        $real = realpath($raiz);
        if ($real === false) {
            return null;
        }
        $pai = $real.'/.validacoes';
        $diretorio = $pai.'/'.$pacote;
        if (is_link($pai) || is_link($diretorio) || !is_dir($diretorio)) {
            return null;
        }
        $entradas = @scandir($diretorio, SCANDIR_SORT_DESCENDING);
        if ($entradas === false) {
            return null;
        }
        foreach ($entradas as $nome) {
            if (!preg_match('/\A\d{8}T\d{6}-\d{6}-[a-f0-9]{16}\.json\z/D', $nome)) {
                continue;
            }
            $arquivo = $diretorio.'/'.$nome;
            if (is_link($arquivo) || !is_file($arquivo) || !is_readable($arquivo)) {
                return null;
            }
            $tamanho = @filesize($arquivo);
            if ($tamanho === false || $tamanho > 32768) {
                return null;
            }
            try {
                $dados = json_decode((string) file_get_contents($arquivo), true, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
            if (!is_array($dados) ||
                ($dados['formato'] ?? null) !== self::FORMATO ||
                ($dados['pacote'] ?? null) !== $pacote ||
                !in_array($dados['status'] ?? null, ['aprovado', 'reprovado'], true) ||
                !is_array($dados['arquivos'] ?? null) ||
                !is_array($dados['relacoes'] ?? null) ||
                !is_array($dados['alertas'] ?? null) ||
                !is_string($dados['verificado_em'] ?? null) ||
                !preg_match('/\A\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ\z/D', $dados['verificado_em'])) {
                return null;
            }
            $manifesto = $real.'/'.$pacote.'/manifesto.json';
            $hash = $dados['manifesto_sha256'] ?? null;
            $dados['manifesto_alterado'] = !is_string($hash) ||
                !preg_match('/\A[a-f0-9]{64}\z/D', $hash) ||
                is_link($manifesto) || !is_file($manifesto) ||
                @filesize($manifesto) > 262144 ||
                !hash_equals($hash, (string) @hash_file('sha256', $manifesto));
            return $dados;
        }
        return null;
    }

    private function validarNome(string $pacote): void
    {
        if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $pacote)) {
            throw new RuntimeException('Nome de pacote invalido.');
        }
    }

    private function raizSegura(string $raiz): string
    {
        if (is_link($raiz) || !is_dir($raiz)) {
            throw new RuntimeException('Staging privado indisponivel.');
        }
        $real = realpath($raiz);
        $permissoes = fileperms($raiz);
        if ($real === false || $permissoes === false || ($permissoes & 0077) !== 0) {
            throw new RuntimeException('Staging deve possuir permissoes privadas.');
        }
        return $real;
    }

    private function diretorioPrivado(string $caminho): string
    {
        if (is_link($caminho)) {
            throw new RuntimeException('Diretorio simbolico de relatorios proibido.');
        }
        if (!is_dir($caminho) && !@mkdir($caminho, 0700)) {
            throw new RuntimeException('Nao foi possivel criar pasta de relatorios.');
        }
        $permissoes = fileperms($caminho);
        if ($permissoes === false || ($permissoes & 0077) !== 0) {
            throw new RuntimeException('Pasta de relatorios nao e privada.');
        }
        return $caminho;
    }
}
