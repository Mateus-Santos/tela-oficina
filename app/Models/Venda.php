<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_venda',
        'desconto',
        'juros',
        'data_venc',
    ];

    public function produtovenda(): hasMany
    {
        return $this->hasMany(ProdutoVenda::class);
    }

}
