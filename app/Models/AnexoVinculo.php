<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnexoVinculo extends Model
{
    use HasFactory;

    protected $table = 'anexos_vinculos';

    protected $fillable = [
        'anexo_id',
        'vinculavel_type',
        'vinculavel_id',
        'tipo',
        'observacoes',
    ];

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }

    public function vinculavel(): MorphTo
    {
        return $this->morphTo();
    }
}
