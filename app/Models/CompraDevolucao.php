<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraDevolucao extends Model
{
    protected $table = 'compra_devolucoes';

    protected $fillable = [
        'compra_id', 'usuario_id', 'operacao_uuid', 'referencia_documento',
        'chave_nfe', 'data_documento', 'motivo', 'status',
    ];

    protected $casts = ['data_documento' => 'date'];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraDevolucaoItem::class, 'compra_devolucao_id');
    }
}
