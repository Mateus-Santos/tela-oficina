<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peca extends Model
{
    use HasFactory;

    protected $table = 'peca';

    protected $fillable = [
        'id_peca',
        'montadora',
        'nome',
        'veiculos',
        'motor',
        'descricao_peca',
        'marcas',
        'departamentos',
        'produtos',
        'vulvula',
        'quantidade',
        'anos'
    ];
    
}
