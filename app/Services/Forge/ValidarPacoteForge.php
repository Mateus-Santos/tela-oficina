<?php

namespace App\Services\Forge;

use RuntimeException;

/**
 * Validador sem efeitos colaterais. Não escreve em banco ou no pacote.
 * A verificação não reserva os arquivos: revalidar um snapshot privado antes da carga.
 */
final class ValidarPacoteForge
{
    private const FORMATO = 'forge-laravel-csv-v1';

    private const CAMPOS = [
        'montadoras.csv' => ['id', 'sky_id', 'nome'],
        'veiculos.csv' => ['id', 'sky_id', 'montadora_id', 'nome'],
        'produtos.csv' => ['sky_id', 'codigo_fabricante', 'descricao', 'marca'],
        'produto_estado_sky.csv' => ['produto_sky_id'],
        'produto_precos_historico.csv' => ['produto_sky_id'],
        'produto_departamentos.csv' => ['produto_sky_id', 'departamento_sky_id'],
        'produto_tipos.csv' => ['produto_sky_id', 'tipo_produto_sky_id'],
        'produto_aplicacoes.csv' => [
            'produto_sky_id', 'montadora', 'veiculo', 'mes_ano',
            'modelo', 'motor', 'comp', 'valvulas', 'cilindros',
            'combustivel', 'complemento',
        ],
        'produto_atributos.csv' => ['produto_sky_id', 'chave'],
        'produto_imagens.csv' => ['produto_sky_id'],
        'produto_similares.csv' => ['produto_sky_id', 'similar_sky_id'],
    ];

    private const EXTRAS = [
        'extras/produto_veiculos_descoberta.csv',
        'extras/produto_selos.csv',
        'extras/buscas_produtos.csv',
        'extras/buscas_produtos_globais.csv',
        'extras/produto_origens_globais.csv',
    ];

    /**
     * @return array{pacote:string,formato:string,manifesto_sha256:string,arquivos:array<string,array{linhas:int,bytes:int,sha256:string}>,relacoes:array<string,int>,alertas:list<string>}
     */
    public function validar(string $raizPrivada, string $nomePacote): array
    {
        if (!preg_match('/\Apronto-\d{8}T\d{12}Z\z/D', $nomePacote)) {
            throw new RuntimeException('Nome de pacote invalido.');
        }

        if (is_link($raizPrivada) || !is_dir($raizPrivada)) {
            throw new RuntimeException('Raiz de pacotes inexistente ou insegura.');
        }

        $raiz = realpath($raizPrivada);
        if ($raiz === false) {
            throw new RuntimeException('Raiz de pacotes inacessivel.');
        }

        $diretorio = $raiz.DIRECTORY_SEPARATOR.$nomePacote;
        if (is_link($diretorio) || !is_dir($diretorio)) {
            throw new RuntimeException('Pacote inexistente ou inseguro.');
        }

        $real = realpath($diretorio);
        if ($real === false || dirname($real) !== $raiz) {
            throw new RuntimeException('Pacote fora da raiz aprovada.');
        }

        $manifesto = $diretorio.'/manifesto.json';
        $this->verificarArquivo($manifesto);
        $tamanhoManifesto = filesize($manifesto);
        if ($tamanhoManifesto === false || $tamanhoManifesto > 262144) {
            throw new RuntimeException('Manifesto excede o tamanho permitido.');
        }

        try {
            $dados = json_decode(
                (string) file_get_contents($manifesto),
                true,
                64,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            throw new RuntimeException('Manifesto JSON invalido.', 0, $e);
        }

        if (!is_array($dados) || ($dados['formato'] ?? null) !== self::FORMATO) {
            throw new RuntimeException('Formato do pacote nao reconhecido.');
        }
        if (!isset($dados['arquivos']) || !is_array($dados['arquivos']) ||
            !array_is_list($dados['arquivos'])) {
            throw new RuntimeException('Inventario de arquivos invalido.');
        }

        $conhecidos = array_fill_keys(array_merge(array_keys(self::CAMPOS), self::EXTRAS), true);
        $declarados = [];
        $resultado = [];

        foreach ($dados['arquivos'] as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Entrada do manifesto invalida.');
            }

            $nome = $item['arquivo'] ?? null;
            $bytes = $item['bytes'] ?? null;
            $sha256 = $item['sha256'] ?? null;
            $consumido = $item['consumido_pelo_importador_atual'] ?? null;

            if (!is_string($nome) || !isset($conhecidos[$nome]) || isset($declarados[$nome])) {
                throw new RuntimeException('Arquivo desconhecido ou repetido no manifesto.');
            }
            if (!is_int($bytes) || $bytes < 0 ||
                !is_string($sha256) || !preg_match('/\A[a-fA-F0-9]{64}\z/D', $sha256) ||
                !is_bool($consumido) || $consumido !== isset(self::CAMPOS[$nome])) {
                throw new RuntimeException("Metadados invalidos do arquivo: {$nome}");
            }
            $declarados[$nome] = true;
        }

        foreach (self::CAMPOS as $nome => $_) {
            if (!isset($declarados[$nome])) {
                throw new RuntimeException("CSV obrigatorio ausente: {$nome}");
            }
        }

        $this->conferirInventarioFisico($diretorio, $declarados);

        foreach ($dados['arquivos'] as $item) {
            $nome = $item['arquivo'];
            $caminho = $diretorio.'/'.$nome;
            $this->verificarArquivo($caminho);
            $tamanho = filesize($caminho);
            if ($tamanho === false || $tamanho !== $item['bytes']) {
                throw new RuntimeException("Tamanho divergente: {$nome}");
            }
            $hash = hash_file('sha256', $caminho);
            if ($hash === false || !hash_equals(strtolower($item['sha256']), $hash)) {
                throw new RuntimeException("SHA-256 divergente: {$nome}");
            }

            $linhas = $this->percorrerCsv(
                $caminho,
                self::CAMPOS[$nome] ?? [],
                static function (array $linha): void {}
            );

            $resultado[$nome] = ['linhas' => $linhas, 'bytes' => $tamanho, 'sha256' => $hash];
        }

        $relacoes = $this->verificarRelacoes($diretorio);
        $alertas = [];
        if ($relacoes['veiculos_sem_sky_id'] > 0) {
            $alertas[] = "Veiculos sem sky_id (nao importados): {$relacoes['veiculos_sem_sky_id']}";
        }
        if ($relacoes['similares_pendentes'] > 0) {
            $alertas[] = "Similar(es) com destino ausente ou igual a origem: {$relacoes['similares_pendentes']}";
        }
        $alertas[] = 'Manifesto nao assinado: conferir procedencia da copia.';
        $alertas[] = 'A verificacao nao congela os CSVs: criar staging imutavel e revalidar antes de importar.';

        return [
            'pacote' => $nomePacote,
            'formato' => self::FORMATO,
            'manifesto_sha256' => (string) hash_file('sha256', $manifesto),
            'arquivos' => $resultado,
            'relacoes' => $relacoes,
            'alertas' => $alertas,
        ];
    }

