@foreach ($conta->anexosVinculos as $vinculo)

    <div
        class="modal fade"
        id="modalExcluirAnexo{{ $vinculo->id }}"
        tabindex="-1"
        aria-labelledby="modalExcluirAnexoLabel{{ $vinculo->id }}"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="modalExcluirAnexoLabel{{ $vinculo->id }}"
                    >
                        <i class="bi bi-trash text-danger"></i>
                        Excluir anexo
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>

                </div>

                <div class="modal-body">

                    <p class="mb-2">
                        Tem certeza que deseja excluir este anexo?
                    </p>

                    <div class="alert alert-warning mb-0">

                        <i class="bi bi-exclamation-triangle"></i>

                        <strong>
                            {{ $vinculo->anexo?->nome_original }}
                        </strong>

                        será removido desta conta.

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <form
                        method="POST"
                        action="{{ route('anexos.destroy', $vinculo) }}"
                        class="d-inline"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i class="bi bi-trash"></i>
                            Excluir anexo
                        </button>

                    </form>

                </div>

            </div>

        </div>
    </div>

@endforeach

@foreach ($conta->pagamentos as $pagamento)

    @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')

        <div
            class="modal fade"
            id="modalEstornarPagamento{{ $pagamento->id }}"
            tabindex="-1"
            aria-labelledby="modalEstornarPagamentoLabel{{ $pagamento->id }}"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form
                        method="POST"
                        action="{{ route('contas-pagar.pagamentos.estornar', [$conta, $pagamento]) }}"
                    >
                        @csrf

                        <div class="modal-header">

                            <h5
                                class="modal-title"
                                id="modalEstornarPagamentoLabel{{ $pagamento->id }}"
                            >
                                <i class="bi bi-arrow-counterclockwise text-danger"></i>
                                Estornar pagamento
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Fechar"
                            ></button>

                        </div>

                        <div class="modal-body">

                            <div class="alert alert-warning">

                                <i class="bi bi-exclamation-triangle"></i>

                                O pagamento de

                                <strong>
                                    R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                </strong>

                                será estornado.

                            </div>

                            <div class="mb-3">

                                <label
                                    for="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-label"
                                >
                                    Motivo do estorno
                                </label>

                                <textarea
                                    name="motivo"
                                    id="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    required
                                ></textarea>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Confirmar estorno
                            </button>

                        </div>

                    </form>

                </div>

            </div>
        </div>

    @endif

@endforeach

@if ($conta->status !== 'cancelada')

    <div
        class="modal fade"
        id="modalCancelarConta"
        tabindex="-1"
        aria-labelledby="modalCancelarContaLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form
                    method="POST"
                    action="{{ route('contas-pagar.cancelar', $conta) }}"
                >
                    @csrf

                    <div class="modal-header">

                        <h5
                            class="modal-title"
                            id="modalCancelarContaLabel"
                        >
                            <i class="bi bi-x-circle text-danger"></i>
                            Cancelar conta a pagar
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <div class="alert alert-warning">

                            <i class="bi bi-exclamation-triangle"></i>

                            Esta ação não poderá ser desfeita enquanto a conta permanecer cancelada.

                        </div>

                        <p>
                            Conta:
                            <strong>{{ $conta->descricao }}</strong>
                        </p>

                        <p>
                            Valor:
                            <strong>
                                R$ {{ number_format((float) $conta->valor, 2, ',', '.') }}
                            </strong>
                        </p>

                        <div class="mb-3">

                            <label
                                for="motivo_cancelamento"
                                class="form-label"
                            >
                                Motivo do cancelamento
                            </label>

                            <textarea
                                name="motivo"
                                id="motivo_cancelamento"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                required
                            ></textarea>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Voltar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            Confirmar cancelamento
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

@endif
