<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetiradaSocio extends Model
{
    protected $table = 'retiradas_socios';

    protected $fillable = [
        'socio_id', 'pagamento_conta_pagar_socio_id', 'tipo',
        'valor', 'data_retirada', 'observacoes', 'estornado_em', 'motivo_estorno',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_retirada' => 'date',
        'estornado_em' => 'datetime',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(PagamentoContaPagarSocio::class, 'pagamento_conta_pagar_socio_id');
    }
}
