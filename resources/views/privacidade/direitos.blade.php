@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/scss/informativo_entry.scss')
@endpush

@section('content')
<main class="termos-uso" id="direitos-titular">
  <div class="campos">
    <h1>Seus direitos sobre dados pessoais</h1>
    <p>Você pode solicitar informações ou exercer os direitos previstos na LGPD em relação aos dados tratados pela S.O.S Mecânica.</p>
    <h2>Como entrar em contato</h2>
    <p>@if(config('oficina.email'))Envie um e-mail para <a href="mailto:{{ config('oficina.email') }}">{{ config('oficina.email') }}</a> ou @endif
      utilize o <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('oficina.whatsapp')) }}" target="_blank" rel="noopener noreferrer">WhatsApp oficial</a>, informando “Solicitação LGPD”. Descreva qual direito deseja exercer. Não envie CPF, RG, documentos nem senhas antes de receber instruções seguras.</p>
    <h2>O que pode ser solicitado</h2>
    <ul>
      <li>Confirmação da existência de tratamento e acesso a dados pessoais.</li>
      <li>Correção de informações desatualizadas ou incorretas.</li>
      <li>Informações sobre compartilhamento, finalidade e duração do tratamento.</li>
      <li>Revogação de consentimentos e, conforme a base legal, eliminação ou bloqueio.</li>
      <li>Outros direitos previstos na legislação, incluindo oposição e portabilidade quando aplicáveis.</li>
    </ul>
    <p>A oficina poderá precisar confirmar a identidade do solicitante antes de entregar informações confidenciais. Algumas informações podem precisar ser conservadas por exigências legais ou para exercício regular de direitos.</p>
    <p>Consulte também a <a href="{{ route('privacidade.politica') }}">Política de Privacidade</a>. Se não obtiver resposta adequada pelos canais da empresa, você poderá buscar orientação da ANPD.</p>
  </div>
</main>
@endsection
