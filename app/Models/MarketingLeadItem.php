<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLeadItem extends Model
{
    protected $table = 'marketing_lead_itens';

    protected $fillable = [
        'marketing_lead_id', 'produto_id', 'descricao', 'quantidade', 'ordem',
    ];

    protected function casts(): array
    {
        return ['quantidade' => 'integer', 'ordem' => 'integer'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MarketingLead::class, 'marketing_lead_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
