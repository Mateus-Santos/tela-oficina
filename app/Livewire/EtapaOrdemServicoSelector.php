<?php

namespace App\Livewire;

use App\Actions\OrdemServico\AlterarEtapaOrdemServico;
use App\Models\Etapa;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;

class EtapaOrdemServicoSelector extends Component
{
    public OrdemServico $ordemServico;

    public ?int $etapaId = null;

    public function mount(
        OrdemServico $ordemServico
    ): void {
        $this->ordemServico =
            $ordemServico;

        $this->etapaId =
            $ordemServico->etapa_id;
    }

    public function updatedEtapaId(
        mixed $value,
        AlterarEtapaOrdemServico $alterarEtapaOrdemServico
    ): void {
        $this->resetErrorBag('etapa');

        $this->ordemServico->refresh();

        if (
            ! Auth::check()
            || Auth::user()->permitions == 2
        ) {
            $this->etapaId =
                $this->ordemServico->etapa_id;

            $this->addError(
                'etapa',
                'Você não possui permissão para alterar a etapa da Ordem de Serviço.'
            );

            return;
        }

        if (
            $this->ordemServico->status
            !== 'aberta'
        ) {
            $this->etapaId =
                $this->ordemServico->etapa_id;

            $this->addError(
                'etapa',
                'Somente ordens de serviço abertas podem mudar de etapa.'
            );

            return;
        }

        $etapa = Etapa::query()
            ->find($value);

        if (! $etapa) {
            $this->etapaId =
                $this->ordemServico->etapa_id;

            $this->addError(
                'etapa',
                'A etapa selecionada não existe.'
            );

            return;
        }

        try {
            $ordemServico =
                $alterarEtapaOrdemServico->execute(
                    $this->ordemServico,
                    $etapa,
                    Auth::id()
                );

            $this->ordemServico =
                $ordemServico;

            $this->etapaId =
                $ordemServico->etapa_id;

            $this->dispatch(
                'etapa-ordem-servico-atualizada',
                ordemServicoId: $ordemServico->id
            );
        } catch (InvalidArgumentException $e) {
            $this->ordemServico->refresh();

            $this->etapaId =
                $this->ordemServico->etapa_id;

            $this->addError(
                'etapa',
                $e->getMessage()
            );
        }
    }

    public function render()
    {
        $etapas = Etapa::query()
            ->paraOrdemServico()
            ->where(
                function ($query) {
                    $query
                        ->where('ativo', true)
                        ->orWhere(
                            'id',
                            $this->ordemServico->etapa_id
                        );
                }
            )
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        return view(
            'livewire.etapa-ordem-servico-selector',
            compact('etapas')
        );
    }
}
