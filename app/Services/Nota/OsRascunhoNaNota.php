<?php

namespace App\Services\Nota;

use App\Actions\OrdemServico\CriarOrdemServico;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\SetorServico;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * O.S. rapida: nao cria registro ate a Nota ser salva.
 * Chamador deve envolver a criacao da Nota / atualizacao em transacao.
 */
final class OsRascunhoNaNota
{
    public static function validar(
        array $item, ?int $clienteId, ?int $veiculoId, string $campo = 'itens'
    ): void {
        if (($item['itemable_type'] ?? null) !== OrdemServico::class
            || (int) ($item['itemable_id'] ?? 0) >= 0
            || (int) ($item['quantidade'] ?? 0) !== 1
            || !$clienteId || !$veiculoId
        ) {
            throw ValidationException::withMessages([
                $campo => 'A O.S. rapida exige cliente, veiculo, tipo O.S. e quantidade 1.',
            ]);
        }
        $setor = filter_var($item['os_nova_setor_servico_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$setor || $setor <= 0 || !SetorServico::query()->whereKey($setor)->exists()) {
            throw ValidationException::withMessages([
                $campo => 'Selecione um setor valido para a nova O.S.',
            ]);
        }
        if (empty(trim((string) ($item['descricao'] ?? '')))) {
            throw ValidationException::withMessages([
                $campo => 'Informe a descricao da O.S.',
            ]);
        }
        if (!is_numeric($item['valor_unitario'] ?? null)
            || (float) $item['valor_unitario'] < 0) {
            throw ValidationException::withMessages([
                $campo => 'Informe o valor da O.S.',
            ]);
        }
    }

    public static function criar(Nota $nota, array $item, ?int $usuarioId = null): OrdemServico
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('O.S. de rascunho exige transacao da Nota.');
        }
        if ($nota->status !== 'Aberto') {
            throw ValidationException::withMessages(['nota' => 'Nota encerrada nao aceita O.S.']);
        }
        self::validar($item, $nota->cliente_id, $nota->veiculo_cliente_id);
        return app(CriarOrdemServico::class)->criarParaNota($nota, [
            'cliente_id' => $nota->cliente_id,
            'veiculo_cliente_id' => $nota->veiculo_cliente_id,
            'setor_servico_id' => (int) $item['os_nova_setor_servico_id'],
            'descricao' => trim((string) $item['descricao']),
            'valor' => (float) $item['valor_unitario'],
        ], $usuarioId);
    }
}
