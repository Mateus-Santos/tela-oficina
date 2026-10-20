<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoDepartamento;
use App\Models\CatalogoFonte;
use App\Models\CatalogoMontadora;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\CatalogoProdutoAtributo;
use App\Models\CatalogoProdutoEstado;
use App\Models\CatalogoProdutoImagem;
use App\Models\CatalogoTipoProduto;
use App\Models\CatalogoVeiculo;
use App\Models\Montadora;
use App\Models\Veiculo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class SkyCsvImporter
{
    public function __construct(
        private readonly VincularProdutoCatalogo $vinculador
    ) {}

    public function importar(string $diretorio): array
    {
        $diretorio = rtrim($diretorio, DIRECTORY_SEPARATOR);

        if (!is_dir($diretorio)) {
            throw new RuntimeException("Diretório de exportação não encontrado: {$diretorio}");
        }

        $fonte = CatalogoFonte::query()->updateOrCreate(
            ['codigo' => 'sky'],
            [
                'nome' => 'Sky Peças',
                'tipo' => 'fornecedor',
                'ativo' => true,
            ]
        );

        $resumo = [
            'montadoras' => 0,
            'veiculos' => 0,
            'montadoras_operacionais_criadas' => 0,
            'veiculos_operacionais_criados' => 0,
            'veiculos_ignorados_sem_chave' => 0,
            'produtos' => 0,
            'vinculos_automaticos' => 0,
            'vinculos_revisao' => 0,
            'estados' => 0,
            'precos_historico' => 0,
            'departamentos' => 0,
            'tipos' => 0,
            'aplicacoes' => 0,
            'atributos' => 0,
            'imagens' => 0,
            'similares' => 0,
        ];

        DB::transaction(function () use ($diretorio, $fonte, &$resumo) {
            [
                $mapMontadoras,
                $mapMontadorasOperacionais,
                $resumo['montadoras'],
                $resumo['montadoras_operacionais_criadas'],
            ] = $this->importarMontadoras(
                $diretorio.'/montadoras.csv',
                $fonte
            );

            [
                $mapVeiculos,
                $resumo['veiculos'],
                $resumo['veiculos_operacionais_criados'],
                $resumo['veiculos_ignorados_sem_chave'],
            ] = $this->importarVeiculos(
                $diretorio.'/veiculos.csv',
                $fonte,
                $mapMontadoras,
                $mapMontadorasOperacionais
            );

            [$mapProdutos, $resumo['produtos']] = $this->importarProdutos(
                $diretorio.'/produtos.csv',
                $fonte
            );

            foreach ($mapProdutos as $catalogoProdutoId) {
                $catalogoProduto = CatalogoProduto::query()->find($catalogoProdutoId);

                if (!$catalogoProduto) {
                    continue;
                }

                $this->vinculador->tentar($catalogoProduto);
            }

            $resumo['vinculos_automaticos'] = CatalogoProduto::query()
                ->where('catalogo_fonte_id', $fonte->id)
                ->where('vinculo_status', 'automatico')
                ->count();

            $resumo['vinculos_revisao'] = CatalogoProduto::query()
                ->where('catalogo_fonte_id', $fonte->id)
                ->where('vinculo_status', 'revisao')
                ->count();

            $resumo['estados'] = $this->importarEstados(
                $diretorio.'/produto_estado_sky.csv',
                $mapProdutos
            );

            $resumo['precos_historico'] = $this->importarPrecosHistorico(
                $diretorio.'/produto_precos_historico.csv',
                $mapProdutos
            );

            $resumo['departamentos'] = $this->importarDepartamentos(
                $diretorio.'/produto_departamentos.csv',
                $fonte,
                $mapProdutos
            );

            $resumo['tipos'] = $this->importarTipos(
                $diretorio.'/produto_tipos.csv',
                $fonte,
                $mapProdutos
            );

            $resumo['aplicacoes'] = $this->importarAplicacoes(
                $diretorio.'/produto_aplicacoes.csv',
                $mapProdutos,
                $mapMontadoras,
                $mapVeiculos
            );

            $resumo['atributos'] = $this->importarAtributos(
                $diretorio.'/produto_atributos.csv',
                $mapProdutos
            );

            $resumo['imagens'] = $this->importarImagens(
                $diretorio.'/produto_imagens.csv',
                $mapProdutos
            );

            $resumo['similares'] = $this->importarSimilares(
                $diretorio.'/produto_similares.csv',
                $mapProdutos
            );

            $fonte->update([
                'ultima_importacao_em' => now(),
            ]);
        });

        return $resumo;
    }

    private function importarMontadoras(string $arquivo, CatalogoFonte $fonte): array
    {
        $mapCatalogo = [];
        $mapOperacional = [];
        $total = 0;
        $criadas = 0;

        foreach ($this->linhas($arquivo, obrigatorio: true) as $linha) {
            $chave = $this->texto($linha['sky_id'] ?? null);
            $nome = $this->texto($linha['nome'] ?? null);
            $idOrigem = (string) ($linha['id'] ?? '');

            if ($chave === '' || $nome === '') {
                continue;
            }

            $catalogoExistente = CatalogoMontadora::query()
                ->where('catalogo_fonte_id', $fonte->id)
                ->where('chave_externa', $chave)
                ->first();

            $montadora = null;

            if ($catalogoExistente?->montadora_id) {
                $montadora = Montadora::query()->find($catalogoExistente->montadora_id);
            }

            if (!$montadora) {
                $montadora = Montadora::query()
                    ->whereRaw('LOWER(TRIM(nome)) = ?', [mb_strtolower(trim($nome))])
                    ->first();
            }

            if (!$montadora) {
                $montadora = Montadora::query()->create(['nome' => $nome]);
                $criadas++;
            }

            $registro = CatalogoMontadora::query()->updateOrCreate(
                [
                    'catalogo_fonte_id' => $fonte->id,
                    'chave_externa' => $chave,
                ],
                [
                    'montadora_id' => $montadora->id,
                    'nome' => $nome,
                    'ativo_fonte' => $this->booleano($linha['ativo_sky'] ?? null, true),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                ]
            );

            $mapCatalogo[$idOrigem] = $registro->id;
            $mapOperacional[$idOrigem] = $montadora->id;
            $total++;
        }

        return [$mapCatalogo, $mapOperacional, $total, $criadas];
    }

    private function importarVeiculos(
        string $arquivo,
        CatalogoFonte $fonte,
        array $mapMontadoras,
        array $mapMontadorasOperacionais
    ): array {
        $map = [];
        $total = 0;
        $criadas = 0;
        $ignoradosSemChave = 0;

        foreach ($this->linhas($arquivo, obrigatorio: true) as $linha) {
            $chave = $this->texto($linha['sky_id'] ?? null);
            $nome = $this->texto($linha['nome'] ?? null);
            $montadoraOrigemId = (string) ($linha['montadora_id'] ?? '');
            $catalogoMontadoraId = $mapMontadoras[$montadoraOrigemId] ?? null;
            $montadoraId = $mapMontadorasOperacionais[$montadoraOrigemId] ?? null;
            $idOrigem = (string) ($linha['id'] ?? '');

            // Registros sem chave externa não têm identidade estável na fonte.
            // São preservados no CSV/coletor, mas não são promovidos ao Jaime.
            if ($chave === '') {
                $ignoradosSemChave++;
                continue;
            }

            if (!$catalogoMontadoraId || !$montadoraId || $nome === '') {
                continue;
            }

            $catalogoExistente = CatalogoVeiculo::query()
                ->where('catalogo_fonte_id', $fonte->id)
                ->where('catalogo_montadora_id', $catalogoMontadoraId)
                ->where('chave_externa', $chave)
                ->first();

            $veiculo = null;

            if ($catalogoExistente?->veiculo_id) {
                $veiculo = Veiculo::query()->find($catalogoExistente->veiculo_id);
            }

            if (!$veiculo) {
                $veiculo = Veiculo::query()
                    ->where('montadora_id', $montadoraId)
                    ->whereRaw('LOWER(TRIM(nome)) = ?', [mb_strtolower(trim($nome))])
                    ->first();
            }

            if (!$veiculo) {
                $veiculo = Veiculo::query()->create([
                    'nome' => $nome,
                    'montadora_id' => $montadoraId,
                ]);
                $criadas++;
            }

            $registro = CatalogoVeiculo::query()->updateOrCreate(
                [
                    'catalogo_fonte_id' => $fonte->id,
                    'catalogo_montadora_id' => $catalogoMontadoraId,
                    'chave_externa' => $chave,
                ],
                [
                    'veiculo_id' => $veiculo->id,
                    'nome' => $nome,
                    'ativo_fonte' => $this->booleano($linha['ativo_sky'] ?? null, true),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                ]
            );

            $map[$idOrigem] = $registro->id;
            $total++;
        }

        return [$map, $total, $criadas, $ignoradosSemChave];
    }

    private function importarProdutos(string $arquivo, CatalogoFonte $fonte): array
    {
        $map = [];
        $total = 0;

        foreach ($this->linhas($arquivo, obrigatorio: true) as $linha) {
            $skyId = $this->texto($linha['sky_id'] ?? null);

            if ($skyId === '') {
                continue;
            }

            $registro = CatalogoProduto::query()->updateOrCreate(
                [
                    'catalogo_fonte_id' => $fonte->id,
                    'chave_externa' => $skyId,
                ],
                [
                    'codigo_fabricante' => $this->nulo($linha['codigo_fabricante'] ?? null),
                    'codigo_auxiliar' => $this->nulo($linha['codigo_auxiliar'] ?? null),
                    'descricao' => $this->nulo($linha['descricao'] ?? null),
                    'marca_raw' => $this->nulo($linha['marca'] ?? null),
                    'unidade' => $this->nulo($linha['unidade'] ?? null),
                    'codigo_barras' => $this->nulo($linha['codigo_barras'] ?? null),
                    'digito_verificador' => $this->nulo($linha['digito_verificador'] ?? null),
                    'ncm' => $this->nulo($linha['ncm'] ?? null),
                    'quantidade_jogo' => $this->nulo($linha['quantidade_jogo'] ?? null),
                    'quantidade_caixa' => $this->nulo($linha['quantidade_caixa'] ?? null),
                    'origem_raw' => $this->nulo($linha['origem'] ?? null),
                    'imagem_thumb_url' => $this->nulo($linha['imagem_thumb_url'] ?? null),
                    'imagem_url' => $this->nulo($linha['imagem_url'] ?? null),
                    'aplicacao_status' => $this->nulo($linha['aplicacao_status'] ?? null),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                ]
            );

            $map[$skyId] = $registro->id;
            $total++;
        }

        return [$map, $total];
    }

    private function importarEstados(string $arquivo, array $mapProdutos): int
    {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;

            if (!$catalogoProdutoId) {
                continue;
            }

            CatalogoProdutoEstado::query()->updateOrCreate(
                ['catalogo_produto_id' => $catalogoProdutoId],
                [
                    'custo_observado' => $this->decimal($linha['custo_sky_observado'] ?? null),
                    'preco_venda_observado' => $this->decimal($linha['preco_venda_sky_observado'] ?? null),
                    'custo_base_seguro' => $this->decimal($linha['custo_base_seguro'] ?? null),
                    'promocao_detectada' => $this->booleano($linha['promocao_detectada'] ?? null),
                    'estoque_observado' => $this->inteiro($linha['estoque_observado'] ?? null),
                    'quantidade_promocional_disponivel' => $this->inteiro($linha['quantidade_promocional_disponivel'] ?? null),
                    'promocao_inicio' => $this->nulo($linha['promocao_inicio'] ?? null),
                    'promocao_fim' => $this->nulo($linha['promocao_fim'] ?? null),
                    'promocao_texto' => $this->nulo($linha['promocao_texto'] ?? null),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarPrecosHistorico(string $arquivo, array $mapProdutos): int
    {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;

            if (!$catalogoProdutoId) {
                continue;
            }

            $chave = hash('sha256', implode('|', [
                $linha['coletado_em'] ?? '',
                $linha['custo_sky_observado'] ?? '',
                $linha['preco_venda_sky_observado'] ?? '',
                $linha['custo_base_seguro'] ?? '',
                $linha['promocao_detectada'] ?? '',
                $linha['estoque_no_momento_da_mudanca'] ?? '',
                $linha['quantidade_promocional_disponivel'] ?? '',
                $linha['promocao_inicio'] ?? '',
                $linha['promocao_fim'] ?? '',
                $linha['promocao_texto'] ?? '',
            ]));

            DB::table('catalogo_produto_precos')->updateOrInsert(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'chave_historico' => $chave,
                ],
                [
                    'custo_observado' => $this->decimal($linha['custo_sky_observado'] ?? null),
                    'preco_venda_observado' => $this->decimal($linha['preco_venda_sky_observado'] ?? null),
                    'custo_base_seguro' => $this->decimal($linha['custo_base_seguro'] ?? null),
                    'promocao_detectada' => $this->booleano($linha['promocao_detectada'] ?? null),
                    'estoque_no_momento' => $this->inteiro($linha['estoque_no_momento_da_mudanca'] ?? null),
                    'quantidade_promocional_disponivel' => $this->inteiro($linha['quantidade_promocional_disponivel'] ?? null),
                    'promocao_inicio' => $this->nulo($linha['promocao_inicio'] ?? null),
                    'promocao_fim' => $this->nulo($linha['promocao_fim'] ?? null),
                    'promocao_texto' => $this->nulo($linha['promocao_texto'] ?? null),
                    'coletado_em' => $this->data($linha['coletado_em'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarDepartamentos(
        string $arquivo,
        CatalogoFonte $fonte,
        array $mapProdutos
    ): int {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;
            $chave = $this->texto($linha['departamento_sky_id'] ?? null);

            if (!$catalogoProdutoId || $chave === '') {
                continue;
            }

            $departamento = CatalogoDepartamento::query()->updateOrCreate(
                [
                    'catalogo_fonte_id' => $fonte->id,
                    'chave_externa' => $chave,
                ],
                ['nome' => $this->texto($linha['departamento'] ?? null)]
            );

            DB::table('catalogo_produto_departamentos')->updateOrInsert(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'catalogo_departamento_id' => $departamento->id,
                ],
                [
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarTipos(
        string $arquivo,
        CatalogoFonte $fonte,
        array $mapProdutos
    ): int {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;
            $chave = $this->texto($linha['tipo_produto_sky_id'] ?? null);

            if (!$catalogoProdutoId || $chave === '') {
                continue;
            }

            $tipo = CatalogoTipoProduto::query()->updateOrCreate(
                [
                    'catalogo_fonte_id' => $fonte->id,
                    'chave_externa' => $chave,
                ],
                ['nome' => $this->texto($linha['tipo_produto'] ?? null)]
            );

            DB::table('catalogo_produto_tipos')->updateOrInsert(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'catalogo_tipo_produto_id' => $tipo->id,
                ],
                [
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_coleta_em' => $this->data($linha['ultima_coleta_em'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarAplicacoes(
        string $arquivo,
        array $mapProdutos,
        array $mapMontadoras,
        array $mapVeiculos
    ): int {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;

            if (!$catalogoProdutoId) {
                continue;
            }

            $camposChave = [
                'montadora' => $linha['montadora'] ?? '',
                'veiculo' => $linha['veiculo'] ?? '',
                'mes_ano' => $linha['mes_ano'] ?? '',
                'modelo' => $linha['modelo'] ?? '',
                'motor' => $linha['motor'] ?? '',
                'comp' => $linha['comp'] ?? '',
                'valvulas' => $linha['valvulas'] ?? '',
                'cilindros' => $linha['cilindros'] ?? '',
                'combustivel' => $linha['combustivel'] ?? '',
                'complemento' => $linha['complemento'] ?? '',
            ];

            $chave = hash('sha256', json_encode($camposChave, JSON_UNESCAPED_UNICODE));

            CatalogoProdutoAplicacao::query()->updateOrCreate(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'chave_aplicacao' => $chave,
                ],
                [
                    'catalogo_montadora_id' => $mapMontadoras[(string) ($linha['montadora_id'] ?? '')] ?? null,
                    'catalogo_veiculo_id' => $mapVeiculos[(string) ($linha['veiculo_id'] ?? '')] ?? null,
                    'montadora_raw' => $this->nulo($linha['montadora'] ?? null),
                    'veiculo_raw' => $this->nulo($linha['veiculo'] ?? null),
                    'mes_ano' => $this->nulo($linha['mes_ano'] ?? null),
                    'modelo' => $this->nulo($linha['modelo'] ?? null),
                    'motor' => $this->nulo($linha['motor'] ?? null),
                    'comp' => $this->nulo($linha['comp'] ?? null),
                    'valvulas' => $this->nulo($linha['valvulas'] ?? null),
                    'cilindros' => $this->nulo($linha['cilindros'] ?? null),
                    'combustivel' => $this->nulo($linha['combustivel'] ?? null),
                    'complemento' => $this->nulo($linha['complemento'] ?? null),
                    'ativa' => true,
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_confirmacao_em' => $this->data($linha['ultima_confirmacao_sky'] ?? null),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarAtributos(string $arquivo, array $mapProdutos): int
    {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;
            $chave = $this->texto($linha['chave'] ?? null);

            if (!$catalogoProdutoId || $chave === '') {
                continue;
            }

            CatalogoProdutoAtributo::query()->updateOrCreate(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'chave' => $chave,
                ],
                [
                    'chave_raw' => $this->nulo($linha['chave_raw'] ?? null),
                    'valor' => $this->nulo($linha['valor'] ?? null),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_confirmacao_em' => $this->data($linha['ultima_confirmacao_em'] ?? null),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarImagens(string $arquivo, array $mapProdutos): int
    {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $catalogoProdutoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;

            if (!$catalogoProdutoId) {
                continue;
            }

            $identificador = $this->texto($linha['hash_sha256'] ?? null)
                ?: $this->texto($linha['url_original'] ?? null)
                ?: $this->texto($linha['arquivo_local'] ?? null);

            if ($identificador === '') {
                continue;
            }

            $chave = hash('sha256', $identificador);

            CatalogoProdutoImagem::query()->updateOrCreate(
                [
                    'catalogo_produto_id' => $catalogoProdutoId,
                    'chave_imagem' => $chave,
                ],
                [
                    'url_original' => $this->nulo($linha['url_original'] ?? null),
                    'url_thumb' => $this->nulo($linha['url_thumb'] ?? null),
                    'arquivo_origem' => $this->nulo($linha['arquivo_local'] ?? null),
                    'hash_sha256' => $this->nulo($linha['hash_sha256'] ?? null),
                    'mime_type' => $this->nulo($linha['mime_type'] ?? null),
                    'tamanho_bytes' => $this->inteiro($linha['tamanho_bytes'] ?? null),
                    'principal' => $this->booleano($linha['principal'] ?? null),
                    'status' => $this->nulo($linha['status'] ?? null),
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_confirmacao_em' => $this->data($linha['ultima_confirmacao_em'] ?? null),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function importarSimilares(string $arquivo, array $mapProdutos): int
    {
        $total = 0;

        foreach ($this->linhas($arquivo) as $linha) {
            $produtoId = $mapProdutos[$this->texto($linha['produto_sky_id'] ?? null)] ?? null;
            $similarId = $mapProdutos[$this->texto($linha['similar_sky_id'] ?? null)] ?? null;

            if (!$produtoId || !$similarId || $produtoId === $similarId) {
                continue;
            }

            DB::table('catalogo_produto_similares')->updateOrInsert(
                [
                    'catalogo_produto_id' => $produtoId,
                    'similar_catalogo_produto_id' => $similarId,
                ],
                [
                    'primeira_coleta_em' => $this->data($linha['primeira_coleta_em'] ?? null),
                    'ultima_confirmacao_em' => $this->data($linha['ultima_confirmacao_em'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $total++;
        }

        return $total;
    }

    private function linhas(string $arquivo, bool $obrigatorio = false): iterable
    {
        if (!is_file($arquivo)) {
            if ($obrigatorio) {
                throw new RuntimeException("Arquivo obrigatório não encontrado: {$arquivo}");
            }

            return;
        }

        $file = new SplFileObject($arquivo, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(';');

        $cabecalho = null;

        foreach ($file as $colunas) {
            if (!is_array($colunas) || $colunas === [null]) {
                continue;
            }

            if ($cabecalho === null) {
                $cabecalho = array_map(function ($valor) {
                    return ltrim((string) $valor, "\xEF\xBB\xBF");
                }, $colunas);
                continue;
            }

            if (count($colunas) !== count($cabecalho)) {
                continue;
            }

            yield array_combine($cabecalho, $colunas);
        }
    }

    private function texto(mixed $valor): string
    {
        return trim((string) ($valor ?? ''));
    }

    private function nulo(mixed $valor): ?string
    {
        $valor = $this->texto($valor);

        return $valor === '' ? null : $valor;
    }

    private function inteiro(mixed $valor): ?int
    {
        $valor = $this->texto($valor);

        return $valor === '' ? null : (int) $valor;
    }

    private function decimal(mixed $valor): ?string
    {
        $valor = $this->texto($valor);

        if ($valor === '') {
            return null;
        }

        if (str_contains($valor, ',') && !str_contains($valor, '.')) {
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }

    private function booleano(mixed $valor, bool $padrao = false): bool
    {
        $valor = mb_strtolower($this->texto($valor));

        if ($valor === '') {
            return $padrao;
        }

        return in_array($valor, ['1', 'true', 'sim', 'yes'], true);
    }

    private function data(mixed $valor): ?Carbon
    {
        $valor = $this->texto($valor);

        if ($valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }
}
