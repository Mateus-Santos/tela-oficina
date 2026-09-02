<div>
    <select
        wire:model.live="status"
        class="form-select form-select-sm {{
            match($status) {
                'aberta' => 'border-primary text-primary',
                'em_andamento' => 'border-success text-success',
                'aguardando_aprovacao' => 'border-warning text-warning',
                'finalizada' => 'border-success text-success',
                'cancelada' => 'border-danger text-danger',
                default => 'border-secondary'
            }
        }}"
    >
        <option value="aberta">Aberta</option>
        <option value="em_andamento">Em Andamento</option>
        <option value="aguardando_aprovacao">Aguardando Aprovação</option>
        <option value="finalizada">Finalizada</option>
        <option value="cancelada">Cancelada</option>
    </select>
</div>
