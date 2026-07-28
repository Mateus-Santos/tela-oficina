<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Nota;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
class StatusNotaSelector extends Component
{
    public Nota $nota;
    public string $novoStatus = '';
    public bool $confirmingStatusChange = false;

    public function mount(Nota $nota)
    {
        $this->nota = $nota;
    }

    public function solicitarTrocaStatus(string $status)
    {
        if (in_array($status, ['Concluido', 'Cancelado'])) {
            $this->novoStatus = $status;
            $this->confirmingStatusChange = true;
        } else {
            $this->atualizarStatus($status);
        }
    }

    public function confirmarTrocaStatus()
    {
        $this->atualizarStatus($this->novoStatus);
        $this->confirmingStatusChange = false;
    }

    private function atualizarStatus(string $status)
    {
        $statusOS = match ($status) {
            'Concluido' => 'finalizada',
            'Cancelado' => 'cancelada',
            'Andamento' => 'em_andamento',
            'Aberto'    => 'aberta',
            default     => strtolower($status),
        };

        DB::transaction(function () use ($status, $statusOS) {
            $this->nota->update(['status' => $status]);

            if (in_array($status, ['Concluido', 'Cancelado'])) {
                $osIds = $this->nota->itens()
                    ->where('itemable_type', OrdemServico::class)
                    ->pluck('itemable_id');

                if ($osIds->isNotEmpty()) {
                    OrdemServico::whereIn('id', $osIds)->update([
                        'status'          => $statusOS,
                        'data_fechamento' => $status === 'Concluido' ? now() : null,
                    ]);
                }
            }
        });
    }

    public function render()
    {
        return view('livewire.status-nota-selector');
    }
}
