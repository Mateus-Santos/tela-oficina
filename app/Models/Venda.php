<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    use HasFactory;

    protected $table = 'venda';

    protected $fillable = [
        'data_venda',
        'desconto',
        'juros',
        'data_venc',
    ];

    public function PecaVenda(): beLongsTo
    {
        return $this->beLongsTo(PecaVenda::class, 'id', 'id_venda');
    }

}
