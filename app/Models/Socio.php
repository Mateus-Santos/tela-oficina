<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Socio extends Model
{
    protected $table = 'socios';

    protected $fillable = ['nome', 'ativo', 'observacoes'];

    protected $casts = ['ativo' => 'boolean'];

    public function contas(): HasMany
    {
        return $this->hasMany(ContaPagarSocio::class);
    }

    public function recebiveis(): HasMany
    {
        return $this->hasMany(ContaReceberSocio::class);
    }

    public function retiradas(): HasMany
    {
        return $this->hasMany(RetiradaSocio::class);
    }

    public function retiradasAtivas(): HasMany
    {
        return $this->retiradas()->whereNull('estornado_em');
    }
}
