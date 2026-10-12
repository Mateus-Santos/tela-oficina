<?php

namespace App\Livewire;

use App\Actions\Notas\AlterarEtapaNota;
use App\Models\Etapa;
use App\Models\Nota;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;

class EtapaNotaSelector extends Component
{
    public Nota $nota;

    public ?int $etapaId = null;

    public function mount(Nota $nota): void
    {
        $this->nota = $nota;
        $this->etapaId = $nota->etapa_id;
    }

    public function updatedEtapaId(
        mixed $value,
        AlterarEtapaNota $alterarEtapaNota
    ): void {
        $this->resetErrorBag('etapa');

        $this->nota->refresh();

        if (
            ! Auth::check()
            || Auth::user()->permitions == 2
        ) {
            $this->etapaId =
                $this->nota->etapa_id;

            $this->addError(
                'etapa',
                'Você não possui permissão para alterar a etapa da Nota.'
            );

            return;
        }

        if ($this->nota->status !== 'Aberto') {
            $this->etapaId =
                $this->nota->etapa_id;

            $this->addError(
                'etapa',
                'Somente notas abertas podem mudar de etapa.'
            );

            return;
        }

        $etapa = Etapa::query()
            ->find($value);

        if (! $etapa) {
            $this->etapaId =
                $this->nota->etapa_id;

            $this->addError(
                'etapa',
                'A etapa selecionada não existe.'
            );

            return;
        }

        try {
            $nota =
                $alterarEtapaNota->execute(
                    $this->nota,
                    $etapa,
                    Auth::id()
                );

            $this->nota =
                $nota;

            $this->etapaId =
                $nota->etapa_id;

            $this->dispatch(
                'etapa-nota-atualizada',
                notaId: $nota->id
            );
        } catch (InvalidArgumentException $e) {
            $this->nota->refresh();

            $this->etapaId =
                $this->nota->etapa_id;

            $this->addError(
                'etapa',
                $e->getMessage()
            );
        }
    }

    public function render()
    {
        $etapas = Etapa::query()
            ->paraNota()
            ->where(
                function ($query) {
                    $query
                        ->where('ativo', true)
                        ->orWhere(
                            'id',
                            $this->nota->etapa_id
                        );
                }
            )
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        return view(
            'livewire.etapa-nota-selector',
            compact('etapas')
        );
    }
}
