<p class="text-body-secondary small">Produtos relacionados pela fonte. A semelhança não garante compatibilidade ou substituição técnica.</p>
@if($similares->isEmpty())
    <p class="text-body-secondary">Nenhuma referência similar cadastrada.</p>
@else
    <div class="row g-3">
        @foreach($similares as $similar)
            <div class="col-12 col-md-6">
                <div class="card h-100"><div class="card-body d-flex gap-3">
                    @if($similar['dados']['imagem'])
                        <img src="{{ $similar['dados']['imagem'] }}" width="75" height="75" style="object-fit:contain" alt="" loading="lazy" referrerpolicy="no-referrer">
                    @endif
                    <div class="flex-grow-1">
                        <div class="small text-body-secondary">{{ $similar['dados']['marca'] ?: 'Marca não informada' }}</div>
                        <strong>{{ $similar['dados']['nome'] }}</strong>
                        <div>{{ $similar['dados']['preco'] !== null ? 'R$ '.number_format((float) $similar['dados']['preco'], 2, ',', '.') : 'A consultar' }}</div>
                        <a class="btn btn-sm btn-outline-primary mt-1" href="{{ $similar['url'] }}">Visualizar</a>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>
    @if($mais)<p class="text-body-secondary small mt-3">Mostrando as primeiras 30 referências.</p>@endif
@endif
