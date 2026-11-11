<?php

namespace App\Http\Controllers\Marketing;

use App\Actions\Notas\CriarNota;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ConverterLeadEmNotaRequest;
use App\Models\Cliente;
use App\Models\MarketingLead;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConverterLeadEmNotaController extends Controller
{
    public function __invoke(
        ConverterLeadEmNotaRequest $request,
        MarketingLead $lead,
        CriarNota $criador
    ): RedirectResponse {
        abort_unless(config('marketing.conversao_nota_habilitada', false), 404);

        $dados = $request->validated();

        DB::transaction(function () use ($lead, $dados, $request, $criador): void {
            // Impede duas conversões concorrentes da mesma solicitação.
            $registro = MarketingLead::query()->lockForUpdate()->findOrFail($lead->id);

            if ($registro->nota_id !== null || in_array($registro->status, ['descartado', 'concluido'], true)) {
                throw ValidationException::withMessages([
                    'conversao' => 'Esta solicitação já foi convertida ou não está disponível para conversão.',
                ]);
            }

            // A conversao nao pode trocar silenciosamente um vinculo previamente conferido.
            if ($registro->cliente_id !== null
                && (int) $registro->cliente_id !== (int) $dados['cliente_id']) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Solicitação vinculada a outro cliente. Confira o vínculo antes de converter.',
                ]);
            }

            if (!Cliente::query()->whereKey($dados['cliente_id'])->exists()) {
                throw ValidationException::withMessages(['cliente_id' => 'Cliente não encontrado.']);
            }

            $solicitados = $registro->itens()->get();
            $precos = $dados['precos'];

            if ($solicitados->isEmpty() || $solicitados->count() > 10
                || $solicitados->count() !== count($precos)
                || $solicitados->pluck('id')->sort()->values()->all()
                    !== collect(array_keys($precos))->map(static fn ($id) => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'precos' => 'Informe um preço para cada item original, sem acrescentar ou retirar itens.',
                ]);
            }

            $itensNota = [];
            $produtosUsados = [];
            $totalCentavos = 0;
            foreach ($solicitados as $item) {
                // Texto livre necessita identificação da peça na oficina.
                // Nunca fabricar produto ou associar referência por semelhança.
                $produto = $item->produto_id
                    ? Produto::query()->find($item->produto_id)
                    : null;

                if (!$produto || !$produto->status || (float) $produto->quantidade <= 0) {
                    throw ValidationException::withMessages([
                        'conversao' => 'Há item sem produto operacional ativo e disponível. Revise a solicitação antes de converter.',
                    ]);
                }

                if (isset($produtosUsados[$produto->id])) {
                    throw ValidationException::withMessages([
                        'conversao' => 'A nota não permite duas linhas para o mesmo produto.',
                    ]);
                }
                $produtosUsados[$produto->id] = true;

                if ($item->quantidade < 1 || $item->quantidade > 99) {
                    throw ValidationException::withMessages(['conversao' => 'Quantidade inválida na solicitação.']);
                }

                $totalCentavos += (int) round(((float) $precos[$item->id]) * 100) * (int) $item->quantidade;
                if ($totalCentavos > 9999999999) {
                    throw ValidationException::withMessages(['precos' => 'Total excede o limite permitido para notas.']);
                }

                $itensNota[] = [
                    'itemable_type' => Produto::class,
                    'itemable_id' => $produto->id,
                    'descricao' => $item->descricao,
                    'quantidade' => (int) $item->quantidade,
                    'valor_unitario' => (float) $precos[$item->id],
                    'desconto' => 0,
                ];
            }

            // A ação existente cria nota Venda/Aberto, sem finalizá-la.
            // Não baixa estoque e não emite conta a receber nesta operação.
            $nota = $criador->execute([
                'cliente_id' => (int) $dados['cliente_id'],
                'veiculo_cliente_id' => null,
                'itens' => $itensNota,
            ], $request->user()->id);

            $nota->update([
                'observacoes' => 'Origem: solicitação '.$registro->protocolo.'. Valores sujeitos à confirmação da oficina.',
            ]);

            $registro->forceFill([
                'cliente_id' => (int) $dados['cliente_id'],
                'nota_id' => $nota->id,
                'status' => 'em_atendimento',
            ])->save();
        });

        return redirect()
            ->route('marketing.leads.show', $lead)
            ->with('marketing_nota_sucesso', 'Nota aberta criada e vinculada à solicitação. Nenhuma venda foi finalizada.');
    }
}
