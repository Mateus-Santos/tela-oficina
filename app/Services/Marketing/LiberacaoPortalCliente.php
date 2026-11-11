<?php

namespace App\Services\Marketing;

use App\Models\MarketingClienteAcesso;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class LiberacaoPortalCliente
{
    private const FLAGS = [
        'portal_inicio' => 'portal_inicio_habilitado',
        'solicitacoes' => 'portal_solicitacoes_habilitado',
    ];

    public function permitido(?User $usuario, string $recurso): bool
    {
        $flag = self::FLAGS[$recurso] ?? null;

        if (
            $flag === null
            || !config("marketing.{$flag}", false)
            || !$usuario?->isCliente()
            || !$usuario->status
        ) {
            return false;
        }

        $clienteId = $usuario->cliente?->id;

        // Fail-closed: um cliente sem grant, ou com migration pendente,
        // nunca recebe acesso pelo simples fato de a flag global estar ligada.
        if ($clienteId === null || !Schema::hasTable('marketing_cliente_acessos')) {
            return false;
        }

        return MarketingClienteAcesso::query()
            ->where('cliente_id', $clienteId)
            ->where($recurso, true)
            ->exists();
    }
}
