<?php

namespace App\Livewire\Nota;

use App\Actions\Clientes\CriarClienteRapido;
use App\Actions\VeiculosClientes\CriarVeiculoCliente;
use App\Models\Cliente;
use App\Models\Montadora;
use App\Models\Veiculo;
use App\Models\VeiculosCliente;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SeletorClienteVeiculo extends Component
{
    /*
     * =====================================================
     * CLIENTE
     * =====================================================
     */

    public string $buscaCliente = '';

    public array $resultadosClientes = [];

    public ?int $clienteSelecionadoId = null;

    public ?array $clienteSelecionado = null;


    /*
     * =====================================================
     * CADASTRO RÁPIDO DE CLIENTE
     * =====================================================
     */

    public bool $exibirCadastroClienteRapido = false;

    public string $clienteRapidoNome = '';

    public string $clienteRapidoTelefone = '';


    /*
     * =====================================================
     * VEÍCULO DO CLIENTE
     * =====================================================
     */

    public string $buscaVeiculo = '';

    public array $resultadosVeiculos = [];

    public ?int $veiculoSelecionadoId = null;

    public ?array $veiculoSelecionado = null;


    /*
     * =====================================================
     * CADASTRO RÁPIDO DE VEÍCULO
     * =====================================================
     */

    public bool $exibirCadastroVeiculoRapido = false;

    public ?int $veiculoRapidoMontadoraId = null;

    public ?int $veiculoRapidoVeiculoId = null;

    public string $veiculoRapidoPlaca = '';

    public ?int $veiculoRapidoAno = null;

    public string $veiculoRapidoCor = '';

    public array $montadorasDisponiveis = [];

    public array $veiculosDisponiveis = [];


    /*
     * =====================================================
     * MOUNT
     * =====================================================
     */

    public function mount(
        ?int $clienteSelecionadoId = null,
        ?int $veiculoSelecionadoId = null
    ): void {
        if ($clienteSelecionadoId) {
            $cliente =
                Cliente::with('pessoa')
                    ->find(
                        $clienteSelecionadoId
                    );

            if ($cliente) {
                $this->clienteSelecionadoId =
                    $cliente->id;

                $this->clienteSelecionado =
                    $this->formatarCliente(
                        $cliente
                    );
            }
        }

        if (
            $veiculoSelecionadoId
            && $this->clienteSelecionadoId
        ) {
            $veiculoCliente =
                VeiculosCliente::query()
                    ->with(
                        'veiculo.montadora'
                    )
                    ->where(
                        'id',
                        $veiculoSelecionadoId
                    )
                    ->whereHas(
                        'clientes',
                        function ($query) {
                            $query->where(
                                'clientes.id',
                                $this
                                    ->clienteSelecionadoId
                            );
                        }
                    )
                    ->first();

            if ($veiculoCliente) {
                $this->veiculoSelecionadoId =
                    $veiculoCliente->id;

                $this->veiculoSelecionado =
                    $this->formatarVeiculo(
                        $veiculoCliente
                    );
            }
        }
    }


    /*
     * =====================================================
     * BUSCA DE CLIENTE
     * =====================================================
     */

    public function updatedBuscaCliente(): void
    {
        $busca =
            trim(
                $this->buscaCliente
            );

        if (
            mb_strlen($busca)
            < 2
        ) {
            $this->resultadosClientes = [];

            return;
        }

        $termos =
            collect(
                preg_split(
                    '/\s+/',
                    $busca,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: []
            )
                ->map(
                    fn ($termo) =>
                        trim($termo)
                )
                ->filter()
                ->unique(
                    fn ($termo) =>
                        mb_strtolower(
                            $termo
                        )
                )
                ->values()
                ->all();

        $query =
            Cliente::query()
                ->with([
                    'pessoa:id,nome,cpf,telefone_1,telefone_2',
                ])
                ->select([
                    'id',
                    'pessoa_id',
                ]);

        foreach (
            $termos
            as $termo
        ) {
            $numerico =
                preg_replace(
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

                            if (
                                $numerico !== ''
                            ) {
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

        $buscaNormalizada =
            mb_strtolower(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $busca
                )
            );

        $buscaNumerica =
            preg_replace(
                '/\D/',
                '',
                $busca
            );

        $this->resultadosClientes =
            $query
                ->limit(30)
                ->get()
                ->sortBy(
                    function ($cliente) use (
                        $buscaNormalizada,
                        $buscaNumerica
                    ) {
                        $nome =
                            mb_strtolower(
                                trim(
                                    $cliente
                                        ->pessoa
                                        ?->nome
                                    ?? ''
                                )
                            );

                        $cpf =
                            preg_replace(
                                '/\D/',
                                '',
                                $cliente
                                    ->pessoa
                                    ?->cpf
                                ?? ''
                            );

                        $telefone1 =
                            preg_replace(
                                '/\D/',
                                '',
                                $cliente
                                    ->pessoa
                                    ?->telefone_1
                                ?? ''
                            );

                        $telefone2 =
                            preg_replace(
                                '/\D/',
                                '',
                                $cliente
                                    ->pessoa
                                    ?->telefone_2
                                ?? ''
                            );

                        if (
                            $buscaNumerica !== ''
                            && $cpf
                                === $buscaNumerica
                        ) {
                            return 0;
                        }

                        if (
                            $nome
                            === $buscaNormalizada
                        ) {
                            return 1;
                        }

                        if (
                            $buscaNumerica !== ''
                            && (
                                $telefone1
                                    === $buscaNumerica
                                || $telefone2
                                    === $buscaNumerica
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
                    fn ($cliente) =>
                        $this->formatarCliente(
                            $cliente
                        )
                )
                ->toArray();
    }


    /*
     * =====================================================
     * SELEÇÃO DE CLIENTE
     * =====================================================
     */

    public function selecionarCliente(
        int $clienteId
    ): void {
        $cliente =
            Cliente::with('pessoa')
                ->find(
                    $clienteId
                );

        if (!$cliente) {
            return;
        }

        $this->clienteSelecionadoId =
            $cliente->id;

        $this->clienteSelecionado =
            $this->formatarCliente(
                $cliente
            );

        $this->buscaCliente = '';

        $this->resultadosClientes = [];

        $this->exibirCadastroClienteRapido =
            false;

        $this->removerVeiculo();
    }


    public function removerCliente(): void
    {
        $this->clienteSelecionadoId =
            null;

        $this->clienteSelecionado =
            null;

        $this->buscaCliente = '';

        $this->resultadosClientes = [];

        $this->fecharCadastroClienteRapido();

        $this->removerVeiculo();
    }


    /*
     * =====================================================
     * CADASTRO RÁPIDO DE CLIENTE
     * =====================================================
     */

    public function abrirCadastroClienteRapido(): void
    {
        $this->resetValidation();

        $this->buscaCliente = '';

        $this->resultadosClientes = [];

        $this->exibirCadastroClienteRapido =
            true;
    }


    public function fecharCadastroClienteRapido(): void
    {
        $this->exibirCadastroClienteRapido =
            false;

        $this->clienteRapidoNome = '';

        $this->clienteRapidoTelefone = '';

        $this->resetValidation([
            'clienteRapidoNome',
            'clienteRapidoTelefone',
        ]);
    }


    public function criarClienteRapido(): void
    {
        $telefone =
            preg_replace(
                '/\D/',
                '',
                $this
                    ->clienteRapidoTelefone
            );

        $this->clienteRapidoTelefone =
            $telefone;

        $dados =
            $this->validate(
                [
                    'clienteRapidoNome' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'clienteRapidoTelefone' => [
                        'required',
                        'string',
                        'min:10',
                        'max:11',
                    ],
                ],
                [
                    'clienteRapidoNome.required' =>
                        'Informe o nome do cliente.',

                    'clienteRapidoNome.max' =>
                        'O nome não pode ultrapassar 255 caracteres.',

                    'clienteRapidoTelefone.required' =>
                        'Informe o telefone principal.',

                    'clienteRapidoTelefone.min' =>
                        'O telefone deve possuir pelo menos 10 dígitos.',

                    'clienteRapidoTelefone.max' =>
                        'O telefone não pode possuir mais de 11 dígitos.',
                ]
            );

        $cliente =
            app(
                CriarClienteRapido::class
            )->execute([
                'nome' =>
                    $dados[
                        'clienteRapidoNome'
                    ],

                'telefone_1' =>
                    $dados[
                        'clienteRapidoTelefone'
                    ],
            ]);

        $this->clienteSelecionadoId =
            $cliente->id;

        $this->clienteSelecionado =
            $this->formatarCliente(
                $cliente
            );

        $this->buscaCliente = '';

        $this->resultadosClientes = [];

        $this->fecharCadastroClienteRapido();

        $this->removerVeiculo();
    }


    /*
     * =====================================================
     * BUSCA DE VEÍCULO
     * =====================================================
     */

    public function updatedBuscaVeiculo(): void
    {
        $busca =
            trim(
                $this->buscaVeiculo
            );

        if (
            !$this->clienteSelecionadoId
            || mb_strlen($busca)
                < 2
        ) {
            $this->resultadosVeiculos = [];

            return;
        }

        $termos =
            collect(
                preg_split(
                    '/\s+/',
                    $busca,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: []
            )
                ->map(
                    fn ($termo) =>
                        trim($termo)
                )
                ->filter()
                ->unique(
                    fn ($termo) =>
                        mb_strtolower(
                            $termo
                        )
                )
                ->values()
                ->all();

        $query =
            VeiculosCliente::query()
                ->with(
                    'veiculo.montadora'
                )
                ->whereHas(
                    'clientes',
                    function ($query) {
                        $query->where(
                            'clientes.id',
                            $this
                                ->clienteSelecionadoId
                        );
                    }
                );

        foreach (
            $termos
            as $termo
        ) {
            $placa =
                strtoupper(
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
                            function (
                                $veiculoQuery
                            ) use ($termo) {
                                $veiculoQuery
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
                                            $montadoraQuery
                                                ->where(
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

        $this->resultadosVeiculos =
            $query
                ->orderBy('placa')
                ->limit(30)
                ->get()
                ->map(
                    fn ($veiculoCliente) =>
                        $this->formatarVeiculo(
                            $veiculoCliente
                        )
                )
                ->toArray();
    }


    /*
     * =====================================================
     * SELEÇÃO DE VEÍCULO
     * =====================================================
     */

    public function selecionarVeiculo(
        int $veiculoClienteId
    ): void {
        if (
            !$this->clienteSelecionadoId
        ) {
            return;
        }

        $veiculoCliente =
            VeiculosCliente::query()
                ->with(
                    'veiculo.montadora'
                )
                ->where(
                    'id',
                    $veiculoClienteId
                )
                ->whereHas(
                    'clientes',
                    function ($query) {
                        $query->where(
                            'clientes.id',
                            $this
                                ->clienteSelecionadoId
                        );
                    }
                )
                ->first();

        if (!$veiculoCliente) {
            return;
        }

        $this->veiculoSelecionadoId =
            $veiculoCliente->id;

        $this->veiculoSelecionado =
            $this->formatarVeiculo(
                $veiculoCliente
            );

        $this->buscaVeiculo = '';

        $this->resultadosVeiculos = [];

        $this->fecharCadastroVeiculoRapido();
    }


    public function removerVeiculo(): void
    {
        $this->veiculoSelecionadoId =
            null;

        $this->veiculoSelecionado =
            null;

        $this->buscaVeiculo = '';

        $this->resultadosVeiculos = [];

        $this->fecharCadastroVeiculoRapido();
    }


    /*
     * =====================================================
     * CADASTRO RÁPIDO DE VEÍCULO
     * =====================================================
     */

    public function abrirCadastroVeiculoRapido(): void
    {
        if (
            !$this->clienteSelecionadoId
        ) {
            return;
        }

        $this->resetValidation();

        $this->buscaVeiculo = '';

        $this->resultadosVeiculos = [];

        $this->carregarMontadoras();

        $this->exibirCadastroVeiculoRapido =
            true;
    }


    public function fecharCadastroVeiculoRapido(): void
    {
        $this->exibirCadastroVeiculoRapido =
            false;

        $this->veiculoRapidoMontadoraId =
            null;

        $this->veiculoRapidoVeiculoId =
            null;

        $this->veiculoRapidoPlaca = '';

        $this->veiculoRapidoAno =
            null;

        $this->veiculoRapidoCor = '';

        $this->veiculosDisponiveis = [];

        $this->resetValidation([
            'veiculoRapidoMontadoraId',
            'veiculoRapidoVeiculoId',
            'veiculoRapidoPlaca',
            'veiculoRapidoAno',
            'veiculoRapidoCor',
        ]);
    }


    public function updatedVeiculoRapidoMontadoraId(
        $valor
    ): void {
        $this->veiculoRapidoVeiculoId =
            null;

        $this->veiculosDisponiveis =
            [];

        if (!$valor) {
            return;
        }

        $this->veiculosDisponiveis =
            Veiculo::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->where(
                    'montadora_id',
                    $valor
                )
                ->orderBy('nome')
                ->get()
                ->map(
                    fn ($veiculo) => [
                        'id' =>
                            $veiculo->id,

                        'nome' =>
                            $veiculo->nome,
                    ]
                )
                ->toArray();
    }


    public function criarVeiculoRapido(): void
    {
        if (
            !$this->clienteSelecionadoId
        ) {
            return;
        }

        $this->veiculoRapidoPlaca =
            strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $this
                        ->veiculoRapidoPlaca
                )
            );

        $this->veiculoRapidoCor =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $this
                        ->veiculoRapidoCor
                )
            );

        $dados =
            $this->validate(
                [
                    'veiculoRapidoMontadoraId' => [
                        'required',
                        'integer',
                        'exists:montadoras,id',
                    ],

                    'veiculoRapidoVeiculoId' => [
                        'required',
                        'integer',

                        Rule::exists(
                            'veiculos',
                            'id'
                        )->where(
                            function ($query) {
                                $query->where(
                                    'montadora_id',
                                    $this
                                        ->veiculoRapidoMontadoraId
                                );
                            }
                        ),
                    ],

                    'veiculoRapidoPlaca' => [
                        'required',
                        'string',
                        'max:7',

                        'regex:/^[A-Z]{3}[0-9]{4}$|^[A-Z]{3}[0-9][A-Z][0-9]{2}$/',

                        'unique:veiculos_clientes,placa',
                    ],

                    'veiculoRapidoAno' => [
                        'required',
                        'integer',
                        'min:1900',
                        'max:' . (
                            date('Y')
                            + 1
                        ),
                    ],

                    'veiculoRapidoCor' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                ],
                [
                    'veiculoRapidoMontadoraId.required' =>
                        'Selecione a montadora.',

                    'veiculoRapidoMontadoraId.exists' =>
                        'A montadora selecionada não existe.',

                    'veiculoRapidoVeiculoId.required' =>
                        'Selecione o modelo do veículo.',

                    'veiculoRapidoVeiculoId.exists' =>
                        'O modelo selecionado não pertence à montadora informada.',

                    'veiculoRapidoPlaca.required' =>
                        'Informe a placa do veículo.',

                    'veiculoRapidoPlaca.regex' =>
                        'Informe uma placa válida. Exemplos: ABC1234 ou ABC1D23.',

                    'veiculoRapidoPlaca.unique' =>
                        'Já existe um veículo cadastrado com esta placa.',

                    'veiculoRapidoAno.required' =>
                        'Informe o ano do veículo.',

                    'veiculoRapidoAno.min' =>
                        'Informe um ano válido.',

                    'veiculoRapidoAno.max' =>
                        'O ano informado não pode ser superior ao próximo ano.',

                    'veiculoRapidoCor.max' =>
                        'A cor não pode ultrapassar 20 caracteres.',
                ]
            );

        $veiculoCliente =
            app(
                CriarVeiculoCliente::class
            )->execute(
                [
                    'placa' =>
                        $dados[
                            'veiculoRapidoPlaca'
                        ],

                    'ano' =>
                        $dados[
                            'veiculoRapidoAno'
                        ],

                    'cor' =>
                        $dados[
                            'veiculoRapidoCor'
                        ] ?: null,

                    'veiculo_id' =>
                        $dados[
                            'veiculoRapidoVeiculoId'
                        ],

                    'clientes' => [
                        $this
                            ->clienteSelecionadoId,
                    ],
                ],
                auth()->user()
            );

        $this->veiculoSelecionadoId =
            $veiculoCliente->id;

        $this->veiculoSelecionado =
            $this->formatarVeiculo(
                $veiculoCliente
            );

        $this->buscaVeiculo = '';

        $this->resultadosVeiculos = [];

        $this->fecharCadastroVeiculoRapido();
    }


    /*
     * =====================================================
     * DADOS AUXILIARES
     * =====================================================
     */

    private function carregarMontadoras(): void
    {
        if (
            $this->montadorasDisponiveis
            !== []
        ) {
            return;
        }

        $this->montadorasDisponiveis =
            Montadora::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->get()
                ->map(
                    fn ($montadora) => [
                        'id' =>
                            $montadora->id,

                        'nome' =>
                            $montadora->nome,
                    ]
                )
                ->toArray();
    }


    private function formatarCliente(
        Cliente $cliente
    ): array {
        return [
            'id' =>
                $cliente->id,

            'nome' =>
                $cliente
                    ->pessoa
                    ?->nome
                ?? 'Cliente sem nome',

            'cpf' =>
                $cliente
                    ->pessoa
                    ?->cpf,

            'telefone' =>
                $cliente
                    ->pessoa
                    ?->telefone_1
                ?: $cliente
                    ->pessoa
                    ?->telefone_2,
        ];
    }


    private function formatarVeiculo(
        VeiculosCliente $veiculoCliente
    ): array {
        return [
            'id' =>
                $veiculoCliente->id,

            'placa' =>
                $veiculoCliente->placa,

            'ano' =>
                $veiculoCliente->ano,

            'cor' =>
                $veiculoCliente->cor,

            'veiculo' =>
                $veiculoCliente
                    ->veiculo
                    ?->nome
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
