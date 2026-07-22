<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\OrdemServico;

class StatusOrdemServicoSelector extends Component
{
    public OrdemServico $ordemServico;
    public string $status;

    public function mount(OrdemServico $ordemServico)
    {
        $this->ordemServico = $ordemServico;
        $this->status = $ordemServico->status;
    }

    public function updatedStatus($value)
    {
        $this->ordemServico->update([
            'status' => $value
        ]);
    }

    public function render()
    {
        return view('livewire.status-ordem-servico-selector');
    }
}