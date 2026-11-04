<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecebimentoContaReceberSocio extends Model
{
    protected $table = 'recebimentos_contas_receber_socios';

    protected $fillable = [
        'conta_receber_socio_id', 'parcela_conta_receber_socio_id',
        'valor', 'data_recebimento', 'meio_recebimento',
        'forma_pagamento_id', 'forma_pagamento_nome', 'observacoes',
        'estornado_em', 'motivo_estorno',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_recebimento' => 'date',
        'estornado_em' => 'datetime',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaReceberSocio::class, 'conta_receber_socio_id');
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(ParcelaContaReceberSocio::class, 'parcela_conta_receber_socio_id');
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }
}
