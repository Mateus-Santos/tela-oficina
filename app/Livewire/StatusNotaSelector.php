<?php

namespace App\Livewire;

use App\Actions\Notas\CancelarNota;
use App\Models\Nota;
use InvalidArgumentException;
use Livewire\Component;

class StatusNotaSelector extends Component
{
    public Nota $nota;

    public string $novoStatus = '';

    public bool $confirmingStatusChange = false;

    public function mount(Nota $nota): void
    {
        $this->nota = $nota;
    }

    public function solicitarTrocaStatus(
        string $status
    ) {
        $this->nota->refresh();

        /*
         * Não faz nada se o usuário selecionar
         * novamente o status atual.
         */
        if (
            $status === $this->nota->status
        ) {
            return;
        }

        /*
         * =====================================================
         * FINALIZAÇÃO
         * =====================================================
         *
         * Finalização não é uma simples troca de status.
         *
         * Ela precisa:
         * - validar estoque;
         * - baixar estoque;
         * - criar/validar Conta a Receber;
         * - validar parcelas.
         *
         * Por isso encaminhamos para o show da Nota.
         */
        if ($status === 'Finalizado') {
            if (
                $this->nota->status !== 'Aberto'
            ) {
                return;
            }

            return $this->redirectRoute(
                'notas.show',
                [
                    'nota' =>
                        $this->nota->id,
                ]
            );
        }

        /*
         * =====================================================
         * CANCELAMENTO
         * =====================================================
         */
        if ($status === 'Cancelado') {
            if (
                !in_array(
                    $this->nota->status,
                    [
                        'Aberto',
                        'Finalizado',
                        'Concluido',
                    ],
                    true
                )
            ) {
                return;
            }

            $this->novoStatus =
                'Cancelado';

            $this->confirmingStatusChange =
                true;

            return;
        }
    }

    public function confirmarTrocaStatus(
        CancelarNota $cancelarNota
    ): void {
        $this->nota->refresh();

        try {
            if (
                $this->novoStatus
                === 'Cancelado'
            ) {
                $cancelarNota->execute(
                    $this->nota
                );
            }

            $this->nota->refresh();

            $this->confirmingStatusChange =
                false;

            $this->novoStatus =
                '';

            $this->dispatch(
                'status-nota-atualizado'
            );
        } catch (InvalidArgumentException $e) {
            $this->confirmingStatusChange =
                false;

            $this->novoStatus =
                '';

            $this->addError(
                'status',
                $e->getMessage()
            );
        }
    }

    public function render()
    {
        return view(
            'livewire.status-nota-selector'
        );
    }
}
