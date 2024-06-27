<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peca extends Model
{
    use HasFactory;

    protected $table = 'peca';

    protected $primaryKey = 'id_peca';

    protected $fillable = [
        'id_peca',
        'id_venda',
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
        'ano',
        'img',
        'codigo_fabricante',
        'valor_uni'
    ];

    public function PecaVenda(): beLongsTo
    {
        return $this->beLongsTo(PecaVenda::class, 'id', 'id_peca');
    }
    
}
