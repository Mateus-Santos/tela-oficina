<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ToggleSwitch extends Component
{
    public Model $model;

    public string $field = 'status';

    public bool $isActive;

    public function mount(): void
    {
        $this->authorizeToggle();

        $this->isActive = (bool) $this->model
            ->getAttribute('status');
    }

    public function updatedIsActive(
        bool $value
    ): void {
        $this->authorizeToggle();

        /*
         * Impede o administrador de bloquear
         * a própria conta acidentalmente.
         */
        abort_if(
            $this->model->is(auth()->user())
            && ! $value,
            403,
            'Você não pode bloquear sua própria conta.'
        );

        $this->model->update([
            'status' => $value,
        ]);
    }

    public function render()
    {
        return view(
            'livewire.toggle-switch'
        );
    }

    private function authorizeToggle(): void
    {
        $usuario = auth()->user();

        abort_unless(
            $usuario
            && $usuario->isAdmin(),
            403,
            'Acesso não autorizado.'
        );

        abort_unless(
            $this->model instanceof User,
            403,
            'Modelo não autorizado.'
        );

        abort_unless(
            $this->field === 'status',
            403,
            'Campo não autorizado.'
        );
    }
}
