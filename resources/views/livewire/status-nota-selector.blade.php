<div>

    @php

        $notaAberta =
            $nota->status === 'Aberto';

        $notaFinalizada =
            $nota->status === 'Finalizado';

        $notaConcluidaLegado =
            $nota->status === 'Concluido';

        $notaCancelada =
            $nota->status === 'Cancelado';

        $cancelamentoComReversaoEstoque =
            $notaFinalizada
            || $notaConcluidaLegado;

    @endphp

    <select
        wire:change="solicitarTrocaStatus($event.target.value)"
        class="form-select form-select-sm {{
            in_array(
                $nota->status,
                [
                    'Finalizado',
                    'Concluido',
                ],
                true
            )
                ? 'border-success text-success'
                : (
                    $nota->status === 'Cancelado'
                        ? 'border-danger text-danger'
                        : ''
                )
        }}"
        @disabled($notaCancelada)
    >

        {{-- ================================================
             NOTA ABERTA
        ================================================= --}}

        @if($notaAberta)

            <option
                value="Aberto"
                selected
            >
                Aberto
            </option>

            <option value="Finalizado">
                Finalizado
            </option>

            <option value="Cancelado">
                Cancelado
            </option>

        {{-- ================================================
             NOTA FINALIZADA
        ================================================= --}}

        @elseif($notaFinalizada)

            <option
                value="Finalizado"
                selected
            >
                Finalizado
            </option>

            <option value="Cancelado">
                Cancelado
            </option>

        {{-- ================================================
             CONCLUÍDO LEGADO
        ================================================= --}}

        @elseif($notaConcluidaLegado)

            <option
                value="Concluido"
                selected
            >
                Concluído (legado)
            </option>

            <option value="Cancelado">
                Cancelado
            </option>

        {{-- ================================================
             CANCELADA
        ================================================= --}}

        @elseif($notaCancelada)

            <option
                value="Cancelado"
                selected
            >
                Cancelado
            </option>

        {{-- ================================================
             STATUS DESCONHECIDO
        ================================================= --}}

        @else

            <option
                value="{{ $nota->status }}"
                selected
            >
                {{ $nota->status }}
            </option>

        @endif

    </select>


    {{-- =====================================================
         ERRO
    ====================================================== --}}

    @if($errors->has('status'))

        <div class="text-danger small mt-1">

            <i class="bi bi-exclamation-triangle"></i>

            {{ $errors->first('status') }}

        </div>

    @endif


    {{-- =====================================================
         MODAL DE CONFIRMAÇÃO
    ====================================================== --}}

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

                    <div class="modal-header bg-danger text-white">

                        <h5 class="modal-title">

                            <i class="bi bi-exclamation-triangle-fill me-2"></i>

                            Confirmar cancelamento

                        </h5>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            wire:click="$set('confirmingStatusChange', false)"
                            aria-label="Fechar"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <p class="fs-6">

                            Você está prestes a

                            <strong>
                                cancelar a Nota #{{ $nota->id }}
                            </strong>.

                        </p>

                        @if($cancelamentoComReversaoEstoque)

                            <div class="alert alert-warning mb-0">

                                <div class="fw-semibold mb-1">

                                    <i class="bi bi-exclamation-triangle"></i>
                                    Atenção

                                </div>

                                Esta Nota já foi finalizada.

                                Os produtos baixados serão devolvidos ao estoque.

                                @if($nota->contaReceber)

                                    A Conta a Receber vinculada também será cancelada.

                                @endif

                                <div class="mt-2">
                                    Esta operação não poderá ser desfeita.
                                </div>

                            </div>

                        @else

                            <div class="alert alert-warning mb-0">

                                <div class="fw-semibold mb-1">

                                    <i class="bi bi-exclamation-triangle"></i>
                                    Atenção

                                </div>

                                Esta Nota ainda está aberta, portanto
                                nenhuma movimentação de estoque será revertida.

                                @if($nota->contaReceber)

                                    A Conta a Receber vinculada também será cancelada.

                                @endif

                                <div class="mt-2">
                                    A Nota não poderá mais ser editada após o cancelamento.
                                </div>

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
                            Voltar

                        </button>

                        <button
                            type="button"
                            class="btn btn-danger"
                            wire:click="confirmarTrocaStatus"
                            wire:loading.attr="disabled"
                        >

                            <span
                                wire:loading.remove
                                wire:target="confirmarTrocaStatus"
                            >

                                <i class="bi bi-check-circle me-1"></i>
                                Sim, cancelar

                            </span>

                            <span
                                wire:loading
                                wire:target="confirmarTrocaStatus"
                            >

                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                ></span>

                                Processando...

                            </span>

                        </button>

                    </div>

                </div>

            </div>

        </div>

    @endif

</div>
