<?php

namespace App\Livewire\Produto;

use App\Models\Montadora;
use App\Models\Produto;
use App\Models\Veiculo;
use App\Services\Produto\ParserAplicacoes;
use App\Services\Produto\ResolverAplicacoes;
use App\Services\Produto\ValidadorAplicacoes;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FormProduto extends Component
{
    public ?Produto $produto = null;

    public string $codigoFabricante = '';
    public string $codigoBarras = '';
    public string $montadoraSelecionada = '';

    public array $veiculos = [];
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

        if (!$produto) {
            return;
        }

        $this->codigoFabricante = $produto->codigo_fabricante ?? '';
        $this->codigoBarras = $produto->codigo_barras ?? '';

        $this->veiculosSelecionados = $produto->veiculos
            ->map(fn ($veiculo) => [
                'id' => $veiculo->id,
                'nome' => $veiculo->nome,
                'montadora' => $veiculo->montadora?->nome,
            ])
            ->values()
            ->toArray();
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

    public function updatedMontadoraSelecionada(): void
    {
        $this->veiculosSelecionados = [];

        if ($this->montadoraSelecionada === '') {
            $this->veiculos = [];
            return;
        }

        $this->veiculos = Veiculo::query()
            ->where('montadora_id', $this->montadoraSelecionada)
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->toArray();
    }

    public function adicionarVeiculo(int $veiculoId): void
    {
        if (
            collect($this->veiculosSelecionados)
                ->contains('id', $veiculoId)
        ) {
            return;
        }

        $veiculo = Veiculo::with('montadora')
            ->find($veiculoId);

        if (!$veiculo) {
            return;
        }

        $this->veiculosSelecionados[] = [
            'id' => $veiculo->id,
            'nome' => $veiculo->nome,
            'montadora' => $veiculo->montadora?->nome,
        ];
    }

    public function removerVeiculo(int $veiculoId): void
    {
        $this->veiculosSelecionados = collect(
            $this->veiculosSelecionados
        )
            ->reject(fn ($veiculo) => (int) $veiculo['id'] === $veiculoId)
            ->values()
            ->toArray();
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
                $montadora = Montadora::query()
                    ->firstOrCreate([
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
        return view('livewire.produto.form-produto', [
            'montadoras' => Montadora::query()
                ->select('id', 'nome')
                ->orderBy('nome')
                ->get(),
        ]);
    }
}
