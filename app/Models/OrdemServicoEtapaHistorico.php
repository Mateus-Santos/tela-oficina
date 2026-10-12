<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoEtapaHistorico extends Model
{
    use HasFactory;

    protected $table =
        'ordem_servico_etapa_historicos';

    protected $fillable = [
        'ordem_servico_id',
        'etapa_origem_id',
        'etapa_destino_id',
        'etapa_origem_nome',
        'etapa_destino_nome',
        'user_id',
        'motivo',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(
            OrdemServico::class,
            'ordem_servico_id'
        );
    }

    public function etapaOrigem(): BelongsTo
    {
        return $this->belongsTo(
            Etapa::class,
            'etapa_origem_id'
        );
    }

    public function etapaDestino(): BelongsTo
    {
        return $this->belongsTo(
            Etapa::class,
            'etapa_destino_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
