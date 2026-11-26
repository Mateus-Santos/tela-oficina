<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class NotasItem extends Model
{
    use HasFactory;

    protected $table = 'notas_itens';

    protected $fillable = [
        'nota_id',
        'itemable_type',
        'itemable_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'desconto',
        'valor_total',
        'garantia_dias',
        'garantia_inicio',
        'garantia_fim',
    ];


    /**
     * Nao aceita inserir ou transferir item O.S. para Nota diferente
     * daquela registrada na FK canonica. Operacoes em massa continuam
     * protegidas nos Actions/Controllers; nao sao eventos Eloquent.
     */
    protected static function booted(): void
    {
        $verificar = static function (self $item): void {
            if ($item->itemable_type !== OrdemServico::class) {
                return;
            }
            $os = OrdemServico::query()->findOrFail((int) $item->itemable_id);
            if ((int) $os->nota_id !== (int) $item->nota_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'itens' => 'O.S. vinculada a Nota divergente. Operacao bloqueada.',
                ]);
            }
        };
        static::creating($verificar);
        static::updating($verificar);
    }

    public function itemable()
    {
        return $this->morphTo();
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }

    public function getTipoFormatadoAttribute(): string
    {
        return match ($this->itemable_type) {
            \App\Models\OrdemServico::class, 'App\Models\OrdemServico' => 'Serviço',
            \App\Models\Produto::class, 'App\Models\Produto'           => 'Produto',
            default                                                     => 'Outro',
        };
    }
}