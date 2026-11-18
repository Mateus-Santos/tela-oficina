<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Support\Estoque\Quantidade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConferirCompra
{
    public function execute(Compra $compra, array $dados): Compra
    {
        return DB::transaction(function () use ($compra, $dados): Compra {
            $compra = Compra::query()->lockForUpdate()->with('itens')->findOrFail($compra->id);
            if (!$compra->estaEmConferencia()) {
                throw new InvalidArgumentException('Somente compras em conferência podem ter quantidades alteradas.');
            }
            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException('A compra não possui itens para conferência.');
            }
            $recebidos = $dados['itens'] ?? [];
            if (!is_array($recebidos) || count($recebidos) !== $compra->itens->count()) {
                throw new InvalidArgumentException('Informe exatamente todos os itens da compra.');
            }
            $itens = $compra->itens->keyBy('id');
            foreach (array_keys($recebidos) as $id) {
                if (!ctype_digit((string) $id) || !$itens->has((int) $id)) {
                    throw new InvalidArgumentException('Existe item que não pertence à compra.');
                }
            }

            // Validar o lote inteiro antes de qualquer UPDATE. Quantidades em milésimos.
            $preparados = [];
            foreach ($compra->itens as $item) {
                $linha = $recebidos[$item->id] ?? null;
                if (!is_array($linha) || !array_key_exists('quantidade_conferida', $linha)) {
                    throw new InvalidArgumentException("Quantidade recebida ausente no item #{$item->id}.");
                }
                $prevista = Quantidade::milessimos((string) $item->quantidade);
                $recebida = Quantidade::milessimos((string) $linha['quantidade_conferida']);
                $avariada = Quantidade::milessimos((string) ($linha['quantidade_avariada'] ?? '0'));
                if ($avariada > $recebida) {
                    throw new InvalidArgumentException("Avaria não pode superar o recebido no item #{$item->id}.");
                }
                // Excedentes não entram automaticamente no estoque.
                $aceita = array_key_exists('quantidade_aceita', $linha)
                    && $linha['quantidade_aceita'] !== null
                    && $linha['quantidade_aceita'] !== ''
                    ? Quantidade::milessimos((string) $linha['quantidade_aceita'])
                    : min($prevista, $recebida - $avariada);
                if ($aceita > $prevista || $aceita > $recebida - $avariada) {
                    throw new InvalidArgumentException("Quantidade aceita excede o permitido no item #{$item->id}.");
                }
                $diferente = $prevista !== $recebida || $avariada > 0 || $aceita !== $prevista;
                $observacao = trim((string) ($linha['observacao_divergencia'] ?? ''));
                if ($diferente && mb_strlen($observacao) < 5) {
                    throw new InvalidArgumentException("Justifique a divergência do item #{$item->id} (mínimo 5 caracteres).");
                }
                if (mb_strlen($observacao) > 2000) {
                    throw new InvalidArgumentException("Justificativa excede o limite no item #{$item->id}.");
                }
                $tipos = [];
                if ($recebida < $prevista) $tipos[] = 'falta';
                if ($recebida > $prevista) $tipos[] = 'excesso';
                if ($avariada > 0) $tipos[] = 'avaria';
                if ($aceita < min($prevista, $recebida - $avariada)) $tipos[] = 'recusa';
                $preparados[$item->id] = [
                    'recebida' => $recebida,
                    'avariada' => $avariada,
                    'aceita' => $aceita,
                    'observacao' => $observacao !== '' ? $observacao : null,
                    'classificacao' => count($tipos) > 1 ? 'mista' : ($tipos[0] ?? 'regular'),
                    'prevista' => $prevista,
                ];
            }

            foreach ($compra->itens as $item) {
                $c = $preparados[$item->id];
                $item->update([
                    'quantidade_conferida' => Quantidade::formatar($c['recebida']),
                    'quantidade_avariada' => Quantidade::formatar($c['avariada']),
                    'quantidade_aceita' => Quantidade::formatar($c['aceita']),
                    'observacao_divergencia' => $c['observacao'],
                ]);
                DB::table('compra_conferencia_historicos')->insert([
                    'compra_id' => $compra->id,
                    'compra_item_id' => $item->id,
                    'usuario_id' => auth()->id(),
                    'quantidade_nf' => Quantidade::formatar($c['prevista']),
                    'quantidade_recebida' => Quantidade::formatar($c['recebida']),
                    'quantidade_avariada' => Quantidade::formatar($c['avariada']),
                    'quantidade_aceita' => Quantidade::formatar($c['aceita']),
                    'classificacao' => $c['classificacao'],
                    'observacao' => $c['observacao'],
                    'created_at' => now(),
                ]);
            }
            return $compra->fresh('itens');
        }, 3);
    }
}
