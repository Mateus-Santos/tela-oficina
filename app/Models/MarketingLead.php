<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingLead extends Model
{
    public const STATUS_LABELS = [
        'novo' => 'Novo',
        'em_atendimento' => 'Em atendimento',
        'aguardando_cliente' => 'Aguardando cliente',
        'concluido' => 'Concluído',
        'descartado' => 'Descartado',
    ];

    protected $hidden = ['ip_criptografado'];

    protected $fillable = [
        'protocolo', 'nome', 'whatsapp', 'peca', 'veiculo', 'ano',
        'motor', 'detalhes', 'utm_source', 'utm_medium',
        'utm_campaign', 'utm_content', 'utm_term', 'gclid',
        'consentido_em', 'status', 'cidade', 'uf',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(MarketingLeadItem::class, 'marketing_lead_id')
            ->orderBy('ordem');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }

    protected function casts(): array
    {
        return ['consentido_em' => 'datetime'];
    }
}
