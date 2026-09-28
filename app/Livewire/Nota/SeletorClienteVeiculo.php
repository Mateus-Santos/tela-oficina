<?php

namespace App\Livewire\Nota;

use App\Models\Cliente;
use App\Models\VeiculosCliente;
use Livewire\Component;

class SeletorClienteVeiculo extends Component
{
    public string $buscaCliente = '';
    public array $resultadosClientes = [];

    public ?int $clienteSelecionadoId = null;
    public ?array $clienteSelecionado = null;

    public string $buscaVeiculo = '';
    public array $resultadosVeiculos = [];

    public ?int $veiculoSelecionadoId = null;
    public ?array $veiculoSelecionado = null;

    public function mount(
        ?int $clienteSelecionadoId = null,
        ?int $veiculoSelecionadoId = null
    ): void {
        if ($clienteSelecionadoId) {
            $cliente = Cliente::with('pessoa')
                ->find($clienteSelecionadoId);

            if ($cliente) {
                $this->clienteSelecionadoId = $cliente->id;
                $this->clienteSelecionado = $this->formatarCliente($cliente);
            }
        }

        if ($veiculoSelecionadoId && $this->clienteSelecionadoId) {
            $veiculoCliente = VeiculosCliente::query()
                ->with('veiculo.montadora')
                ->where('id', $veiculoSelecionadoId)
                ->whereHas(
                    'clientes',
                    function ($query) {
                        $query->where(
                            'clientes.id',
                            $this->clienteSelecionadoId
                        );
                    }
                )
                ->first();

            if ($veiculoCliente) {
                $this->veiculoSelecionadoId = $veiculoCliente->id;
                $this->veiculoSelecionado = $this->formatarVeiculo(
                    $veiculoCliente
                );
            }
        }
    }

