<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabalhoRealizado extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_trabalhos_realizados',
        'id_colaborador',
        'id_manutencao',
    ];

}
