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
    /** Entrada publica: nao permite O.S. persistida sem Nota. */
    public function execute(array $dados, ?int $userId = null): OrdemServico
    {
        return app(\App\Services\Nota\CriarOsComNota::class)
            ->execute($dados, $userId)['os'];
    }

    /**
     * Operacao interna: apenas dentro da transacao da Nota ja criada.
     * O chamador deve inserir o NotasItem antes do commit externo.
     */
    public function criarParaNota(\App\Models\Nota $nota, array $dados, ?int $userId = null): OrdemServico
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Criacao de O.S. vinculada exige transacao da Nota.');
        }
        $nota = \App\Models\Nota::query()->lockForUpdate()->findOrFail($nota->id);
        if ($nota->status !== 'Aberto') {
            throw ValidationException::withMessages(['nota' => 'Somente Nota aberta recebe O.S.']);
        }
        if ((int) $nota->cliente_id !== (int) ($dados['cliente_id'] ?? 0)
            || (int) $nota->veiculo_cliente_id !== (int) ($dados['veiculo_cliente_id'] ?? 0)) {
            throw ValidationException::withMessages(['nota' => 'Cliente ou veiculo da O.S. divergente da Nota.']);
        }
        $veiculo = VeiculosCliente::query()
            ->whereKey($dados['veiculo_cliente_id'])
            ->whereHas('clientes', fn ($q) => $q->where('clientes.id', $dados['cliente_id']))
            ->first();
        if (! $veiculo) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' => 'O veiculo nao pertence ao cliente informado.',
            ]);
        }
        $etapa = $this->obterEtapaInicial();
        $ordemServico = OrdemServico::create([
            'nota_id' => $nota->id,
            'data_abertura' => now(),
            'cliente_id' => $dados['cliente_id'],
            'veiculo_cliente_id' => $dados['veiculo_cliente_id'],
            'setor_servico_id' => $dados['setor_servico_id'],
            'etapa_id' => $etapa->id,
            'descricao' => $dados['descricao'] ?? null,
            'valor' => $this->normalizarValor($dados['valor']),
            'status' => 'aberta',
        ]);
        OrdemServicoEtapaHistorico::create([
            'ordem_servico_id' => $ordemServico->id,
            'etapa_origem_id' => null,
            'etapa_destino_id' => $etapa->id,
            'etapa_origem_nome' => null,
            'etapa_destino_nome' => $etapa->nome,
            'user_id' => $userId,
            'motivo' => 'Etapa inicial definida na criacao da Ordem de Servico.',
        ]);
        return $ordemServico->fresh(['nota', 'setorServico', 'etapa', 'historicoEtapas']);
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
