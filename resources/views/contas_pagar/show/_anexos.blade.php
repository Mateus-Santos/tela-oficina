@php
    $tipos = [
        'nf' => 'Nota fiscal',
        'nf_xml' => 'NF-e XML',
        'foto' => 'Foto',
        'comprovante' => 'Comprovante',
        'boleto' => 'Boleto',
        'contrato' => 'Contrato',
        'orcamento' => 'Orçamento',
        'conta_luz' => 'Conta de luz',
        'conta_agua' => 'Conta de água',
        'conta_telefone' => 'Conta de telefone',
        'recibo' => 'Recibo',
        'outro' => 'Outro',
    ];

    $anexosCompra = $conta->compra?->anexosVinculos ?? collect();
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-paperclip"></i>
            <strong>Anexos</strong>
        </div>
    </div>

    <div class="card-body">
        @if ($conta->status !== 'cancelada' && !$conta->estaPaga())
            <form
                method="POST"
                action="{{ route('contas-pagar.anexos.store', $conta) }}"
                enctype="multipart/form-data"
                class="mb-4"
            >
                @csrf

                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="arquivo" class="form-label">Arquivo</label>

                        <input
                            type="file"
                            name="arquivo"
                            id="arquivo"
                            class="form-control @error('arquivo') is-invalid @enderror"
                            required
                        >

                        @error('arquivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            PDF, JPG, JPEG, PNG, WEBP ou XML. Máximo de 2 MB.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo</label>

                        <select
                            name="tipo"
                            id="tipo"
                            class="form-select @error('tipo') is-invalid @enderror"
                            required
                        >
                            <option value="">Selecione...</option>
                            <option value="nf" @selected(old('tipo') === 'nf')>Nota fiscal</option>
                            <option value="nf_xml" @selected(old('tipo') === 'nf_xml')>NF-e XML</option>
                            <option value="boleto" @selected(old('tipo') === 'boleto')>Boleto</option>
                            <option value="comprovante" @selected(old('tipo') === 'comprovante')>Comprovante</option>
                            <option value="contrato" @selected(old('tipo') === 'contrato')>Contrato</option>
                            <option value="orcamento" @selected(old('tipo') === 'orcamento')>Orçamento</option>
                            <option value="recibo" @selected(old('tipo') === 'recibo')>Recibo</option>
                            <option value="foto" @selected(old('tipo') === 'foto')>Foto</option>
                            <option value="conta_luz" @selected(old('tipo') === 'conta_luz')>Conta de luz</option>
                            <option value="conta_agua" @selected(old('tipo') === 'conta_agua')>Conta de água</option>
                            <option value="conta_telefone" @selected(old('tipo') === 'conta_telefone')>Conta de telefone</option>
                            <option value="outro" @selected(old('tipo') === 'outro')>Outro</option>
                        </select>

                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="observacoes" class="form-label">Observações</label>

                        <input
                            type="text"
                            name="observacoes"
                            id="observacoes"
                            class="form-control @error('observacoes') is-invalid @enderror"
                            value="{{ old('observacoes') }}"
                        >

                        @error('observacoes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i>
                            Enviar anexo
                        </button>
                    </div>
                </div>
            </form>
        @endif

        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-wallet2"></i>
            <strong>Anexos da conta</strong>
        </div>

        @if ($conta->anexosVinculos->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-paperclip fs-2 d-block mb-2"></i>
                Nenhum anexo vinculado diretamente a esta conta.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Observações</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($conta->anexosVinculos as $vinculo)
                            @php
                                $anexo = $vinculo->anexo;
                            @endphp

                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark fs-5"></i>

                                        <span
                                            class="text-break"
                                            title="{{ $anexo->nome_original }}"
                                        >
                                            {{ $anexo->nome_original }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    {{ $tipos[$vinculo->tipo] ?? $vinculo->tipo }}
                                </td>

                                <td>
                                    {{ number_format($anexo->tamanho / 1024, 1, ',', '.') }} KB
                                </td>

                                <td>
                                    {{ $vinculo->observacoes ?: '—' }}
                                </td>

                                <td>
                                    {{ $vinculo->created_at?->format('d/m/Y H:i') }}
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a
                                            href="{{ route('anexos.show', $anexo) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Visualizar anexo"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="{{ route('anexos.download', $anexo) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Baixar arquivo"
                                        >
                                            <i class="bi bi-download"></i>
                                        </a>

                                        @if ($conta->status !== 'cancelada' && !$conta->estaPaga())
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalExcluirAnexo{{ $vinculo->id }}"
                                                title="Excluir anexo"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($conta->compra)
            <hr class="my-4">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cart-check"></i>

                    <strong>
                        Anexos da compra
                        #{{ str_pad($conta->compra->id, 6, '0', STR_PAD_LEFT) }}
                    </strong>
                </div>

                <a
                    href="{{ route('compras.show', $conta->compra) }}"
                    class="btn btn-sm btn-outline-secondary"
                >
                    <i class="bi bi-box-arrow-up-right"></i>
                    Abrir compra
                </a>
            </div>

            @if ($anexosCompra->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="bi bi-paperclip fs-2 d-block mb-2"></i>
                    A compra vinculada não possui anexos.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Tipo</th>
                                <th>Tamanho</th>
                                <th>Observações</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($anexosCompra as $vinculo)
                                @php
                                    $anexo = $vinculo->anexo;
                                @endphp

                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark fs-5"></i>

                                            <span
                                                class="text-break"
                                                title="{{ $anexo->nome_original }}"
                                            >
                                                {{ $anexo->nome_original }}
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        {{ $tipos[$vinculo->tipo] ?? $vinculo->tipo }}
                                    </td>

                                    <td>
                                        {{ number_format($anexo->tamanho / 1024, 1, ',', '.') }} KB
                                    </td>

                                    <td>
                                        {{ $vinculo->observacoes ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $vinculo->created_at?->format('d/m/Y H:i') }}
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a
                                                href="{{ route('anexos.show', $anexo) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="Visualizar anexo"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a
                                                href="{{ route('anexos.download', $anexo) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Baixar arquivo"
                                            >
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

    </div>
</div>
