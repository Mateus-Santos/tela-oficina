<?php

namespace App\Livewire;

use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\FinalizarNota;
use App\Models\Nota;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Component;

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
        if (!in_array($status, ['Finalizado', 'Cancelado'])) {
            $this->atualizarStatus($status);
            return;
        }

        $this->novoStatus = $status;
        $this->confirmingStatusChange = true;
    }

    public function confirmarTrocaStatus(FinalizarNota $finalizarNota, CancelarNota $cancelarNota)
    {
        $this->nota->refresh();

        try {
            if ($this->novoStatus === 'Finalizado') {
                $finalizarNota->execute($this->nota);
            } elseif ($this->novoStatus === 'Cancelado') {
                $cancelarNota->execute($this->nota);
            }

            $this->nota->refresh();
            $this->confirmingStatusChange = false;
            $this->novoStatus = '';
            $this->dispatch('status-nota-atualizado');
        } catch (InvalidArgumentException $e) {
            $this->confirmingStatusChange = false;
            $this->novoStatus = '';
            $this->addError('status', $e->getMessage());
        }
    }

    private function atualizarStatus(string $status)
    {
        if (!in_array($status, ['Aberto'], true)) {
            return;
        }

        DB::transaction(function () use ($status) {
            $this->nota->update([
                'status' => $status,
            ]);
        });

        $this->nota->refresh();
        $this->dispatch('status-nota-atualizado');
    }

    public function render()
    {
        return view('livewire.status-nota-selector');
    }
}
