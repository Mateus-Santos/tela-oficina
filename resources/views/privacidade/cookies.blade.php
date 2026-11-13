@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/scss/informativo_entry.scss')
@endpush

@section('content')
<main class="termos-uso" id="politica-cookies">
  <div class="campos">
    <h1>Política de Cookies e Serviços Externos</h1>
    <p class="small text-muted">Versão 2026-11-12. Informações relativas ao site da S.O.S Mecânica.</p>
    <h2>Quais tecnologias usamos?</h2>
    <ul>
      <li><strong>Necessárias:</strong> cookies de sessão e de proteção de formulários (como o XSRF-TOKEN e o cookie de sessão Laravel, conforme configuração). Viabilizam login, segurança e continuidade da navegação; não dependem da opção de carregar o mapa.</li>
      <li><strong>Preferência local:</strong> <code>sos_privacidade_google_maps_v1</code>, armazenada no <code>localStorage</code>. Registra “permitido” ou “recusado” para o mapa. Permanece até alteração ou limpeza dos dados do navegador.</li>
      <li><strong>Google Maps:</strong> serviço opcional. Seu iframe não é criado antes de autorização; quando autorizado, o navegador pode transmitir informações técnicas ao Google e receber tecnologias de terceiros.</li>
    </ul>
    <h2>Como escolher ou mudar de ideia?</h2>
    <p>Na <a href="{{ url('/#localizacao') }}">seção de localização da página inicial</a>, use “Preferências de privacidade” para permitir ou rejeitar o mapa. Rejeitar mantém as demais funcionalidades disponíveis. Revogar a preferência remove o iframe desta página, mas não apaga automaticamente cookies eventualmente definidos pelo próprio Google; para isso, use também as configurações do navegador.</p>
    <h2>Recursos de terceiros que não são a opção do mapa</h2>
    <p>O tema atual também referencia bibliotecas e fontes por redes externas (por exemplo, provedores de CDN e fontes). Esses pedidos de recursos podem transmitir dados técnicos de conexão, independentemente da opção do mapa. A revisão de hospedagem local desses recursos integra uma sprint futura de privacidade e performance. Este controle não representa bloqueio universal de todos os serviços de terceiros.</p>
    <h2>Sobre publicidade e análise de acesso</h2>
    <p>Não presumimos que todas as integrações de marketing estejam ativas. Se forem instalados cookies de publicidade, análise ou retargeting, será necessário atualizar este inventário e impedir o carregamento de serviços opcionais antes da escolha aplicável.</p>
    <p>Mais detalhes: <a href="{{ route('privacidade.politica') }}">Política de Privacidade</a>.</p>
  </div>
</main>
@endsection
