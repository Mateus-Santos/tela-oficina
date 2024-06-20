<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    use HasFactory;

    protected $table = 'venda';

    protected $fillable = [
        'valor_total',
        'data_venda',
        'quantidade',
        'valor_uni',
        'desconto',
        'juros',
        'valor_pagto',
        'data_venc',
        'data_pagto',
    ];
}
