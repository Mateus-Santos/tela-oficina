<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MarketingClienteAcesso extends Model
{
    protected $table = 'marketing_cliente_acessos';

    protected $fillable = ['cliente_id', 'portal_inicio', 'solicitacoes', 'atualizado_por'];

    protected $casts = [
        'portal_inicio' => 'boolean',
        'solicitacoes' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
