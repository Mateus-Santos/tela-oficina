<?php

namespace App\Livewire;

use App\Actions\Notas\CancelarNota;
use App\Models\Nota;
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
        if ($status === 'Finalizado') {
            return $this->redirectRoute(
                'notas.show',
                ['nota' => $this->nota->id]
            );
        }

        if ($status === 'Cancelado') {
            $this->novoStatus = $status;
            $this->confirmingStatusChange = true;
            return;
        }

        $this->atualizarStatus($status);
    }

    public function confirmarTrocaStatus(
        CancelarNota $cancelarNota
    ) {
        $this->nota->refresh();

        try {
            if ($this->novoStatus === 'Cancelado') {
                $cancelarNota->execute($this->nota);
            }

            $this->nota->refresh();
            $this->confirmingStatusChange = false;
            $this->novoStatus = '';

            $this->dispatch(
                'status-nota-atualizado'
            );
        } catch (InvalidArgumentException $e) {
            $this->confirmingStatusChange = false;
            $this->novoStatus = '';

            $this->addError(
                'status',
                $e->getMessage()
            );
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

        $this->dispatch(
            'status-nota-atualizado'
        );
    }

    public function render()
    {
        return view(
            'livewire.status-nota-selector'
        );
    }
}
