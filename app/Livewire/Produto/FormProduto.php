<?php

namespace App\Livewire\Produto;

use App\Actions\Marca\CriarMarca;
use App\Models\Marca;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use App\Models\Veiculo;
use App\Services\Produto\GerarChaveAplicacao;
use App\Services\Produto\ParserAplicacoes;
use App\Services\Produto\ResolverAplicacoes;
use App\Services\Produto\ValidadorAplicacoes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FormProduto extends Component
{
    public ?Produto $produto = null;

    public string $codigo = '';
    public string $codigoFabricante = '';
    public string $codigoBarras = '';

    public ?int $marcaSelecionada = null;
    public bool $mostrarCadastroMarca = false;
    public string $novaMarcaNome = '';
    public string $mensagemMarca = '';

    public string $buscaVeiculo = '';
    public array $resultadosVeiculos = [];
    public array $veiculosSelecionados = [];
    public array $aplicacoesManuais = [];

    public bool $codigoDuplicado = false;
    public bool $codigoFabricanteDuplicado = false;
    public bool $codigoBarrasDuplicado = false;

    public bool $mostrarPreviaImportacao = false;
    public string $mensagemImportacao = '';
    public array $aplicacoesImportadas = [];
    public array $previsaoImportacao = [];

    public function mount(?Produto $produto = null): void
    {
        $this->produto = $produto;

        $marcaInicial = old('marca_id', $produto?->marca_id);

        $this->marcaSelecionada = $marcaInicial
            ? (int) $marcaInicial
            : null;

        if (!$produto) {
            return;
        }

        $this->codigo = $produto->codigo ?? '';
        $this->codigoFabricante = $produto->codigo_fabricante ?? '';
        $this->codigoBarras = $produto->codigo_barras ?? '';

        $this->carregarAplicacoesManuais(
            $produto
        );
    }

    public function abrirCadastroMarca(): void
    {
        $this->novaMarcaNome = '';
        $this->mensagemMarca = '';
        $this->resetValidation('novaMarcaNome');
        $this->mostrarCadastroMarca = true;
    }

    public function fecharCadastroMarca(): void
    {
        $this->mostrarCadastroMarca = false;
        $this->novaMarcaNome = '';
        $this->resetValidation('novaMarcaNome');
    }

    public function criarMarcaRapida(): void
    {
        $this->novaMarcaNome = trim($this->novaMarcaNome);

        $dados = $this->validate([
            'novaMarcaNome' => [
                'required',
                'string',
                'max:150',
                Rule::unique('marcas', 'nome'),
            ],
        ], [
            'novaMarcaNome.required' => 'Informe o nome da marca.',
            'novaMarcaNome.string' => 'O nome da marca é inválido.',
            'novaMarcaNome.max' => 'O nome da marca não pode ter mais de 150 caracteres.',
            'novaMarcaNome.unique' => 'Esta marca já está cadastrada.',
        ]);

        $marca = app(CriarMarca::class)->execute([
            'nome' => $dados['novaMarcaNome'],
            'logo_url' => null,
            'ativo' => true,
        ]);

        $this->marcaSelecionada = (int) $marca->id;
        $this->novaMarcaNome = '';
        $this->mostrarCadastroMarca = false;
        $this->mensagemMarca = "Marca {$marca->nome} cadastrada e selecionada.";
        $this->resetValidation('novaMarcaNome');
    }

    public function updatedMarcaSelecionada(): void
    {
        $this->updatedCodigoFabricante();
    }

    public function updatedCodigo(): void
    {
        $codigo = mb_strtoupper(
            trim($this->codigo)
        );

        $this->codigo = $codigo;

        if ($codigo === '') {
            $this->codigoDuplicado = false;

            return;
        }

        $query = Produto::query()
            ->where('codigo', $codigo);

        if ($this->produto) {
            $query->where(
                'id',
                '!=',
                $this->produto->id
            );
        }

        $this->codigoDuplicado =
            $query->exists();
    }

    public function updatedCodigoFabricante(): void
    {
        $codigo =
            trim($this->codigoFabricante);

        if (
            $codigo === ''
            || !$this->marcaSelecionada
        ) {
            $this->codigoFabricanteDuplicado =
                false;

            return;
        }

        $query = Produto::query()
            ->where(
                'codigo_fabricante',
                $codigo
            )
            ->where(
                'marca_id',
                $this->marcaSelecionada
            );

        if ($this->produto) {
            $query->where(
                'id',
                '!=',
                $this->produto->id
            );
        }

        $this->codigoFabricanteDuplicado =
            $query->exists();
    }

    public function updatedCodigoBarras(): void
    {
        $codigo = trim($this->codigoBarras);

        if ($codigo === '') {
            $this->codigoBarrasDuplicado = false;
            return;
        }

        $query = Produto::where('codigo_barras', $codigo);

        if ($this->produto) {
            $query->where('id', '!=', $this->produto->id);
        }

        $this->codigoBarrasDuplicado = $query->exists();
    }

    public function updatedBuscaVeiculo(): void
    {
        $busca = trim($this->buscaVeiculo);

        if (mb_strlen($busca) < 2) {
            $this->resultadosVeiculos = [];

            return;
        }

        $termos = collect(
            preg_split(
                '/\s+/',
                $busca,
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: []
        )
            ->map(fn ($termo) => trim($termo))
            ->filter()
            ->unique(
                fn ($termo) => mb_strtolower($termo)
            )
            ->values()
            ->all();

        $idsSelecionados = array_map(
            'intval',
            array_column(
                $this->veiculosSelecionados,
                'id'
            )
        );

        $query = Veiculo::query()
            ->with([
                'montadora:id,nome',
            ])
            ->select([
                'id',
                'nome',
                'montadora_id',
            ]);

        foreach ($termos as $termo) {
            $query->where(function ($query) use ($termo) {
                $query
                    ->where(
                        'nome',
                        'like',
                        "%{$termo}%"
                    )
                    ->orWhereHas(
                        'montadora',
                        function ($montadoraQuery) use ($termo) {
                            $montadoraQuery->where(
                                'nome',
                                'like',
                                "%{$termo}%"
                            );
                        }
                    );
            });
        }

        $buscaNormalizada = mb_strtolower(
            preg_replace('/\s+/', ' ', $busca)
        );

        $this->resultadosVeiculos = $query
            ->get()
            ->sortBy(function ($veiculo) use ($buscaNormalizada) {
                $nome = mb_strtolower(
                    trim($veiculo->nome)
                );

                $montadora = mb_strtolower(
                    trim($veiculo->montadora?->nome ?? '')
                );

                $completo = trim(
                    "{$montadora} {$nome}"
                );

                if ($completo === $buscaNormalizada) {
                    return 0;
                }

                if ($nome === $buscaNormalizada) {
                    return 1;
                }

                if ($montadora === $buscaNormalizada) {
                    return 2;
                }

                if (str_starts_with(
                    $completo,
                    $buscaNormalizada
                )) {
                    return 3;
                }

                if (str_starts_with(
                    $nome,
                    $buscaNormalizada
                )) {
                    return 4;
                }

                if (str_starts_with(
                    $montadora,
                    $buscaNormalizada
                )) {
                    return 5;
                }

                return 6;
            })
            ->values()
            ->map(fn ($veiculo) => [
                'id' => $veiculo->id,
                'nome' => $veiculo->nome,
                'montadora' => $veiculo->montadora?->nome,
                'selecionado' => in_array(
                    (int) $veiculo->id,
                    $idsSelecionados,
                    true
                ),
            ])
            ->toArray();
    }

    public function adicionarVeiculo(int $veiculoId): void
    {
        $veiculo = Veiculo::with(
            'montadora'
        )->find($veiculoId);

        if (!$veiculo) {
            return;
        }

        $this->adicionarAplicacaoManual(
            $veiculo,
            []
        );

        $this->buscaVeiculo = '';
        $this->resultadosVeiculos = [];
    }

    public function removerVeiculo(int $veiculoId): void
    {
        $this->veiculosSelecionados =
            collect(
                $this->veiculosSelecionados
            )
                ->reject(
                    fn ($veiculo) =>
                        (int) $veiculo['id']
                        === $veiculoId
                )
                ->values()
                ->toArray();

        /*
         * Remover um veículo na interface remove somente
         * aplicações MANUAIS daquele veículo.
         *
         * Aplicações provenientes de catálogo não vivem
         * neste array e não são afetadas.
         */
        $this->aplicacoesManuais =
            collect(
                $this->aplicacoesManuais
            )
                ->reject(
                    fn ($aplicacao) =>
                        (int) (
                            $aplicacao[
                                'veiculo_id'
                            ]
                            ?? 0
                        ) === $veiculoId
                )
                ->values()
                ->toArray();

        if (
            trim(
                $this->buscaVeiculo
            ) !== ''
        ) {
            $this->updatedBuscaVeiculo();
        }
    }

    public function importarAplicacoes(string $texto): void
    {
        $this->limparImportacao();

        $validador = app(ValidadorAplicacoes::class);
        $validacao = $validador->validar($texto);

        if (!$validacao['valido']) {
            $this->mensagemImportacao = $validacao['mensagem'];
            return;
        }

        $parser = app(ParserAplicacoes::class);
        $aplicacoes = $parser->processar($texto);

        if ($aplicacoes === []) {
            $this->mensagemImportacao = 'Nenhuma aplicação válida foi encontrada.';
            return;
        }

        $resolver = app(ResolverAplicacoes::class);
        $previsao = $resolver->resolver($aplicacoes);

        $this->aplicacoesImportadas = $aplicacoes;
        $this->previsaoImportacao = $previsao;
        $this->mostrarPreviaImportacao = true;
        $this->mensagemImportacao = 'Aplicações analisadas com sucesso.';
    }

    public function confirmarImportacao(): void
    {
        if (
            !$this->mostrarPreviaImportacao
            || $this->aplicacoesImportadas === []
        ) {
            $this->mensagemImportacao =
                'Nenhuma importação aguardando confirmação.';

            return;
        }

        $resolver =
            app(
                ResolverAplicacoes::class
            );

        DB::transaction(
            function () use (
                $resolver
            ) {
                foreach (
                    $this->aplicacoesImportadas
                    as $aplicacao
                ) {
                    $veiculo =
                        $resolver
                            ->resolverOuCriarVeiculo(
                                $aplicacao
                            );

                    $veiculo->loadMissing(
                        'montadora'
                    );

                    $this
                        ->adicionarAplicacaoManual(
                            $veiculo,
                            $aplicacao
                        );
                }
            }
        );

        $total =
            count(
                $this->aplicacoesImportadas
            );

        $this->limparImportacao();

        $this->mensagemImportacao =
            "{$total} aplicações importadas com sucesso.";
    }

    private function carregarAplicacoesManuais(
        Produto $produto
    ): void {
        $possuiQualquerAplicacao =
            $produto
                ->aplicacoes()
                ->exists();

        $aplicacoes =
            $produto
                ->aplicacoes()
                ->where(
                    'origem',
                    ProdutoAplicacao::
                        ORIGEM_MANUAL
                )
                ->with(
                    'veiculo.montadora'
                )
                ->get();

        $this->aplicacoesManuais =
            $aplicacoes
                ->map(
                    fn ($aplicacao) => [
                        'veiculo_id' =>
                            $aplicacao
                                ->veiculo_id,

                        'mes_ano' =>
                            $aplicacao
                                ->mes_ano,

                        'modelo' =>
                            $aplicacao
                                ->modelo,

                        'motor' =>
                            $aplicacao
                                ->motor,

                        'comp' =>
                            $aplicacao
                                ->comp,

                        'valvulas' =>
                            $aplicacao
                                ->valvulas,

                        'cilindros' =>
                            $aplicacao
                                ->cilindros,

                        'combustivel' =>
                            $aplicacao
                                ->combustivel,

                        'complemento' =>
                            $aplicacao
                                ->complemento,
                    ]
                )
                ->values()
                ->toArray();

        $this->veiculosSelecionados =
            $aplicacoes
                ->filter(
                    fn ($aplicacao) =>
                        $aplicacao->veiculo
                            !== null
                )
                ->unique(
                    'veiculo_id'
                )
                ->map(
                    fn ($aplicacao) => [
                        'id' =>
                            $aplicacao
                                ->veiculo
                                ->id,

                        'nome' =>
                            $aplicacao
                                ->veiculo
                                ->nome,

                        'montadora' =>
                            $aplicacao
                                ->veiculo
                                ->montadora
                                ?->nome,
                    ]
                )
                ->values()
                ->toArray();

        /*
         * Compatibilidade defensiva para algum Produto
         * legado que possua somente produtos_veiculos
         * e ainda não tenha sido convertido.
         *
         * Se já existir QUALQUER produto_aplicacao
         * (inclusive Sky), não transformamos a pivot em
         * conhecimento manual.
         */
        if (
            $possuiQualquerAplicacao
            || $this->veiculosSelecionados !== []
        ) {
            return;
        }

        $produto->loadMissing(
            'veiculos.montadora'
        );

        foreach (
            $produto->veiculos
            as $veiculo
        ) {
            $this->adicionarAplicacaoManual(
                $veiculo,
                []
            );
        }
    }

    private function adicionarAplicacaoManual(
        Veiculo $veiculo,
        array $dados
    ): void {
        $aplicacao = [
            'veiculo_id' =>
                (int) $veiculo->id,

            'mes_ano' =>
                $this->valorAplicacao(
                    $dados['mes_ano']
                    ?? null
                ),

            'modelo' =>
                $this->valorAplicacao(
                    $dados['modelo']
                    ?? null
                ),

            'motor' =>
                $this->valorAplicacao(
                    $dados['motor']
                    ?? null
                ),

            'comp' =>
                $this->valorAplicacao(
                    $dados['comp']
                    ?? null
                ),

            'valvulas' =>
                $this->valorAplicacao(
                    $dados['valvulas']
                    ?? null
                ),

            'cilindros' =>
                $this->valorAplicacao(
                    $dados['cilindros']
                    ?? null
                ),

            'combustivel' =>
                $this->valorAplicacao(
                    $dados['combustivel']
                    ?? null
                ),

            'complemento' =>
                $this->valorAplicacao(
                    $dados['complemento']
                    ?? null
                ),
        ];

        $possuiDetalhes =
            $this->aplicacaoPossuiDetalhes(
                $aplicacao
            );

        /*
         * Se já havia uma seleção simples do mesmo
         * veículo e depois chegou uma aplicação rica
         * pelo importador, removemos apenas o placeholder
         * vazio. Aplicações técnicas distintas do mesmo
         * veículo continuam coexistindo.
         */
        if ($possuiDetalhes) {
            $this->aplicacoesManuais =
                collect(
                    $this->aplicacoesManuais
                )
                    ->reject(
                        fn ($existente) =>
                            (int) (
                                $existente[
                                    'veiculo_id'
                                ]
                                ?? 0
                            )
                                === (int) $veiculo->id
                            && !$this
                                ->aplicacaoPossuiDetalhes(
                                    $existente
                                )
                    )
                    ->values()
                    ->toArray();
        }

        $gerador =
            app(
                GerarChaveAplicacao::class
            );

        $chaveNova =
            $gerador->gerar(
                (int) $veiculo->id,
                $aplicacao
            );

        $jaExiste =
            collect(
                $this->aplicacoesManuais
            )
                ->contains(
                    fn ($existente) =>
                        $gerador->gerar(
                            isset(
                                $existente[
                                    'veiculo_id'
                                ]
                            )
                                ? (int) $existente[
                                    'veiculo_id'
                                ]
                                : null,
                            $existente
                        ) === $chaveNova
                );

        if (!$jaExiste) {
            $this->aplicacoesManuais[] =
                $aplicacao;
        }

        if (
            !collect(
                $this->veiculosSelecionados
            )->contains(
                'id',
                $veiculo->id
            )
        ) {
            $this->veiculosSelecionados[] = [
                'id' =>
                    $veiculo->id,

                'nome' =>
                    $veiculo->nome,

                'montadora' =>
                    $veiculo
                        ->montadora
                        ?->nome,
            ];
        }
    }

    private function aplicacaoPossuiDetalhes(
        array $aplicacao
    ): bool {
        foreach (
            [
                'mes_ano',
                'modelo',
                'motor',
                'comp',
                'valvulas',
                'cilindros',
                'combustivel',
                'complemento',
            ]
            as $campo
        ) {
            if (
                trim(
                    (string) (
                        $aplicacao[
                            $campo
                        ]
                        ?? ''
                    )
                ) !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    private function valorAplicacao(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor =
            trim(
                (string) $valor
            );

        return $valor !== ''
            ? $valor
            : null;
    }

    public function cancelarImportacao(): void
    {
        $this->limparImportacao();
        $this->mensagemImportacao = 'Importação cancelada.';
    }

    private function limparImportacao(): void
    {
        $this->mostrarPreviaImportacao = false;
        $this->aplicacoesImportadas = [];
        $this->previsaoImportacao = [];
    }

    public function render()
    {
        $queryMarcas = Marca::query()
            ->select('id', 'nome', 'ativo')
            ->where('ativo', true);

        if ($this->produto?->marca_id) {
            $queryMarcas->orWhere('id', $this->produto->marca_id);
        }

        return view('livewire.produto.form-produto', [
            'marcas' => $queryMarcas
                ->orderBy('nome')
                ->get(),
        ]);
    }
}
