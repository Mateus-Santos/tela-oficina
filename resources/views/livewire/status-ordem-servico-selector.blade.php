<div>
    <select 
        wire:model.live="status" 
        class="form-select form-select-sm {{ match($status) {
            'aberto' => 'border-primary text-primary',
            'finalizada' => 'border-warning text-success',
            'em_andamento' => 'border-success text-success',
            'cancelada' => 'border-danger text-danger',
            'aguardando_aprovacao' => 'border-danger text-success',
            default => 'border-secondary'
        } }}"
    >
        <option value="aberto">aberto</option>
        <option value="em_andamento">Em Andamento</option>
        <option value="finalizada">Finalizada</option>
        <option value="cancelada">Cancelado</option>
        <option value="aguardando_aprovacao">Aguardando Aprovação</option>
    </select>
</div>