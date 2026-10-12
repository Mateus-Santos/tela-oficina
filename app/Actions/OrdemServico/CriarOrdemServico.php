<?php

namespace App\Actions\OrdemServico;

use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\OrdemServicoEtapaHistorico;
use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CriarOrdemServico
{
    public function execute(
        array $dados,
        ?int $userId = null
    ): OrdemServico {
        return DB::transaction(
            function () use (
                $dados,
                $userId
            ) {
                $veiculoCliente =
                    VeiculosCliente::query()
                        ->where(
                            'id',
                            $dados['veiculo_cliente_id']
                        )
                        ->whereHas(
                            'clientes',
                            function ($query) use ($dados) {
                                $query->where(
                                    'clientes.id',
                                    $dados['cliente_id']
                                );
                            }
                        )
                        ->first();

                if (! $veiculoCliente) {
                    throw ValidationException::withMessages([
                        'veiculo_cliente_id' => 'O veículo selecionado não está vinculado ao cliente informado.',
                    ]);
                }

                $etapaInicial =
                    $this->obterEtapaInicial();

                $valor =
                    $this->normalizarValor(
                        $dados['valor']
                    );

                $ordemServico =
                    OrdemServico::create([
                        'data_abertura' => now(),

                        'cliente_id' => $dados['cliente_id'],

                        'veiculo_cliente_id' => $dados['veiculo_cliente_id'],

                        'setor_servico_id' => $dados['setor_servico_id'],

                        'etapa_id' => $etapaInicial->id,

                        'descricao' => $dados['descricao'] ?? null,

                        'valor' => $valor,

                        'status' => 'aberta',
                    ]);

                OrdemServicoEtapaHistorico::create([
                    'ordem_servico_id' => $ordemServico->id,

                    'etapa_origem_id' => null,

                    'etapa_destino_id' => $etapaInicial->id,

                    'etapa_origem_nome' => null,

                    'etapa_destino_nome' => $etapaInicial->nome,

                    'user_id' => $userId,

                    'motivo' => 'Etapa inicial definida na criação da Ordem de Serviço.',
                ]);

                return $ordemServico->fresh([
                    'setorServico',
                    'etapa',
                    'historicoEtapas',
                ]);
            }
        );
    }

    private function obterEtapaInicial(): Etapa
    {
        $etapas =
            Etapa::query()
                ->paraOrdemServico()
                ->where(
                    'ativo',
                    true
                )
                ->where(
                    'tipo',
                    'inicial'
                )
                ->orderBy('ordem')
                ->get();

        if ($etapas->count() !== 1) {
            throw new InvalidArgumentException(
                'O fluxo de Ordens de Serviço deve possuir exatamente uma etapa inicial ativa.'
            );
        }

        return $etapas->first();
    }

    private function normalizarValor(
        mixed $valor
    ): float {
        $texto =
            trim(
                (string) $valor
            );

        if (str_contains($texto, ',')) {
            $texto =
                str_replace(
                    '.',
                    '',
                    $texto
                );

            $texto =
                str_replace(
                    ',',
                    '.',
                    $texto
                );
        }

        return round(
            (float) $texto,
            2
        );
    }
}
