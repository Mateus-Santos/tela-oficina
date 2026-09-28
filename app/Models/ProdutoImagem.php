<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoImagem extends Model
{
    use HasFactory;

    protected $table = 'produto_imagens';

    protected $fillable = [
        'produto_id',
        'caminho',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
