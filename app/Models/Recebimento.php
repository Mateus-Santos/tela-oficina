<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recebimento extends Model
{
    use HasFactory;

    protected $table = 'recebimentos';

    protected $fillable = [
        'conta_receber_id',
        'forma_pagamento_id',
        'valor',
        'data_pagamento',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'datetime',
    ];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(
            ContaReceber::class,
            'conta_receber_id'
        );
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(
            FormaPagamento::class,
            'forma_pagamento_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}
