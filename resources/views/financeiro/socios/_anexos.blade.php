@php
    $tipos = [
        'nf' => 'Nota fiscal', 'nf_xml' => 'NF-e XML', 'foto' => 'Foto',
        'comprovante' => 'Comprovante', 'boleto' => 'Boleto',
        'contrato' => 'Contrato', 'orcamento' => 'Orçamento',
        'conta_luz' => 'Conta de luz', 'conta_agua' => 'Conta de água',
        'conta_telefone' => 'Conta de telefone', 'recibo' => 'Recibo',
        'outro' => 'Outro',
    ];
    $podeAlterarAnexos = !in_array($conta->status, ['paga', 'cancelada'], true);
@endphp
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 text-start">
        <strong><i class="bi bi-paperclip me-1" aria-hidden="true"></i> Documentos e anexos</strong>
        <span class="badge text-bg-light border">{{ $conta->anexosVinculos->count() }} arquivo(s)</span>
    </div>
    <div class="card-body text-start">
        @if($podeAlterarAnexos)
        <form method="POST" action="{{ route('socios-financeiro.contas.anexos.store', [$socio, $conta]) }}" enctype="multipart/form-data" class="row g-3 mb-4">
            @csrf
            <div class="col-12 col-lg-5">
                <label class="form-label" for="socio-anexo-arquivo">Arquivo</label>
                <input id="socio-anexo-arquivo" type="file" class="form-control @error('arquivo') is-invalid @enderror"
                    name="arquivo" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml" required>
                <div class="form-text">PDF, imagens ou XML; até 2 MB. Armazenamento privado.</div>
                @error('arquivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label" for="socio-anexo-tipo">Tipo</label>
                <select id="socio-anexo-tipo" name="tipo" class="form-select" required>
                    <option value="">Selecionar tipo</option>
                    @foreach($tipos as $chave => $nome)
                    <option value="{{ $chave }}" @selected(old('tipo') === $chave)>{{ $nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label" for="socio-anexo-obs">Observações</label>
                <input id="socio-anexo-obs" class="form-control" name="observacoes" maxlength="1000" value="{{ old('observacoes') }}">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-upload" aria-hidden="true"></i> Enviar anexo</button>
            </div>
        </form>
        @else
        <div class="alert alert-info small">Esta conta está encerrada. Os anexos permanecem disponíveis para consulta, mas não podem ser alterados.</div>
        @endif

        @if($conta->anexosVinculos->isEmpty())
            <p class="text-muted mb-0">Nenhum documento vinculado.</p>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Arquivo</th><th>Tipo</th><th>Tamanho</th><th>Observações</th><th>Ações</th></tr></thead>
                <tbody>
                @foreach($conta->anexosVinculos as $vinculo)
                    @php $anexo = $vinculo->anexo; @endphp
                    <tr>
                        <td class="text-break">{{ $anexo->nome_original }}</td>
                        <td>{{ $tipos[$vinculo->tipo] ?? $vinculo->tipo }}</td>
                        <td>{{ number_format($anexo->tamanho / 1024, 1, ',', '.') }} KB</td>
                        <td class="text-break">{{ $vinculo->observacoes ?: '—' }}</td>
                        <td><div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('anexos.show', $anexo) }}" class="btn btn-sm btn-outline-secondary" title="Visualizar arquivo" aria-label="Visualizar {{ $anexo->nome_original }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <a href="{{ route('anexos.download', $anexo) }}" class="btn btn-sm btn-outline-primary" title="Baixar arquivo" aria-label="Baixar {{ $anexo->nome_original }}"><i class="bi bi-download" aria-hidden="true"></i></a>
                            @if($podeAlterarAnexos)
                            <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#editar-anexo-socio-{{ $vinculo->id }}" aria-label="Editar anexo {{ $anexo->nome_original }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                            <form method="POST" action="{{ route('anexos.destroy', $vinculo) }}" onsubmit="return confirm('Excluir este anexo?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Excluir anexo {{ $anexo->nome_original }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@if($podeAlterarAnexos)
    @foreach($conta->anexosVinculos as $vinculo)
        <div class="modal fade" id="editar-anexo-socio-{{ $vinculo->id }}" tabindex="-1" aria-labelledby="titulo-anexo-socio-{{ $vinculo->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
                <form action="{{ route('anexos.update', $vinculo) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title" id="titulo-anexo-socio-{{ $vinculo->id }}">Editar anexo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                    <div class="modal-body row g-3">
                        <div class="col-12"><strong class="text-break">{{ $vinculo->anexo->nome_original }}</strong></div>
                        <div class="col-12 col-md-6"><label class="form-label" for="tipo-socio-anexo-{{ $vinculo->id }}">Tipo</label><select class="form-select" id="tipo-socio-anexo-{{ $vinculo->id }}" name="tipo" required>
                            @foreach($tipos as $chave => $nome)<option value="{{ $chave }}" @selected($vinculo->tipo === $chave)>{{ $nome }}</option>@endforeach
                        </select></div>
                        <div class="col-12 col-md-6"><label class="form-label" for="arquivo-socio-anexo-{{ $vinculo->id }}">Substituir arquivo (opcional)</label><input type="file" class="form-control" id="arquivo-socio-anexo-{{ $vinculo->id }}" name="arquivo" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml"></div>
                        <div class="col-12"><label class="form-label" for="obs-socio-anexo-{{ $vinculo->id }}">Observações</label><textarea class="form-control" id="obs-socio-anexo-{{ $vinculo->id }}" name="observacoes" rows="2" maxlength="1000">{{ $vinculo->observacoes }}</textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Salvar alterações</button></div>
                </form>
            </div></div>
        </div>
    @endforeach
@endif
