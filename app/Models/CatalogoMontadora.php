<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoMontadora extends Model
{
    protected $table = 'catalogo_montadoras';

    protected $guarded = [];

    public function montadora(): BelongsTo
    {
        return $this->belongsTo(Montadora::class);
    }

    protected $casts = [
        'ativo_fonte' => 'boolean',
        'primeira_coleta_em' => 'datetime',
        'ultima_coleta_em' => 'datetime',
    ];
}
