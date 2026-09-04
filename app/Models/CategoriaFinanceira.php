<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanceira extends Model
{
    use HasFactory;

    protected $table = 'categorias_financeiras';

    protected $fillable = [
        'nome',
        'tipo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function contasReceber(): HasMany
    {
        return $this->hasMany(
            ContaReceber::class,
            'categoria_financeira_id'
        );
    }
}
