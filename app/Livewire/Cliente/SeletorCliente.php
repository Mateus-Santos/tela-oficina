<?php

namespace App\Livewire\Cliente;

use App\Models\Cliente;
use Livewire\Component;

class SeletorCliente extends Component
{
    public string $buscaCliente = '';

    public array $resultadosClientes = [];

    public array $clientesSelecionados = [];

    public function mount(array $clientesSelecionadosIds = []): void
    {
        if ($clientesSelecionadosIds === []) {
            return;
        }

        $this->clientesSelecionados = Cliente::query()
            ->with('pessoa')
            ->whereIn('id', $clientesSelecionadosIds)
            ->get()
            ->map(
                fn ($cliente) =>
                    $this->formatarCliente($cliente)
            )
            ->values()
            ->toArray();
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
                fn ($termo) =>
                    mb_strtolower($termo)
            )
            ->values()
            ->all();

        $idsSelecionados = array_map(
            'intval',
            array_column(
                $this->clientesSelecionados,
                'id'
            )
        );

        $query = Cliente::query()
            ->with([
                'pessoa:id,nome,cpf,telefone_1,telefone_2',
            ])
            ->select([
                'id',
                'pessoa_id',
            ]);

        foreach ($termos as $termo) {
            $termoNumerico = preg_replace(
                '/\D/',
                '',
                $termo
            );

            $query->whereHas(
                'pessoa',
                function ($pessoaQuery) use (
                    $termo,
                    $termoNumerico
                ) {
                    $pessoaQuery->where(
                        function ($query) use (
                            $termo,
                            $termoNumerico
                        ) {
                            $query->where(
                                'nome',
                                'like',
                                "%{$termo}%"
                            );

                            if ($termoNumerico !== '') {
                                $query
                                    ->orWhere(
                                        'cpf',
                                        'like',
                                        "%{$termoNumerico}%"
                                    )
                                    ->orWhere(
                                        'telefone_1',
                                        'like',
                                        "%{$termoNumerico}%"
                                    )
                                    ->orWhere(
                                        'telefone_2',
                                        'like',
                                        "%{$termoNumerico}%"
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
                    $pessoa = $cliente->pessoa;

                    $nome = mb_strtolower(
                        trim($pessoa?->nome ?? '')
                    );

                    $cpf = preg_replace(
                        '/\D/',
                        '',
                        $pessoa?->cpf ?? ''
                    );

                    $telefone1 = preg_replace(
                        '/\D/',
                        '',
                        $pessoa?->telefone_1 ?? ''
                    );

                    $telefone2 = preg_replace(
                        '/\D/',
                        '',
                        $pessoa?->telefone_2 ?? ''
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
                fn ($cliente) => array_merge(
                    $this->formatarCliente($cliente),
                    [
                        'selecionado' => in_array(
                            (int) $cliente->id,
                            $idsSelecionados,
                            true
                        ),
                    ]
                )
            )
            ->toArray();
    }

    public function selecionarCliente(int $clienteId): void
    {
        if (
            collect($this->clientesSelecionados)
                ->contains('id', $clienteId)
        ) {
            return;
        }

        $cliente = Cliente::with('pessoa')
            ->find($clienteId);

        if (!$cliente) {
            return;
        }

        $this->clientesSelecionados[] =
            $this->formatarCliente($cliente);

        $this->buscaCliente = '';
        $this->resultadosClientes = [];
    }

    public function removerCliente(int $clienteId): void
    {
        $this->clientesSelecionados = collect(
            $this->clientesSelecionados
        )
            ->reject(
                fn ($cliente) =>
                    (int) $cliente['id'] === $clienteId
            )
            ->values()
            ->toArray();

        if (trim($this->buscaCliente) !== '') {
            $this->updatedBuscaCliente();
        }
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

    public function render()
    {
        return view(
            'livewire.cliente.seletor-cliente'
        );
    }
}
