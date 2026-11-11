@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/scss/home_entry.scss')
@endpush

@section('content')
@php
    $oficina = config('oficina');
    $whatsapp = preg_replace('/\D+/', '', $oficina['whatsapp']);
    $telefone = preg_replace('/\D+/', '', $oficina['telefone']);
    $telefoneExtra = preg_replace('/\D+/', '', $oficina['telefone_2'] ?? '');
    $email = trim($oficina['email'] ?? '');
    $mapaQuery = rawurlencode($oficina['endereco']);
    $paginaPecas = $paginaPecas ?? false;
    $whatsappPecas = 'https://wa.me/'.$whatsapp.'?text='.rawurlencode(
        'Olá! Quero solicitar orçamento de uma peça automotiva.'
    );
    $servicos = [
        ['icone' => 'bi-clipboard2-check', 'titulo' => 'Revisão preventiva', 'texto' => 'Verificações para identificar necessidades de manutenção.'],
        ['icone' => 'bi-droplet-half', 'titulo' => 'Óleo e filtros', 'texto' => 'Troca de óleo, filtros e cuidados básicos do motor.'],
        ['icone' => 'bi-disc', 'titulo' => 'Freios', 'texto' => 'Inspeção e manutenção dos componentes de frenagem.'],
        ['icone' => 'bi-car-front', 'titulo' => 'Suspensão e direção', 'texto' => 'Avaliação de ruídos, folgas e componentes de rodagem.'],
        ['icone' => 'bi-thermometer-half', 'titulo' => 'Arrefecimento', 'texto' => 'Verificação de fluido, mangueiras e sistema de refrigeração.'],
        ['icone' => 'bi-box-seam', 'titulo' => 'Peças automotivas', 'texto' => 'Orientação na escolha de peças para o serviço.'],
    ];
