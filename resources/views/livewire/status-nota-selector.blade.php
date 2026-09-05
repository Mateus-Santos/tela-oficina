<div>
<select
    wire:change="solicitarTrocaStatus($event.target.value)"
    class="form-select form-select-sm {{ in_array($nota->status, ['Finalizado', 'Concluido']) ? 'border-success text-success' : ($nota->status === 'Cancelado' ? 'border-danger text-danger' : '') }}"
>
    @if($nota->status === 'Concluido')
        <option value="Concluido" selected>Concluído (legado)</option>
    @else
        <option value="Aberto" {{ $nota->status === 'Aberto' ? 'selected' : '' }}>Aberto</option>
        <option value="Finalizado" {{ $nota->status === 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
        <option value="Cancelado" {{ $nota->status === 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
    @endif
</select>

@if($errors->has('status'))
    <div class="text-danger small mt-1">
        <i class="bi bi-exclamation-triangle"></i>
        {{ $errors->first('status') }}
    </div>
@endif

@if($confirmingStatusChange)
    <div
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0,0,0,0.5);"
        role="dialog"
        aria-modal="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header {{ $novoStatus === 'Cancelado' ? 'bg-danger text-white' : 'bg-success text-white' }}">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Confirmar Alteração de Status
                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        wire:click="$set('confirmingStatusChange', false)"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    @if($novoStatus === 'Finalizado')
                        <p class="fs-6">
                            Você está prestes a <strong>finalizar a Nota #{{ $nota->id }}</strong>.
                        </p>

                        <div class="alert alert-warning mb-0">
                            <strong>Atenção:</strong>
                            os produtos desta nota serão baixados do estoque e a nota não poderá mais ser editada.
                            As Ordens de Serviço (OS) vinculadas também serão finalizadas.
                        </div>
                    @elseif($novoStatus === 'Cancelado')
                        <p class="fs-6">
                            Você está prestes a <strong>cancelar a Nota #{{ $nota->id }}</strong>.
                        </p>

                        <div class="alert alert-warning mb-0">
                            <strong>Atenção:</strong>
                            os produtos baixados desta nota serão devolvidos ao estoque.
                            Esta operação não poderá ser desfeita.
                            As Ordens de Serviço (OS) vinculadas também serão canceladas.
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        wire:click="$set('confirmingStatusChange', false)"
                    >
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="btn {{ $novoStatus === 'Cancelado' ? 'btn-danger' : 'btn-success' }}"
                        wire:click="confirmarTrocaStatus"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="confirmarTrocaStatus">
                            <i class="bi bi-check-circle me-1"></i>
                            Sim, confirmar
                        </span>

                        <span wire:loading wire:target="confirmarTrocaStatus">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Processando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif


</div>
