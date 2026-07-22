<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pessoa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'cpf',
        'rg',
        'data_nascimento',
        'telefone_1',
        'telefone_2',
        'endereco_id'
    ];

    public function enderecos()
    {
        return $this->hasMany(Endereco::class, 'pessoa_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'pessoa_id');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }
}