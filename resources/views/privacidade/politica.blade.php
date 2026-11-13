@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/scss/informativo_entry.scss')
@endpush

@section('content')
<main class="termos-uso" id="politica-privacidade">
  <div class="campos">
    <h1>Política de Privacidade — S.O.S Mecânica</h1>
    <p class="small text-muted">Versão 2026-11-12. Esta página explica o tratamento de dados no site e no sistema de atendimento da oficina.</p>
    <h2>1. Identificação do controlador e contato</h2>
    <p>A <strong>S.O.S Mecânica</strong>, com estabelecimento em {{ config('oficina.endereco') }}, é a controladora dos dados pessoais tratados nas atividades próprias de atendimento, orçamento e operação da oficina descritas nesta política.</p>
    @if (filled(config('oficina.razao_social')))
      <p><strong>Razão social:</strong> {{ config('oficina.razao_social') }}.</p>
    @endif
    @if (filled(config('oficina.cnpj')))
      <p><strong>CNPJ:</strong> {{ config('oficina.cnpj') }}.</p>
    @endif
    <p>
      <strong>Canal para solicitações sobre dados pessoais:</strong>
      @if (filled(config('oficina.privacidade_email')))
        <a href="mailto:{{ config('oficina.privacidade_email') }}">{{ config('oficina.privacidade_email') }}</a> ou
      @elseif (filled(config('oficina.email')))
        <a href="mailto:{{ config('oficina.email') }}">{{ config('oficina.email') }}</a> ou
      @endif
      <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('oficina.whatsapp')) }}" target="_blank" rel="noopener noreferrer">WhatsApp da oficina</a>.
      Informe que o assunto é proteção de dados. Não envie senhas, documentos ou informações sensíveis antes de receber instruções para uma verificação segura de identidade.
    </p>

    <h2>2. Quais dados podem ser tratados</h2>
    <p>Dependendo do relacionamento: nome, telefone, WhatsApp, e-mail, dados de cadastro e autenticação, informações dos veículos, dados do atendimento, orçamentos, peças solicitadas, ordens de serviço, notas, compras, pagamentos e registros necessários à segurança do sistema.</p>
    <p>Em formulários de orçamento, parâmetros de origem como UTM e gclid podem ser preservados para compreender a procedência da solicitação. A captura do IP em leads de Marketing é opcional e desabilitada por padrão na implementação auditada; sua ativação exige revisão de finalidade e configuração.</p>

    <h2>3. Para que usamos os dados e bases legais</h2>
    <ul>
      <li>Responder solicitações, elaborar orçamentos e prestar serviços: medidas pré-contratuais e execução contratual, conforme o caso.</li>
      <li>Registrar operações, documentos e obrigações fiscais: cumprimento de obrigações legais e regulatórias.</li>
      <li>Autenticar usuários, prevenir abusos e proteger contas: segurança e legítimo interesse, quando cabível, respeitando os direitos dos titulares.</li>
      <li>Permitir a exibição do Google Maps incorporado: escolha opcional e específica do visitante.</li>
    </ul>
    <p>O consentimento para o mapa não constitui consentimento para publicidade, perfilamento ou comunicações promocionais. Caso essas atividades sejam adotadas no futuro, os respectivos avisos e fundamentos serão avaliados separadamente.</p>

    <h2>4. Compartilhamento e fornecedores</h2>
    <p>Para operar o serviço, determinados dados podem ser tratados por fornecedores de hospedagem, serviços de e-mail e infraestrutura de comunicação. Links externos, como WhatsApp e Google Maps, seguem também as políticas desses provedores após sua abertura. Ao autorizar o mapa incorporado, o navegador realiza conexões com o Google e pode receber cookies ou tecnologias de terceiros. A lista de prestadores, contratos e possíveis transferências internacionais permanece sujeita a inventário interno.</p>

    <h2>5. Cookies e tecnologias semelhantes</h2>
    <p>Usamos mecanismos necessários à sessão, proteção CSRF e autenticação. A preferência opcional do mapa é guardada no armazenamento local do navegador, sem envio de um identificador de consentimento ao servidor nesta versão. O mapa do Google fica desativado por padrão e não é incorporado antes de autorização. Veja a <a href="{{ route('privacidade.cookies') }}">Política de Cookies</a> para detalhes e alteração da escolha.</p>

    <h2>6. Segurança e conservação</h2>
    <p>Adotamos controles técnicos e de acesso compatíveis com as funcionalidades do sistema. Nenhuma medida elimina integralmente riscos de segurança. Os dados são mantidos conforme a finalidade, as obrigações legais e a necessidade de defesa de direitos. A matriz operacional de retenção para os demais cadastros e documentos está em revisão; não se declara aqui um prazo único sem validação.</p>

    <h2>7. Direitos dos titulares</h2>
    <p>Você pode solicitar confirmação de tratamento, acesso, correção, informações sobre compartilhamento e, nas hipóteses legais, anonimização, bloqueio, eliminação, portabilidade, oposição e revogação do consentimento. A eliminação pode depender de obrigações legais de guarda. Veja <a href="{{ route('privacidade.direitos') }}">como exercer seus direitos</a>.</p>

    <h2>8. Atualizações</h2>
    <p>Esta política poderá ser atualizada para refletir mudanças nos processos e fornecedores. A versão e a data de revisão serão indicadas nesta página. A aprovação formal desta política requer conferência dos fluxos reais e dos dados da empresa.</p>
  </div>
</main>
@endsection
