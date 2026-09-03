<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'tipo',
        'arquivo',
        'nome_original',
        'mime_type',
        'tamanho',
        'anexavel_type',
        'anexavel_id',
        'observacoes',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    public function anexavel(): MorphTo
    {
        return $this->morphTo();
    }
}
