<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoVeiculo extends Model
{
    protected $table = 'catalogo_veiculos';

    protected $guarded = [];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function catalogoMontadora(): BelongsTo
    {
        return $this->belongsTo(CatalogoMontadora::class);
    }

    protected $casts = [
        'ativo_fonte' => 'boolean',
        'primeira_coleta_em' => 'datetime',
        'ultima_coleta_em' => 'datetime',
    ];
}
