<?php

namespace App\Livewire\Veiculo;

use App\Models\Veiculo;
use Livewire\Component;

class SeletorVeiculo extends Component
{
    public string $buscaVeiculo = '';

    public array $resultadosVeiculos = [];

    public ?int $veiculoSelecionadoId = null;

    public ?array $veiculoSelecionado = null;

    public function mount(
        ?int $veiculoSelecionadoId = null
    ): void {
        if (!$veiculoSelecionadoId) {
            return;
        }

        $veiculo = Veiculo::with('montadora')
            ->find($veiculoSelecionadoId);

        if (!$veiculo) {
            return;
        }

        $this->veiculoSelecionadoId =
            $veiculo->id;

        $this->veiculoSelecionado =
            $this->formatarVeiculo($veiculo);
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
                fn ($termo) =>
                    mb_strtolower($termo)
            )
            ->values()
            ->all();

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
            $query->where(
                function ($query) use ($termo) {
                    $query
                        ->where(
                            'nome',
                            'like',
                            "%{$termo}%"
                        )
                        ->orWhereHas(
                            'montadora',
                            function (
                                $montadoraQuery
                            ) use ($termo) {
                                $montadoraQuery->where(
                                    'nome',
                                    'like',
                                    "%{$termo}%"
                                );
                            }
                        );
                }
            );
        }

        $buscaNormalizada = mb_strtolower(
            preg_replace(
                '/\s+/',
                ' ',
                $busca
            )
        );

        $this->resultadosVeiculos = $query
            ->get()
            ->sortBy(
                function ($veiculo) use (
                    $buscaNormalizada
                ) {
                    $nome = mb_strtolower(
                        trim($veiculo->nome)
                    );

                    $montadora = mb_strtolower(
                        trim(
                            $veiculo->montadora?->nome
                            ?? ''
                        )
                    );

                    $completo = trim(
                        "{$montadora} {$nome}"
                    );

                    if (
                        $completo ===
                        $buscaNormalizada
                    ) {
                        return 0;
                    }

                    if (
                        $nome ===
                        $buscaNormalizada
                    ) {
                        return 1;
                    }

                    if (
                        $montadora ===
                        $buscaNormalizada
                    ) {
                        return 2;
                    }

                    if (
                        str_starts_with(
                            $completo,
                            $buscaNormalizada
                        )
                    ) {
                        return 3;
                    }

                    if (
                        str_starts_with(
                            $nome,
                            $buscaNormalizada
                        )
                    ) {
                        return 4;
                    }

                    return 5;
                }
            )
            ->values()
            ->map(
                fn ($veiculo) => array_merge(
                    $this->formatarVeiculo(
                        $veiculo
                    ),
                    [
                        'selecionado' =>
                            $veiculo->id ===
                            $this->veiculoSelecionadoId,
                    ]
                )
            )
            ->toArray();
    }

    public function selecionarVeiculo(
        int $veiculoId
    ): void {
        $veiculo = Veiculo::with('montadora')
            ->find($veiculoId);

        if (!$veiculo) {
            return;
        }

        $this->veiculoSelecionadoId =
            $veiculo->id;

        $this->veiculoSelecionado =
            $this->formatarVeiculo($veiculo);

        $this->buscaVeiculo = '';
        $this->resultadosVeiculos = [];
    }

    public function removerVeiculo(): void
    {
        $this->veiculoSelecionadoId = null;
        $this->veiculoSelecionado = null;
    }

    private function formatarVeiculo(
        Veiculo $veiculo
    ): array {
        return [
            'id' => $veiculo->id,
            'nome' => $veiculo->nome,

            'montadora' =>
                $veiculo->montadora?->nome,
        ];
    }

    public function render()
    {
        return view(
            'livewire.veiculo.seletor-veiculo'
        );
    }
}
