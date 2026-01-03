<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeiculosClientes extends Model
{
    use HasFactory;

    protected $fillable = [
        'ano',
        'placa',
        'cor',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->BelongsTo(Veiculo::class);
    }
}