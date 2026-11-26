<div class="card shadow-sm mt-4 mb-4 text-start">
    <div class="card-body">
        <h2 class="h5">Documentos e anexos</h2>
        <p class="small text-muted">Os arquivos sao privados; visualizacao, edicao, substituicao e exclusao usam as mesmas regras de anexos de Compras.</p>
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($podeAlterar)
            <form method="POST" action="{{ $urlStore }}" enctype="multipart/form-data" class="row g-3 mb-4">
                @csrf
                <div class="col-12 col-md-3"><label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select" required>
                        @foreach(['nf'=>'Nota fiscal', 'nf_xml'=>'XML', 'foto'=>'Foto', 'comprovante'=>'Comprovante', 'boleto'=>'Boleto', 'contrato'=>'Contrato', 'orcamento'=>'Orcamento', 'conta_luz'=>'Conta de luz', 'conta_agua'=>'Conta de agua', 'conta_telefone'=>'Conta de telefone', 'recibo'=>'Recibo', 'outro'=>'Outro'] as $codigo=>$rotulo)
                            <option value="{{ $codigo }}">{{ $rotulo }}</option>
                        @endforeach
                    </select></div>
                <div class="col-12 col-md-5"><label class="form-label">Arquivo (max. 2 MiB)</label>
                    <input type="file" name="arquivo" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp" class="form-control" required></div>
                <div class="col-12 col-md-4"><label class="form-label">Observacoes</label>
                    <input name="observacoes" maxlength="1000" class="form-control"></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Adicionar anexo</button></div>
            </form>
        @endif
        @forelse($anexosVinculos as $vinculo)
            @php($anexo = $vinculo->anexo)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div><strong>{{ $anexo?->nome_original ?? 'Arquivo indisponivel' }}</strong>
                        <div class="small text-muted">{{ $vinculo->tipo }} - {{ $vinculo->observacoes }}</div></div>
                    @if($anexo)
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('anexos.show', $anexo) }}">Visualizar</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('anexos.download', $anexo) }}">Baixar</a>
                    </div>
                    @endif
                </div>
                @if($podeAlterar)
                <form method="POST" action="{{ route('anexos.update', $vinculo) }}" enctype="multipart/form-data" class="row g-2 mt-2">
                    @csrf @method('PATCH')
                    <div class="col-12 col-md-3"><label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            @foreach(['nf', 'nf_xml', 'foto', 'comprovante', 'boleto', 'contrato', 'orcamento', 'conta_luz', 'conta_agua', 'conta_telefone', 'recibo', 'outro'] as $tipo)
                                <option value="{{ $tipo }}" @selected($vinculo->tipo === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-12 col-md-4"><label class="form-label">Observacoes</label>
                        <input name="observacoes" class="form-control" maxlength="1000" value="{{ $vinculo->observacoes }}"></div>
                    <div class="col-12 col-md-3"><label class="form-label">Substituir arquivo (opcional)</label>
                        <input type="file" name="arquivo" class="form-control" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp"></div>
                    <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-sm" type="submit">Salvar</button></div>
                </form>
                <form method="POST" action="{{ route('anexos.destroy', $vinculo) }}" class="mt-2"
                    onsubmit="return confirm('Excluir este vinculo e o arquivo caso nao tenha outros vinculos?');">
                    @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit">Excluir</button>
                </form>
                @endif
            </div>
        @empty
            <p class="text-muted">Nenhum anexo cadastrado.</p>
        @endforelse
    </div>
</div>
