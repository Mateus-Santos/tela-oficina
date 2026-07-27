<div>
    {{-- Select de Status --}}
    <select
        wire:change="solicitarTrocaStatus($event.target.value)"
        class="form-select form-select-sm {{ $nota->status === 'Concluido' ? 'border-success text-success' : ($nota->status === 'Cancelado' ? 'border-danger text-danger' : '') }}"
    >
        <option value="Aberto" {{ $nota->status == 'Aberto' ? 'selected' : '' }}>Aberto</option>
        <option value="Andamento" {{ $nota->status == 'Andamento' ? 'selected' : '' }}>Em Andamento</option>
        <option value="Concluido" {{ $nota->status == 'Concluido' ? 'selected' : '' }}>Concluído</option>
        <option value="Cancelado" {{ $nota->status == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
    </select>

    {{-- Pop-up / Modal de Confirmação --}}
    @if($confirmingStatusChange)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header {{ $novoStatus === 'Cancelado' ? 'bg-danger text-white' : 'bg-success text-white' }}">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Alteração de Status
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="$set('confirmingStatusChange', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fs-6">
                            Você está prestes a alterar o status da <strong>Nota #{{ $nota->id }}</strong> para
                            <span class="badge {{ $novoStatus === 'Cancelado' ? 'bg-danger' : 'bg-success' }}">{{ $novoStatus }}</span>.
                        </p>
                        <div class="alert alert-warning mb-0">
                            <strong>Atenção:</strong> Esta ação também irá alterar o status de <strong>todas as Ordens de Serviço (OS)</strong> vinculadas a esta nota para <u>{{ $novoStatus }}</u>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('confirmingStatusChange', false)">
                            Cancelar
                        </button>
                        <button type="button" class="btn {{ $novoStatus === 'Cancelado' ? 'btn-danger' : 'btn-success' }}" wire:click="confirmarTrocaStatus">
                            Sim, confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
