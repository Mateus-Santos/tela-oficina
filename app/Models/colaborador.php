<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Colaborador extends Model
{
    use HasFactory;

    protected $fillable = [
        'chave_pix',
        'conta_banco',
    ];

    public function user(): hasOne
    {
        return $this->hasOne(User::class);
    } 

    public function produtovenda(): hasMany
    {
        return $this->hasMany(ProdutoVenda::class);
    }
}