    public function updatedBuscaCliente(): void
    {
        $busca = trim($this->buscaCliente);

        if (mb_strlen($busca) < 2) {
            $this->resultadosClientes = [];
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

        $query = Cliente::query()
            ->with([
                'pessoa:id,nome,cpf,telefone_1,telefone_2',
            ])
            ->select([
                'id',
                'pessoa_id',
            ]);

        foreach ($termos as $termo) {
            $numerico = preg_replace(
                '/\D/',
                '',
                $termo
            );

            $query->whereHas(
                'pessoa',
                function ($pessoaQuery) use (
                    $termo,
                    $numerico
                ) {
                    $pessoaQuery->where(
                        function ($query) use (
                            $termo,
                            $numerico
                        ) {
                            $query->where(
                                'nome',
                                'like',
                                "%{$termo}%"
                            );

                            if ($numerico !== '') {
                                $query
                                    ->orWhere(
                                        'cpf',
                                        'like',
                                        "%{$numerico}%"
                                    )
                                    ->orWhere(
                                        'telefone_1',
                                        'like',
                                        "%{$numerico}%"
                                    )
                                    ->orWhere(
                                        'telefone_2',
                                        'like',
                                        "%{$numerico}%"
                                    );
                            }
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

        $buscaNumerica = preg_replace(
            '/\D/',
            '',
            $busca
        );

        $this->resultadosClientes = $query
            ->get()
            ->sortBy(
                function ($cliente) use (
                    $buscaNormalizada,
                    $buscaNumerica
                ) {
                    $nome = mb_strtolower(
                        trim(
                            $cliente->pessoa?->nome
                            ?? ''
                        )
                    );

                    $cpf = preg_replace(
                        '/\D/',
                        '',
                        $cliente->pessoa?->cpf ?? ''
                    );

                    $telefone1 = preg_replace(
                        '/\D/',
                        '',
                        $cliente->pessoa?->telefone_1 ?? ''
                    );

                    $telefone2 = preg_replace(
                        '/\D/',
                        '',
                        $cliente->pessoa?->telefone_2 ?? ''
                    );

                    if (
                        $buscaNumerica !== ''
                        && $cpf === $buscaNumerica
                    ) {
                        return 0;
                    }

                    if ($nome === $buscaNormalizada) {
                        return 1;
                    }

                    if (
                        $buscaNumerica !== ''
                        && (
                            $telefone1 === $buscaNumerica
                            || $telefone2 === $buscaNumerica
                        )
                    ) {
                        return 2;
                    }

                    if (
                        str_starts_with(
                            $nome,
                            $buscaNormalizada
                        )
                    ) {
                        return 3;
                    }

                    return 4;
                }
            )
            ->values()
            ->map(
                fn ($cliente) => $this->formatarCliente($cliente)
            )
            ->toArray();
    }

    public function selecionarCliente(int $clienteId): void
    {
        $cliente = Cliente::with('pessoa')
            ->find($clienteId);

        if (!$cliente) {
            return;
        }

        $this->clienteSelecionadoId = $cliente->id;

        $this->clienteSelecionado = $this->formatarCliente(
            $cliente
        );

        $this->buscaCliente = '';
        $this->resultadosClientes = [];

        $this->removerVeiculo();
    }

    public function removerCliente(): void
    {
        $this->clienteSelecionadoId = null;
        $this->clienteSelecionado = null;

        $this->buscaCliente = '';
        $this->resultadosClientes = [];

        $this->removerVeiculo();
    }

    public function updatedBuscaVeiculo(): void
    {
        $busca = trim($this->buscaVeiculo);

        if (
            !$this->clienteSelecionadoId
            || mb_strlen($busca) < 2
        ) {
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

        $query = VeiculosCliente::query()
            ->with('veiculo.montadora')
            ->whereHas(
                'clientes',
                function ($query) {
                    $query->where(
                        'clientes.id',
                        $this->clienteSelecionadoId
                    );
                }
            );

        foreach ($termos as $termo) {
            $placa = strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $termo
                )
            );

            $query->where(
                function ($query) use (
                    $termo,
                    $placa
                ) {
                    $query
                        ->where(
                            'placa',
                            'like',
                            "%{$placa}%"
                        )
                        ->orWhereHas(
                            'veiculo',
                            function ($veiculoQuery) use ($termo) {
                                $veiculoQuery
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
                            }
                        );
                }
            );
        }

        $this->resultadosVeiculos = $query
            ->orderBy('placa')
            ->get()
            ->map(
                fn ($veiculoCliente) => $this->formatarVeiculo(
                    $veiculoCliente
                )
            )
            ->toArray();
    }

    public function selecionarVeiculo(
        int $veiculoClienteId
    ): void {
        if (!$this->clienteSelecionadoId) {
            return;
        }

        $veiculoCliente = VeiculosCliente::query()
            ->with('veiculo.montadora')
            ->where('id', $veiculoClienteId)
            ->whereHas(
                'clientes',
                function ($query) {
                    $query->where(
                        'clientes.id',
                        $this->clienteSelecionadoId
                    );
                }
            )
            ->first();

        if (!$veiculoCliente) {
            return;
        }

        $this->veiculoSelecionadoId = $veiculoCliente->id;

        $this->veiculoSelecionado = $this->formatarVeiculo(
            $veiculoCliente
        );

        $this->buscaVeiculo = '';
        $this->resultadosVeiculos = [];
    }

    public function removerVeiculo(): void
    {
        $this->veiculoSelecionadoId = null;
        $this->veiculoSelecionado = null;

        $this->buscaVeiculo = '';
        $this->resultadosVeiculos = [];
    }

    private function formatarCliente(
        Cliente $cliente
    ): array {
        return [
            'id' => $cliente->id,

            'nome' =>
                $cliente->pessoa?->nome
                ?? 'Cliente sem nome',

            'cpf' =>
                $cliente->pessoa?->cpf,

            'telefone' =>
                $cliente->pessoa?->telefone_1
                ?: $cliente->pessoa?->telefone_2,
        ];
    }

    private function formatarVeiculo(
        VeiculosCliente $veiculoCliente
    ): array {
        return [
            'id' => $veiculoCliente->id,

            'placa' =>
                $veiculoCliente->placa,

            'ano' =>
                $veiculoCliente->ano,

            'cor' =>
                $veiculoCliente->cor,

            'veiculo' =>
                $veiculoCliente->veiculo?->nome
                ?? 'Veículo não informado',

            'montadora' =>
                $veiculoCliente
                    ->veiculo
                    ?->montadora
                    ?->nome,
        ];
    }

    public function render()
    {
        return view(
            'livewire.nota.seletor-cliente-veiculo'
        );
    }
}
