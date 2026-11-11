<?php

namespace App\Http\Requests\Marketing;

use App\Models\MarketingLead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarStatusLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(MarketingLead::STATUS_LABELS))],
        ];
    }
}
