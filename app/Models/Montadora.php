<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Montadora extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
    ];

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class);
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }
}
