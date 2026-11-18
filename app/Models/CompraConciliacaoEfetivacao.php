<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Registro imutavel da decisao, separado de pagamentos e movimentacao de caixa. */
class CompraConciliacaoEfetivacao extends Model
{
    protected $table = 'compra_conciliacao_efetivacoes';

    protected $fillable = [
        'compra_id', 'compra_pre_conciliacao_id', 'usuario_id', 'operacao_uuid',
        'tratamento', 'status', 'valor', 'conta_pagar_id', 'valor_conta_antes',
        'valor_conta_depois', 'documento_confirmacao', 'justificativa',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_conta_antes' => 'decimal:2',
        'valor_conta_depois' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function preConciliacao(): BelongsTo
    {
        return $this->belongsTo(CompraPreConciliacao::class, 'compra_pre_conciliacao_id');
    }
}