    /** @param array<string,bool> $declarados */
    private function conferirInventarioFisico(string $pasta, array $declarados): void
    {
        $esperados = array_fill_keys(array_merge(['manifesto.json', 'LEIA-ME.txt'], array_keys($declarados)), true);
        $extras = $pasta.'/extras';
        if (is_link($extras)) {
            throw new RuntimeException('Diretorio de extras simbolico proibido.');
        }
        if (is_dir($extras)) {
            foreach (scandir($extras) ?: [] as $nome) {
                if ($nome === '.' || $nome === '..') {
                    continue;
                }
                if (!isset($esperados['extras/'.$nome])) {
                    throw new RuntimeException('Arquivo extra nao declarado ou desconhecido.');
                }
                $this->verificarArquivo($extras.'/'.$nome);
            }
        }

        foreach (scandir($pasta) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            if ($nome === 'extras' && is_dir($extras)) {
                continue;
            }
            if (!isset($esperados[$nome])) {
                throw new RuntimeException('Entrada desconhecida no pacote.');
            }
            $this->verificarArquivo($pasta.'/'.$nome);
        }
    }

    private function verificarArquivo(string $caminho): void
    {
        if (is_link($caminho) || !is_file($caminho) || !is_readable($caminho)) {
            throw new RuntimeException('Arquivo ausente, ilegivel ou simbolico: '.basename($caminho));
        }
    }

