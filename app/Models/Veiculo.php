<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
    ];

    public function montadora(): BelongsTo
    {
        return $this->belongsTo(Montadora::class);
    }

    public function VeiculosClientes(): HasMany
    {
        return $this->hasMany(VeiculosClientes::class);
    }

    public function produtos()
    {
        return $this->belongsToMany(Produto::class, 'produtos_veiculos');
    }

}
