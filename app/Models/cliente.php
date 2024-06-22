<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'cliente';

    protected $fillable = [
        'id_cliente',
        'id_pessoa',
        'id_user',
        'pontos',
    ];

    public function pessoa()
    {
        return $this->hasOne(Pessoa::class, 'id_pessoa', 'id_pessoa');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }
}