    /**
     * @param list<string> $minimos
     * @param callable(array<string,string>):void $receber
     */
    private function percorrerCsv(string $caminho, array $minimos, callable $receber): int
    {
        $arquivo = fopen($caminho, 'rb');
        if ($arquivo === false) {
            throw new RuntimeException('Falha de abertura do CSV.');
        }

        try {
            $cabecalho = fgetcsv($arquivo, 0, ';', '"', '');
            if (!is_array($cabecalho)) {
                throw new RuntimeException('CSV sem cabecalho: '.basename($caminho));
            }
            $cabecalho[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho[0]);
            if (preg_match('//u', implode(';', $cabecalho)) !== 1) {
                throw new RuntimeException('Cabecalho fora de UTF-8: '.basename($caminho));
            }
            if (count($cabecalho) !== count(array_unique($cabecalho)) ||
                in_array('', $cabecalho, true)) {
                throw new RuntimeException('Cabecalho duplicado/vazio: '.basename($caminho));
            }
            foreach ($minimos as $campo) {
                if (!in_array($campo, $cabecalho, true)) {
                    throw new RuntimeException("Coluna ausente em ".basename($caminho).": {$campo}");
                }
            }

            $total = 0;
            while (($valores = fgetcsv($arquivo, 0, ';', '"', '')) !== false) {
                if ($valores === [null]) {
                    continue;
                }
                if (count($valores) !== count($cabecalho)) {
                    throw new RuntimeException('Colunas inconsistentes em '.basename($caminho));
                }
                if (preg_match('//u', implode(';', array_map('strval', $valores))) !== 1) {
                    throw new RuntimeException('Registro fora de UTF-8: '.basename($caminho));
                }
                $linha = array_combine($cabecalho, $valores);
                if ($linha === false) {
                    throw new RuntimeException('CSV malformado: '.basename($caminho));
                }
                $receber($linha);
                $total++;
            }
            return $total;
        } finally {
            fclose($arquivo);
        }
    }

    /** @return array{produtos_unicos:int,montadoras_unicas:int,veiculos_sem_sky_id:int,similares_pendentes:int,ids_veiculo_repetidos_entre_montadoras:int} */
    private function verificarRelacoes(string $pasta): array
    {
        $produtos = [];
        $montadoras = [];
        $montadorasSkyId = [];
        $veiculos = [];
        $porSkyId = [];
        $semSkyId = 0;
        $repetidosEntreMontadoras = 0;

        $this->percorrerCsv($pasta.'/produtos.csv', self::CAMPOS['produtos.csv'],
            static function (array $linha) use (&$produtos): void {
                $id = trim((string) $linha['sky_id']);
                if ($id === '' || isset($produtos[$id])) {
                    throw new RuntimeException('Produto com sky_id vazio ou duplicado.');
                }
                $produtos[$id] = true;
            }
        );

        $this->percorrerCsv($pasta.'/montadoras.csv', self::CAMPOS['montadoras.csv'],
            static function (array $linha) use (&$montadoras, &$montadorasSkyId): void {
                $id = trim((string) $linha['id']);
                $skyId = trim((string) $linha['sky_id']);
                if ($id === '' || $skyId === '' || isset($montadoras[$id]) || isset($montadorasSkyId[$skyId])) {
                    throw new RuntimeException('Montadora com id ou sky_id vazio/duplicado.');
                }
                $montadoras[$id] = true;
                $montadorasSkyId[$skyId] = true;
            }
        );

        $this->percorrerCsv($pasta.'/veiculos.csv', self::CAMPOS['veiculos.csv'],
            static function (array $linha) use ($montadoras, &$veiculos, &$porSkyId, &$semSkyId, &$repetidosEntreMontadoras): void {
                $montadora = trim((string) $linha['montadora_id']);
                $id = trim((string) $linha['sky_id']);
                if (!isset($montadoras[$montadora])) {
                    throw new RuntimeException('Veiculo referencia montadora ausente.');
                }
                if ($id === '') {
                    $semSkyId++;
                    return;
                }
                $chave = $montadora."\0".$id;
                if (isset($veiculos[$chave])) {
                    throw new RuntimeException('Veiculo duplicado na identidade composta.');
                }
                if (isset($porSkyId[$id]) && $porSkyId[$id] !== $montadora) {
                    $repetidosEntreMontadoras++;
                }
                $porSkyId[$id] = $montadora;
                $veiculos[$chave] = true;
            }
        );

        $this->percorrerCsv($pasta.'/produto_aplicacoes.csv', self::CAMPOS['produto_aplicacoes.csv'],
            static function (array $linha) use ($produtos): void {
                if (!isset($produtos[trim((string) $linha['produto_sky_id'])])) {
                    throw new RuntimeException('Aplicacao referencia produto ausente.');
                }
            }
        );

        $similaresPendentes = 0;
        $this->percorrerCsv($pasta.'/produto_similares.csv', self::CAMPOS['produto_similares.csv'],
            static function (array $linha) use ($produtos, &$similaresPendentes): void {
                $origem = trim((string) $linha['produto_sky_id']);
                $destino = trim((string) $linha['similar_sky_id']);
                if ($origem === $destino || !isset($produtos[$origem]) || !isset($produtos[$destino])) {
                    $similaresPendentes++;
                }
            }
        );

        return [
            'produtos_unicos' => count($produtos),
            'montadoras_unicas' => count($montadoras),
            'veiculos_sem_sky_id' => $semSkyId,
            'similares_pendentes' => $similaresPendentes,
            'ids_veiculo_repetidos_entre_montadoras' => $repetidosEntreMontadoras,
        ];
    }
}
