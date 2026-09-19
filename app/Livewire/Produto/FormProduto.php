<?php

namespace App\Livewire\Produto;

use App\Models\Montadora;
use App\Models\Produto;
use App\Models\Veiculo;
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
