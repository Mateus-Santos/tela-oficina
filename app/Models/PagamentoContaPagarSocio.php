<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PagamentoContaPagarSocio extends Model
{
    protected $table = 'pagamentos_contas_pagar_socios';

    protected $fillable = [
        'conta_pagar_socio_id', 'parcela_conta_pagar_socio_id',
        'valor', 'data_pagamento', 'origem', 'forma_pagamento_id',
        'observacoes', 'estornado_em', 'motivo_estorno',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
        'estornado_em' => 'datetime',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaPagarSocio::class, 'conta_pagar_socio_id');
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(ParcelaContaPagarSocio::class, 'parcela_conta_pagar_socio_id');
    }

    public function retirada(): HasOne
    {
        return $this->hasOne(RetiradaSocio::class, 'pagamento_conta_pagar_socio_id');
    }
}
