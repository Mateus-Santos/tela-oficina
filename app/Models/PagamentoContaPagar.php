<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoContaPagar extends Model
{
    use HasFactory;

    protected $table = 'pagamentos_contas_pagar';

    protected $fillable = [
        'conta_pagar_id',
        'valor',
        'data_pagamento',
        'forma_pagamento',
        'observacoes',
        'estornado_em',
        'motivo_estorno',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
        'estornado_em' => 'datetime',
    ];

    public function contaPagar(): BelongsTo
    {
        return $this->belongsTo(ContaPagar::class);
    }

    public function estaEstornado(): bool
    {
        return $this->estornado_em !== null;
    }
}
