<?php

namespace App\Services\Nota;

use App\Actions\Notas\AtualizarNota;
use App\Actions\Notas\CriarNota;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;

/** O.S. avulsa sempre nasce dentro da Nota. Sem commit parcial. */
final class CriarOsComNota
{
    public function __construct(
        private CriarOrdemServico $criarOs,
        private CriarNota $criarNota,
        private AtualizarNota $atualizarNota,
    ) {}

    /** @return array{os: OrdemServico, nota: \App\Models\Nota} */
    public function execute(array $dados, ?int $usuarioId = null): array
    {
        return DB::transaction(function () use ($dados, $usuarioId): array {
            // Nota transitoriamente vazia SOMENTE dentro desta transacao.
            $nota = $this->criarNota->execute([
                'cliente_id' => $dados['cliente_id'],
                'veiculo_cliente_id' => $dados['veiculo_cliente_id'],
                'itens' => [],
            ], $usuarioId);

            $os = $this->criarOs->criarParaNota($nota, $dados, $usuarioId);
            $nota = $this->atualizarNota->execute($nota, [
                'cliente_id' => $os->cliente_id,
                'veiculo_cliente_id' => $os->veiculo_cliente_id,
                'itens' => [[
                    'itemable_type' => OrdemServico::class,
                    'itemable_id' => $os->id,
                    'descricao' => $os->descricao ?: 'Ordem de servico #' . $os->id,
                    'quantidade' => 1,
                    'valor_unitario' => $os->valor,
                    'desconto' => 0,
                ]],
            ]);

            return ['os' => $os, 'nota' => $nota];
        }, 3);
    }
}
