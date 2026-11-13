@extends('layouts.layout')

@push('p04-css-pagina')
    @vite('resources/scss/informativo_entry.scss')
@endpush

@section('content')
<main class="termos-uso">
  <div class="campos">
    <h1 class="mb-4">Termos de Uso — S.O.S Mecânica</h1>
    <p class="small text-muted">Versão 2026-11-12. Estes termos regulam o uso do portal e não substituem contratos, ordens de serviço ou normas de defesa do consumidor.</p>

    <div class="content">
      <h2>1. Objetivo do Sistema</h2>
      <p>
        O Sistema de Acompanhamento para Oficina Mecânica foi desenvolvido para facilitar a comunicação entre oficinas mecânicas e seus clientes, permitindo que estes acompanhem em tempo real o progresso das manutenções realizadas em seus veículos. Através deste sistema, os clientes poderão:
      </p>
      <ul>
        <li>Visualizar o histórico de serviços;</li>
        <li>Acompanhar o status atual dos reparos e manutenções;</li>
        <li>Receber notificações e atualizações sobre o andamento dos serviços.</li>
      </ul>

      <h2>2. Condições de Uso</h2>
      <p>
        O portal disponibiliza informações e funcionalidades para a relação com a oficina. Seu uso deve respeitar as condições apresentadas nesta página e as normas aplicáveis. As condições de cada reparo, orçamento, venda ou ordem de serviço são tratadas nos respectivos documentos e atendimentos.
      </p>
      <p>
        A <a href="{{ route('privacidade.politica') }}">Política de Privacidade</a> explica o tratamento dos dados. A leitura ou utilização do site não significa consentimento geral para finalidades opcionais; quando necessário, a escolha será solicitada separadamente.
      </p>

      <h2>3. Cadastro e Responsabilidades do Usuário</h2>
      <p>
        Para acessar as funcionalidades do Sistema, o Usuário deverá fornecer informações precisas e atualizadas sobre si e seu veículo. O Usuário é responsável por manter a segurança de suas credenciais de acesso e por todas as atividades realizadas através de sua conta. Em caso de suspeita de uso não autorizado, o Usuário deverá informar imediatamente à oficina mecânica responsável.
      </p>

      <h2>4. Limitação de Responsabilidade</h2>
      <p>
        O Sistema foi projetado para fornecer informações de acompanhamento e atualização, mas não substitui a comunicação direta com a oficina ou o diagnóstico técnico de profissionais qualificados. A oficina mecânica se reserva o direito de modificar o status ou as informações dos serviços conforme necessário, e o Sistema não garante a precisão em tempo real dos dados fornecidos.
      </p>
      <p>
        Informações exibidas no portal podem exigir confirmação da equipe. Eventuais limitações técnicas não afastam direitos assegurados pela legislação aplicável, inclusive normas de proteção e defesa do consumidor e de proteção de dados pessoais.
      </p>

      <h2>5. Privacidade e Proteção de Dados</h2>
      <p>
        O portal pode tratar dados de cadastro, contato, veículos, atendimentos, ordens de serviço, notas e orçamentos, conforme a finalidade e a base legal aplicável. Consulte a <a href="{{ route('privacidade.politica') }}">Política de Privacidade</a>, a <a href="{{ route('privacidade.cookies') }}">Política de Cookies</a> e o <a href="{{ route('privacidade.direitos') }}">canal para exercer seus direitos</a>.
      </p>

      <h2>6. Direitos de Propriedade Intelectual</h2>
      <p>
        Todos os direitos sobre o Sistema, incluindo código-fonte, design, logotipos e conteúdo são protegidos por direitos de propriedade intelectual e pertencem ao desenvolvedor ou à oficina mecânica licenciada para utilizá-lo. O uso do Sistema pelo Usuário é limitado a consultas e acompanhamento de status e não concede qualquer direito de reprodução, modificação ou distribuição do Sistema.
      </p>

      <h2>7. Modificações dos Termos de Uso</h2>
      <p>
        Estes Termos de Uso podem ser atualizados periodicamente para refletir mudanças no Sistema ou em requisitos legais. Sempre que mudanças significativas forem realizadas, a oficina mecânica notificará os Usuários para garantir ciência e aceitação das novas condições.
      </p>

      <h2>8. Legislação e Solução de Conflitos</h2>
      <p>
        Aplica-se a legislação brasileira, observadas as regras legais de competência e os direitos dos consumidores e titulares de dados. Para esclarecimentos, utilize os canais de atendimento divulgados no site.
      </p>
    </div>

    <div class="mt-4">
      <a href="/" class="btn btn-primary">Voltar para a Página Inicial</a>
    </div>
  </div>
</main>
@endsection
