<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraPreConciliacao extends Model
{
    protected $table = 'compra_pre_conciliacoes';

    protected $fillable = [
        'compra_id', 'usuario_id', 'status', 'tratamento_proposto',
        'valor_proposto', 'referencia_documento', 'justificativa',
    ];

    protected $casts = ['valor_proposto' => 'decimal:2'];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
