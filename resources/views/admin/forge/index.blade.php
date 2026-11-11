@extends('layouts.layout')

@section('content')
<div class="container cadastro py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-database-check me-2"></i>Integração Forge</h1>
            <p class="text-muted mb-0">Pacotes recebidos no staging privado do Laravel.</p>
        </div>
        <a href="{{ route('admin.forge.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-clockwise me-1"></i> Verificar pacotes recebidos
        </a>
    </div>

    <div class="alert alert-info" role="status">
        <strong>Consulta de pacotes — somente leitura.</strong>
        Esta página não executa validação dos CSVs por HTTP, não compara versões e não importa dados. As validações completas são realizadas fora da requisição, com resultados históricos apresentados abaixo.
        A atualização do catálogo permanece desabilitada até a implementação dos controles de segurança.
    </div>

    @if (!$consulta['disponivel'])
        <div class="alert alert-warning" role="status">
            Staging privado indisponível neste ambiente. Nenhum pacote foi consultado.
        </div>
    @elseif ($consulta['total'] === 0)
        <div class="card"><div class="card-body">
            Nenhum pacote Forge recebido foi encontrado.
        </div></div>
    @else
        <div class="card shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                <strong>Pacotes encontrados: {{ $consulta['total'] }}</strong>
                <span class="text-muted small">Exibindo no máximo 30 pacotes</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th scope="col">Pacote</th><th scope="col">Situação do manifesto</th><th scope="col">Última validação registrada</th></tr></thead>
                    <tbody>
                    @foreach ($consulta['pacotes'] as $pacote)
                        <tr>
                            <td><code>{{ $pacote['nome'] }}</code></td>
                            <td>
                                @if ($pacote['manifesto_presente'])
                                    <span class="badge bg-secondary">Manifesto presente (não validado)</span>
                                @else
                                    <span class="badge bg-warning text-dark">Manifesto ausente ou ilegível</span>
                                @endif
                            </td>
                            <td>
                                @if ($pacote['relatorio'] !== null)
                                    @php($relatorio = $pacote['relatorio'])
                                    @if ($relatorio['status'] === 'aprovado')
                                        <span class="badge bg-success">Validação anterior: aprovada</span>
                                    @else
                                        <span class="badge bg-danger">Validação anterior: reprovada</span>
                                    @endif
                                    <div class="small text-muted mt-1">Resultado histórico: {{ $relatorio['verificado_em'] }} (UTC)</div>
                                    @if ($relatorio['manifesto_alterado'])
                                        <div class="small text-danger fw-semibold">Manifesto ausente ou alterado desde a verificação.</div>
                                    @endif
                                    <div class="small text-muted">Este registro não substitui uma nova verificação antes da importação.</div>
                                    @if ($relatorio['status'] === 'aprovado')
                                        <details class="mt-2">
                                            <summary class="small">Detalhes da análise anterior</summary>
                                            <div class="small mt-1">Arquivos: {{ count($relatorio['arquivos']) }}</div>
                                            <div class="small text-break">SHA-256 do manifesto: <code>{{ $relatorio['manifesto_sha256'] }}</code></div>
                                            <ul class="small ps-3 mt-1 mb-0">
                                                @foreach ($relatorio['arquivos'] as $nomeArquivo => $arquivo)
                                                    <li><code>{{ $nomeArquivo }}</code> — {{ $arquivo['linhas'] ?? 0 }} linhas</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                @else
                                    <span class="text-muted">Sem validação registrada</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
