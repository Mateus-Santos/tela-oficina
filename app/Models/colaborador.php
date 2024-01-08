<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_colaborador',
        'id_pessoa',
        'id_user',
        'chave_pix',
        'conta_banco',
    ];

}
