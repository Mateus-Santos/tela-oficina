<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Nota;

class StatusNotaSelector extends Component
{
    public Nota $nota;
    public string $status;

    // Opções disponíveis no sistema
    public array $opcoesStatus = [
        'Aberto'      => 'Aberto',
        'Andamento'   => 'Em Andamento',
        'Concluido'   => 'Concluído',
        'Cancelado'   => 'Cancelado',
    ];

    public function mount(Nota $nota)
    {
        $this->nota = $nota;
        $this->status = $nota->status;
    }

    // Executado automaticamente ao alterar o select
    public function updatedStatus($value)
    {
        $this->nota->update([
            'status' => $value,
        ]);

        session()->flash('success', 'Status da nota atualizado!');
    }

    public function render()
    {
        return view('livewire.status-nota-selector');
    }
}