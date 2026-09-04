<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Veiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'montadora_id',
    ];

    public function montadora(): BelongsTo
    {
        return $this->belongsTo(Montadora::class);
    }

    public function veiculosCliente(): HasMany
    {
        return $this->hasMany(VeiculosCliente::class);
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'produtos_veiculos');
    }

}
