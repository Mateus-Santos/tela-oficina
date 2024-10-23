<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

class ToggleSwitch extends Component
{
    public Model $model;
    public string $field = 'status';

    public bool $isActive;

    public function mount()
    {
        $this->isActive = (bool) $this->model->getAttribute($this->field);
    }

    public function render()
    {

        return view('livewire.toggle-switch');
    }

    public function updating($field, $value)
    {
        $this->model->setAttribute($this->field, $value)->save();
    }
}