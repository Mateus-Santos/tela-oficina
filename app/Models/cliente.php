<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'cliente';

    protected $fillable = [
        'id_cliente',
        'id_user',
        'pontos',
    ];


    public function user(): hasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }

    public function pecavenda(): hasMany
    {
        return $this->hasMany(PecaVenda::class, 'id', 'id_user');
    }
}
