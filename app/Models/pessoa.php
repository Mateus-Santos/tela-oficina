<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'data_nascimento',
        'telefone_1',
        'telefone_2'
    ];

    protected $hidden = [
        'cpf',
        'rg',
    ];

}
