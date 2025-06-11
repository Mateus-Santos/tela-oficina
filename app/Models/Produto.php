<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_barras',
        'montadora',
        'nome',
        'ano_modelo',
        'veiculos',
        'motor',
        'descricao',
        'marcas',
        'departamentos',
        'valvula',
        'quantidade',
        'estoque_minimo',
        'preco_uni',
        'img',
        'codigo_fabricante',
        'localizacao',
        'unidade_medida',
        'status',
        'fornecedor_id',
    ];

    protected $casts = [
        'montadora'     => 'array',
        'veiculos'      => 'array',
        'marcas'        => 'array',
        'departamentos' => 'array',
        'valvula'       => 'array',
        'status'        => 'boolean',
        'quantidade'    => 'integer',
        'estoque_minimo'=> 'integer',
        'preco_uni'     => 'float',
        'fornecedor_id' => 'integer',
        'ano_modelo'    => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($produto) {
            // Gera código de barras apenas se não fornecido
            if (empty($produto->codigo_barras)) {
                // Gera base com prefixo 200 + timestamp (garante unicidade)
                $base = '200' . substr(time(), -9); // 12 dígitos
                $produto->codigo_barras = $base . self::calcularChecksumEAN13($base);
            }
        });
    }

    public static function calcularChecksumEAN13(string $codigo): string
    {
        $sum = 0;
        foreach (str_split($codigo) as $i => $digit) {
            $sum += intval($digit) * ($i % 2 === 0 ? 1 : 3);
        }
        $mod = $sum % 10;
        return (string) ($mod === 0 ? 0 : 10 - $mod);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function produtoVendas()
    {
        return $this->hasMany(ProdutoVenda::class);
    }
}
