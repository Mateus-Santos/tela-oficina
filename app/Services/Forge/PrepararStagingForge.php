<?php

namespace App\Services\Forge;

use RuntimeException;

/**
 * Publica uma copia validada de um pacote Forge em storage/app/forge-v270.
 * Sem acesso a banco. Nao altera a origem. Nao substitui pacotes existentes.
 * Permissoes somente leitura reduzem alteracoes acidentais, mas NAO equivalem
 * a imutabilidade criptografica/contra o proprio usuario Unix.
 */
final class PrepararStagingForge
{
    public function __construct(
        private readonly ValidarPacoteForge $validador = new ValidarPacoteForge()
    ) {}

    /**
     * @return array{pacote:string,manifesto_sha256:string,arquivos:int,bytes:int,destino:string,alertas:list<string>}
     */
    public function preparar(string $raizOrigem, string $nomePacote, string $raizDestino): array
    {
        if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $nomePacote)) {
            throw new RuntimeException('Nome de pacote Forge invalido.');
        }

        if (is_link($raizOrigem) || !is_dir($raizOrigem)) {
            throw new RuntimeException('Raiz de origem inexistente ou simbolica.');
        }

        // Validacao completa ANTES de criar qualquer diretorio de staging.
        $origemValidada = $this->validador->validar($raizOrigem, $nomePacote);
        $origem = realpath($raizOrigem);
        if ($origem === false) {
            throw new RuntimeException('Nao foi possivel resolver a origem.');
        }
        $origemPacote = $origem.'/'.$nomePacote;

        if (is_link($raizDestino)) {
            throw new RuntimeException('Raiz de staging simbolica proibida.');
        }
        $pai = dirname($raizDestino);
        if (is_link($pai) || !is_dir($pai)) {
            throw new RuntimeException('Diretorio pai do staging ausente/inseguro.');
        }
        $paiReal = realpath($pai);
        if ($paiReal === false) {
            throw new RuntimeException('Nao foi possivel resolver o pai do staging.');
        }
        $nomeRaizDestino = basename($raizDestino);
        if ($nomeRaizDestino !== 'forge-v270') {
            throw new RuntimeException('Raiz de staging deve se chamar forge-v270.');
        }
        $raizDestino = $paiReal.'/'.$nomeRaizDestino;
        if ($raizDestino === $origem || str_starts_with($raizDestino.'/', $origem.'/')) {
            throw new RuntimeException('Destino nao pode estar dentro da origem.');
        }

        $umaskOriginal = umask(0077);
        $trava = null;
        $temporario = null;
        $definitivo = null;
        $publicado = false;
        $concluido = false;

        try {
            if (!is_dir($raizDestino)) {
                if (!@mkdir($raizDestino, 0700) && !is_dir($raizDestino)) {
                    throw new RuntimeException('Falha ao criar staging privado.');
                }
            }
            if (is_link($raizDestino) || !is_dir($raizDestino)) {
                throw new RuntimeException('Raiz de staging invalida.');
            }
            $permissoes = fileperms($raizDestino);
            if ($permissoes === false || (($permissoes & 0077) !== 0)) {
                throw new RuntimeException('Raiz de staging deve ser privada (0700).');
            }

            // flock no mesmo filesystem, com lock mantido ate publicacao.
            $caminhoTrava = $raizDestino.'/.preparar.lock';
            if (is_link($caminhoTrava)) {
                throw new RuntimeException('Arquivo de lock simbolico proibido.');
            }
            $trava = @fopen($caminhoTrava, 'c');
            if ($trava === false || !flock($trava, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Outro processo prepara staging no momento.');
            }
            @chmod($caminhoTrava, 0600);

            $definitivo = $raizDestino.'/'.$nomePacote;
            if (file_exists($definitivo) || is_link($definitivo)) {
                throw new RuntimeException('Pacote ja existe no staging; nao sobrescrever.');
            }

            $temporario = $raizDestino.'/.recebendo-'.$nomePacote.'-'.bin2hex(random_bytes(8));
            if (!@mkdir($temporario, 0700)) {
                throw new RuntimeException('Falha ao reservar staging temporario.');
            }

            $arquivos = array_keys($origemValidada['arquivos']);
            $arquivos[] = 'manifesto.json';
            if (is_file($origemPacote.'/LEIA-ME.txt') && !is_link($origemPacote.'/LEIA-ME.txt')) {
                $arquivos[] = 'LEIA-ME.txt';
            }

            $recebido = $temporario.'/'.$nomePacote;
            if (!@mkdir($recebido, 0700)) {
                throw new RuntimeException('Falha ao criar pacote temporario.');
            }

            if (array_any($arquivos, static fn (string $nome): bool => str_starts_with($nome, 'extras/'))) {
                if (!@mkdir($recebido.'/extras', 0700)) {
                    throw new RuntimeException('Falha ao criar diretorio extras.');
                }
            }

            foreach ($arquivos as $arquivo) {
                $this->copiarArquivo($origemPacote.'/'.$arquivo, $recebido.'/'.$arquivo);
            }

            // Validar no destino, nunca confiar somente na origem/manifesto.
            $copiaValidada = $this->validador->validar($temporario, $nomePacote);
            if (!hash_equals($origemValidada['manifesto_sha256'], $copiaValidada['manifesto_sha256']) ||
                $origemValidada['arquivos'] !== $copiaValidada['arquivos'] ||
                $origemValidada['relacoes'] !== $copiaValidada['relacoes']) {
                throw new RuntimeException('Staging divergiu do pacote de origem validado.');
            }

            // Protecao contra escrita acidental, com permissao de leitura ao proprietario.
            foreach ($arquivos as $arquivo) {
                if (!@chmod($recebido.'/'.$arquivo, 0400)) {
                    throw new RuntimeException('Falha ao proteger arquivo recebido.');
                }
            }
            if (is_dir($recebido.'/extras') && !@chmod($recebido.'/extras', 0500)) {
                throw new RuntimeException('Falha ao proteger diretorio extras.');
            }
            // Um rename() de diretorio sem permissao de escrita (0500) pode
            // retornar EACCES para usuario nao privilegiado. Manter 0700
            // ate publicar; os arquivos e extras ja estao protegidos.
            if (file_exists($definitivo) || is_link($definitivo)) {
                throw new RuntimeException('Pacote apareceu no destino durante a copia.');
            }
            if (!@rename($recebido, $definitivo)) {
                $erro = error_get_last();
                $detalhe = is_array($erro) ? (string) ($erro['message'] ?? '') : '';
                throw new RuntimeException(
                    'Falha na publicacao atomica do staging.'
                    .($detalhe !== '' ? ' '.$detalhe : '')
                );
            }
            $publicado = true;
            if (!@chmod($definitivo, 0500)) {
                throw new RuntimeException('Falha ao proteger diretorio publicado.');
            }
            if (!@rmdir($temporario)) {
                $this->limparTemporario($temporario);
            }
            $temporario = null;
            $concluido = true;

            return [
                'pacote' => $nomePacote,
                'manifesto_sha256' => $copiaValidada['manifesto_sha256'],
                'arquivos' => count($copiaValidada['arquivos']),
                'bytes' => array_sum(array_column($copiaValidada['arquivos'], 'bytes')),
                'destino' => $definitivo,
                'alertas' => [
                    'A pasta e somente leitura, mas o proprietario Unix ainda pode alterar permissoes.',
                    'Revalidar o staging imediatamente antes de qualquer importacao futura.',
                    'Nao comprova autenticidade criptografica do emissor Forge.',
                ],
            ];
        } finally {
            // Se falhar a protecao final, remover apenas a copia publicada
            // por esta execucao. Nao tocar em pacotes preexistentes.
            if (!$concluido && $publicado && $definitivo !== null &&
                is_dir($definitivo) && !is_link($definitivo)) {
                $this->limparTemporario($definitivo);
            }
            if ($temporario !== null && is_dir($temporario) && !is_link($temporario)) {
                $this->limparTemporario($temporario);
            }
            if (is_resource($trava)) {
                flock($trava, LOCK_UN);
                fclose($trava);
            }
            umask($umaskOriginal);
        }
    }

    private function copiarArquivo(string $origem, string $destino): void
    {
        if (is_link($origem) || !is_file($origem) || !is_readable($origem)) {
            throw new RuntimeException('Arquivo de origem ausente/inseguro: '.basename($origem));
        }

        $entrada = @fopen($origem, 'rb');
        $saida = @fopen($destino, 'xb');
        if ($entrada === false || $saida === false) {
            if (is_resource($entrada)) {
                fclose($entrada);
            }
            if (is_resource($saida)) {
                fclose($saida);
            }
            throw new RuntimeException('Falha ao abrir arquivo para copia: '.basename($origem));
        }
        try {
            $esperados = filesize($origem);
            $copiados = stream_copy_to_stream($entrada, $saida);
            if ($esperados === false || $copiados === false || $copiados !== $esperados || !fflush($saida)) {
                throw new RuntimeException('Copia incompleta: '.basename($origem));
            }
        } finally {
            fclose($entrada);
            fclose($saida);
        }
    }

    private function limparTemporario(string $dir): void
    {
        @chmod($dir, 0700);
        foreach (scandir($dir) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            $entrada = $dir.'/'.$nome;
            if (is_dir($entrada) && !is_link($entrada)) {
                $this->limparTemporario($entrada);
            } else {
                @unlink($entrada);
            }
        }
        @rmdir($dir);
    }
}
