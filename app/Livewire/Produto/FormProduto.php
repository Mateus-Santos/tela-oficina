<?php

namespace App\Livewire\Produto;

use App\Actions\Marca\CriarMarca;
use App\Models\Marca;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\Veiculo;
use App\Services\Produto\ParserAplicacoes;
use App\Services\Produto\ResolverAplicacoes;
use App\Services\Produto\ValidadorAplicacoes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FormProduto extends Component
{
    public ?Produto $produto = null;

    public string $codigoFabricante = '';
    public string $codigoBarras = '';

    public ?int $marcaSelecionada = null;
    public bool $mostrarCadastroMarca = false;
    public string $novaMarcaNome = '';
    public string $mensagemMarca = '';

    public string $buscaVeiculo = '';
    public array $resultadosVeiculos = [];
    public array $veiculosSelecionados = [];

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

        $this->codigoFabricante = $produto->codigo_fabricante ?? '';
        $this->codigoBarras = $produto->codigo_barras ?? '';

        $this->veiculosSelecionados = $produto->veiculos
            ->load('montadora')
            ->map(fn ($veiculo) => [
                'id' => $veiculo->id,
                'nome' => $veiculo->nome,
                'montadora' => $veiculo->montadora?->nome,
            ])
            ->values()
            ->toArray();
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

    public function updatedCodigoFabricante(): void
    {
        $codigo = trim($this->codigoFabricante);

        if ($codigo === '') {
            $this->codigoFabricanteDuplicado = false;
            return;
        }

        $query = Produto::where('codigo_fabricante', $codigo);

        if ($this->produto) {
            $query->where('id', '!=', $this->produto->id);
        }

        $this->codigoFabricanteDuplicado = $query->exists();
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
        if (collect($this->veiculosSelecionados)->contains('id', $veiculoId)) {
            return;
        }

        $veiculo = Veiculo::with('montadora')->find($veiculoId);

        if (!$veiculo) {
            return;
        }

        $this->veiculosSelecionados[] = [
            'id' => $veiculo->id,
            'nome' => $veiculo->nome,
            'montadora' => $veiculo->montadora?->nome,
        ];

        $this->buscaVeiculo = '';
        $this->resultadosVeiculos = [];
    }

    public function removerVeiculo(int $veiculoId): void
    {
        $this->veiculosSelecionados = collect($this->veiculosSelecionados)
            ->reject(fn ($veiculo) => (int) $veiculo['id'] === $veiculoId)
            ->values()
            ->toArray();

        if (trim($this->buscaVeiculo) !== '') {
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
        if (!$this->mostrarPreviaImportacao || $this->aplicacoesImportadas === []) {
            $this->mensagemImportacao = 'Nenhuma importação aguardando confirmação.';
            return;
        }

        DB::transaction(function () {
            foreach ($this->aplicacoesImportadas as $aplicacao) {
                $montadora = Montadora::query()->firstOrCreate([
                    'nome' => $aplicacao['montadora'],
                ]);

                $veiculo = Veiculo::query()
                    ->where('montadora_id', $montadora->id)
                    ->where('nome', $aplicacao['veiculo'])
                    ->first();

                if (!$veiculo) {
                    $veiculo = Veiculo::create([
                        'nome' => $aplicacao['veiculo'],
                        'montadora_id' => $montadora->id,
                    ]);
                }

                $this->adicionarVeiculo($veiculo->id);
            }
        });

        $total = count($this->aplicacoesImportadas);

        $this->limparImportacao();
        $this->mensagemImportacao = "{$total} aplicações importadas com sucesso.";
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
