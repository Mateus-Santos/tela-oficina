<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Models\CompraPreConciliacao;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * D03A: registra somente uma PROPOSTA em rascunho.
 * Nao altera contas a pagar, parcelas, pagamentos nem movimentacoes de estoque.
 */
class SalvarPreConciliacao
{
    public function execute(Compra $compra, array $dados, int $usuarioId): CompraPreConciliacao
    {
        return DB::transaction(function () use ($compra, $dados, $usuarioId): CompraPreConciliacao {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException('Somente compras aprovadas admitem pre-conciliacao.');
            }
            if (!$compra->possuiDivergenciaConferencia() && !$compra->devolucoes()->exists()) {
                throw new InvalidArgumentException('Compra sem divergencia ou devolucao registrada.');
            }
            $tipo = (string) $dados['tratamento_proposto'];
            if (!in_array($tipo, ['abatimento', 'credito', 'reembolso', 'sem_ajuste'], true)) {
                throw new InvalidArgumentException('Tratamento proposto invalido.');
            }
            $valorCentavos = (int) round(((float) $dados['valor_proposto']) * 100);
            $valorCompraCentavos = (int) round(((float) $compra->valor_total) * 100);
            if ($valorCentavos < 0 || $valorCentavos > $valorCompraCentavos) {
                throw new InvalidArgumentException('Valor proposto deve estar entre zero e o total original da compra.');
            }
            if (($tipo === 'sem_ajuste') !== ($valorCentavos === 0)) {
                throw new InvalidArgumentException('Sem ajuste exige valor zero; os demais tratamentos exigem valor positivo.');
            }
            $justificativa = trim((string) $dados['justificativa']);
            if (mb_strlen($justificativa) < 10) {
                throw new InvalidArgumentException('Justificativa deve ter pelo menos dez caracteres.');
            }
            $registro = CompraPreConciliacao::query()
                ->where('compra_id', $compra->id)->lockForUpdate()->first();
            if ($registro && $registro->status !== 'rascunho') {
                throw new InvalidArgumentException('Nao e possivel modificar conciliacao fora de rascunho.');
            }
            if (!$registro) {
                $registro = new CompraPreConciliacao();
                $registro->compra_id = $compra->id;
            }
            $registro->usuario_id = $usuarioId;
            $registro->status = 'rascunho';
            $registro->tratamento_proposto = $tipo;
            $registro->valor_proposto = number_format($valorCentavos / 100, 2, '.', '');
            $registro->referencia_documento = trim((string) ($dados['referencia_documento'] ?? '')) ?: null;
            $registro->justificativa = $justificativa;
            $registro->save();

            return $registro;
        }, 3);
    }
}
