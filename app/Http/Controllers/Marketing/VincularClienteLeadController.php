<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\VincularClienteLeadRequest;
use App\Models\MarketingLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class VincularClienteLeadController extends Controller
{
    public function vincular(VincularClienteLeadRequest $request, MarketingLead $lead): RedirectResponse
    {
        $dados = $request->validated();

        DB::transaction(static function () use ($lead, $dados): void {
            // A trava impede que dois administradores associem simultaneamente
            // a mesma solicitacao a clientes diferentes.
            $registro = MarketingLead::query()->lockForUpdate()->findOrFail($lead->id);
            self::verificarDisponibilidade($registro, $dados['protocolo_confirmacao']);

            if ($registro->cliente_id !== null) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Solicitação já vinculada. Desvincule primeiro após nova conferência.',
                ]);
            }

            // O cliente foi escolhido e conferido por uma pessoa da oficina.
            // Nunca correlacionar registros por nome, WhatsApp ou e-mail.
            $registro->forceFill(['cliente_id' => (int) $dados['cliente_id']])->save();
        });

        // Trilha operacional sem telefone, nome, CPF ou dados de campanha.
        Log::notice('marketing.lead_cliente_vinculado', [
            'lead_id' => $lead->id,
            'cliente_id' => (int) $dados['cliente_id'],
            'administrador_id' => $request->user()->id,
        ]);

        return redirect()->route('marketing.leads.show', $lead)
            ->with('marketing_vinculo_sucesso', 'Cliente vinculado após conferência administrativa.');
    }

    public function desvincular(Request $request, MarketingLead $lead): RedirectResponse
    {
        // Defesa adicional ao grupo de rotas auth/check.blocked/admin.
        abort_unless($request->user()?->isAdmin(), 403);

        $dados = $request->validate([
            'protocolo_confirmacao' => ['required', 'string', 'max:40'],
            'confirmar_desvinculacao' => ['required', 'accepted'],
        ]);

        $clienteAnterior = DB::transaction(static function () use ($lead, $dados): int {
            $registro = MarketingLead::query()->lockForUpdate()->findOrFail($lead->id);
            self::verificarDisponibilidade($registro, $dados['protocolo_confirmacao']);

            if ($registro->cliente_id === null) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Solicitação não possui cliente vinculado.',
                ]);
            }

            $clienteAnterior = (int) $registro->cliente_id;
            $registro->forceFill(['cliente_id' => null])->save();

            return $clienteAnterior;
        });

        Log::notice('marketing.lead_cliente_desvinculado', [
            'lead_id' => $lead->id,
            'cliente_id_anterior' => $clienteAnterior,
            'administrador_id' => $request->user()->id,
        ]);

        return redirect()->route('marketing.leads.show', $lead)
            ->with('marketing_vinculo_sucesso', 'Vínculo desfeito. A solicitação deixa de aparecer na área daquele cliente.');
    }

    private static function verificarDisponibilidade(MarketingLead $lead, string $protocolo): void
    {
        if (
            $lead->nota_id !== null
            || in_array($lead->status, ['descartado', 'concluido'], true)
        ) {
            throw ValidationException::withMessages([
                'vinculo' => 'Não é possível alterar o vínculo de uma solicitação convertida ou encerrada.',
            ]);
        }

        // A confirmacao do protocolo exige que o operador confira o pedido
        // concreto e reduz associacoes por engano entre abas abertas.
        if (!hash_equals((string) $lead->protocolo, $protocolo)) {
            throw ValidationException::withMessages([
                'protocolo_confirmacao' => 'Protocolo informado não corresponde à solicitação.',
            ]);
        }
    }
}
