<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'pontos',
    ];


    public function user(): hasOne
    {
        return $this->hasOne(User::class);
    }

}