@endphp
<div class="sos-home">
    <section id="hero" class="sos-hero">
        <div class="container sos-hero__grid {{ $paginaPecas ? 'sos-hero__grid--lead' : '' }}">
            <div>
                <span class="sos-eyebrow"><i class="bi bi-geo-alt"></i> Oficina no Cruzeiro · Feira de Santana</span>
                @if ($paginaPecas)
                    <h1>Precisa de uma peça para seu carro?</h1>
                    <p>Conte para a nossa equipe qual peça você procura e informe o veículo, se souber. Não precisa conhecer códigos técnicos.</p>
                @else
                    <h1>Cuidado de verdade para o seu carro.</h1>
                    <p>Manutenção, revisão e reparos com atendimento próximo e explicações claras sobre o serviço.</p>
                @endif
                <div class="sos-actions">
                    @if ($paginaPecas)
                        <a class="sos-button sos-button--primary" href="#solicitar-orcamento"><i class="bi bi-pencil-square"></i> Preencher orçamento</a>
                    @else
                        <a class="sos-button sos-button--primary" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> Falar com a oficina</a>
                    @endif
                    <a class="sos-button sos-button--outline" href="#localizacao"><i class="bi bi-pin-map"></i> Como chegar</a>
                </div>
                @if ($paginaPecas)
                    <div class="sos-hero__points"><span><i class="bi bi-check-circle"></i> Atendimento humano</span><span><i class="bi bi-check-circle"></i> Sem cadastro</span><span><i class="bi bi-check-circle"></i> Compatibilidade a confirmar</span></div>
                @else
                    <div class="sos-hero__points"><span><i class="bi bi-check-circle"></i> Revisão</span><span><i class="bi bi-check-circle"></i> Manutenção</span><span><i class="bi bi-check-circle"></i> Peças</span></div>
                @endif
            </div>
            @if ($paginaPecas)
                @include('marketing._form_pecas')
            @else
                <div class="sos-hero__visual" aria-hidden="true">
                    <div class="sos-hero__ring"><i class="bi bi-gear-wide-connected"></i></div>
                    <div class="sos-hero__tag"><i class="bi bi-tools"></i> SOS Mecânica</div>
                </div>
            @endif
        </div>
    </section>

    <main id="main">
        @if (! $paginaPecas)
        <section id="servicos" class="sos-section">
            <div class="container">
                <span class="sos-kicker">O que fazemos</span>
                <h2>Serviços para cuidar do seu veículo</h2>
                <p class="sos-intro">Da revisão básica a reparos mecânicos, nossa equipe ajuda você a entender o que precisa ser feito.</p>
                <div class="sos-services">
                    @foreach($servicos as $servico)
                        <article class="sos-service">
                            <div class="sos-service__icon"><i class="bi {{ $servico['icone'] }}"></i></div>
                            <h3>{{ $servico['titulo'] }}</h3>
                            <p>{{ $servico['texto'] }}</p>
                            @if ($servico['titulo'] === 'Peças automotivas')
                                <a class="sos-text-link" href="{{ route('marketing.pecas.orcamento') }}">Solicitar orçamento <i class="bi bi-arrow-up-right"></i></a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="sobre" class="sos-section sos-section--muted">
            <div class="container sos-split">
                <div>
                    <span class="sos-kicker">Sobre a oficina</span>
                    <h2>Sua rotina merece um carro bem cuidado.</h2>
                    <p>Na SOS Mecânica, em Feira de Santana, trabalhamos com manutenção automotiva e atenção aos detalhes. Nosso objetivo é orientar o cliente sobre as necessidades do veículo antes da execução do serviço.</p>
                    <a class="sos-text-link" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">Converse com a equipe <i class="bi bi-arrow-up-right"></i></a>
                </div>
                <div class="sos-steps" aria-label="Etapas do atendimento">
                    <div><b>01</b><span><strong>Conte o que aconteceu</strong><small>Explique os sintomas ou a manutenção desejada.</small></span></div>
                    <div><b>02</b><span><strong>Avaliação do veículo</strong><small>Verificamos o serviço e orientamos sobre os próximos passos.</small></span></div>
                    <div><b>03</b><span><strong>Serviço combinado</strong><small>Alinhamos a execução e as informações do atendimento.</small></span></div>
                </div>
            </div>
        </section>

        @else
        <section id="como-funciona" class="sos-section sos-section--muted">
            <div class="container sos-split">
                <div>
                    <span class="sos-kicker">Orçamento de peças</span>
                    <h2>Como solicitar orçamento de peças</h2>
                    <p class="sos-intro">Explique o que procura pelo WhatsApp. Se souber, informe a marca, o modelo, o ano e a motorização do veículo.</p>
                    <p>A compatibilidade, o preço e a disponibilidade serão confirmados no atendimento.</p>
                    <a class="sos-text-link" href="{{ $whatsappPecas }}" target="_blank" rel="noopener noreferrer">Conversar com a oficina <i class="bi bi-arrow-up-right"></i></a>
                </div>
                <div class="sos-steps" aria-label="Etapas do orçamento">
                    <div><b>01</b><span><strong>Descreva a peça</strong><small>Use o nome que conhece ou explique para que ela serve.</small></span></div>
                    <div><b>02</b><span><strong>Informe o veículo</strong><small>Marca, modelo, ano e motor ajudam na identificação.</small></span></div>
                    <div><b>03</b><span><strong>Confirme com a equipe</strong><small>A oficina verifica os detalhes antes de combinar a venda.</small></span></div>
                </div>
            </div>
        </section>
        @endif

        <section id="localizacao" class="sos-section">
            <div class="container sos-split">
                <div>
                    <span class="sos-kicker">Onde estamos</span>
                    <h2>Venha visitar a SOS Mecânica</h2>
                    <p class="sos-address"><i class="bi bi-geo-alt-fill"></i> {{ $oficina['endereco'] }}</p>
                    <p class="sos-intro">Confira a localização no mapa e abra a rota no seu aplicativo de navegação.</p>
                    <a class="sos-button sos-button--dark" href="https://www.google.com/maps/search/?api=1&amp;query={{ $mapaQuery }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-sign-turn-right"></i> Abrir rota no Google Maps</a>
                </div>
                <div class="sos-map">
                    <iframe title="Mapa de localização da Oficina SOS Mecânica" src="https://www.google.com/maps?q={{ $mapaQuery }}&amp;output=embed" width="100%" height="310" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </div>
        </section>

        <section id="contato" class="sos-section sos-section--contact">
            <div class="container sos-contact">
                <div>
                    <span class="sos-kicker">Fale conosco</span>
                    @if ($paginaPecas)
                        <h2>Vamos conversar sobre a peça?</h2>
                        <p>Entre em contato e conte o que você precisa. O atendimento confirmará as condições do orçamento.</p>
                    @else
                        <h2>Precisa de uma avaliação?</h2>
                        <p>Entre em contato para explicar o que o seu carro precisa.</p>
                    @endif
                    @if($email)
                        <a class="sos-contact__link" href="mailto:{{ $email }}"><i class="bi bi-envelope"></i> {{ $email }}</a>
                    @endif
                    <a class="sos-contact__link" href="tel:+{{ $telefone }}"><i class="bi bi-telephone"></i> {{ $oficina['telefone_exibicao'] }}</a>
                    @if($telefoneExtra)
                        <a class="sos-contact__link" href="tel:+{{ $telefoneExtra }}"><i class="bi bi-telephone-plus"></i> {{ $oficina['telefone_2'] }}</a>
                    @endif
                </div>
                <a class="sos-button sos-button--primary" href="{{ $paginaPecas ? $whatsappPecas : 'https://wa.me/'.$whatsapp }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> Chamar no WhatsApp</a>
            </div>
        </section>
    </main>
</div>
@endsection
