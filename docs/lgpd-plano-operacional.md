# Check-list operacional LGPD — S.O.S Mecânica

## Antes da publicação desta sprint

**Gate obrigatório de publicação S15:** conferir a identidade do controlador, preencher
`OFICINA_RAZAO_SOCIAL`, `OFICINA_CNPJ` e `OFICINA_PRIVACIDADE_EMAIL`
com valores oficiais e válidos no ambiente de produção (ou documentar formalmente
por que algum campo não se aplica). Não usar dados fictícios. Confirmar que o
contato é atendido e que as informações coincidem com os registros da empresa.
Essas variáveis são opcionais para testes e não são preenchidas automaticamente.
Revisar o aviso público e os fornecedores efetivamente usados antes do deploy.

- [ ] Confirmar **razão social, CNPJ** e o papel da empresa como controladora. A página pública mostra o nome comercial e o endereço; razão social e CNPJ só aparecem quando houver valores configurados e verificados.
- [ ] Confirmar o endereço público, canais de contato, responsável pelo recebimento de solicitações LGPD e prazo interno de resposta; definir e divulgar e-mail especializado se disponível.
- [ ] Homologar com jurídico/privacidade as finalidades e hipóteses legais, termos de uso, regras de relação de consumo, retenção e conteúdo informativo.
- [ ] Conferir inventário de recursos externos efetivamente carregados no navegador (fontes Google, CDNs Bootstrap/jQuery, WhatsApp, Google Maps) e cookies definidos em navegação autenticada e anônima.
- [ ] Revisar divulgação de cookies Laravel, dependentes de configuração, com instrumentos do navegador e cabeçalhos reais.
- [ ] Validar se a política reflete todos os recursos efetivamente habilitados em produção (Marketing, Forge, portal, formulários, cadastro, e-mails etc.).
- [ ] Testar a partir de navegadores sem extensões e com bloqueadores; o mapa não deve ser carregado por padrão.
- [ ] Verificar build, testes e regressão de login e navegação; aplicar apenas por PR revisado, com deploy separado.

## Sprint 2 — Governança e pedidos de titulares

- [ ] Inventário de dados por módulo (clientes, colaboradores, veículos, notas/OS, estoque, financeiro, leads, arquivos, imagens, backups, logs, fornecedores); coletar categoria, origem, finalidade, base legal, acessos e retenção.
- [ ] Classificar cada integração/fornecedor (hospedagem, e-mail, WhatsApp, Google, catálogos de peças, ferramentas de suporte) por papel contratual e eventual transferência internacional.
- [ ] Procedimento autenticado, sem exposição de dados, para confirmação, acesso, correção, oposição, eliminação quando cabível, portabilidade e revogação. Registrar protocolo e resposta de forma restrita.
- [ ] Matriz de guarda e descarte, inclusive documentos fiscais, notas, OS, cadastros não convertidos, arquivos e backups. Não apagar dados sujeitos a retenção obrigatória.
- [ ] Definir política de atendimento e verificação de identidade com acesso mínimo, revisando prazos legais e procedimentos da ANPD.

## Sprint 3 — Segurança, infraestrutura e auditoria

- [ ] Revisar mínimo privilégio, autenticação, MFA quando disponível, logs, segregação de ambientes, exportações e acesso administrativo.
- [ ] Inspecionar backup e recuperação, retenção de cópias, criptografia, chaves, limites de acesso e auditoria de restauração.
- [ ] Processo de resposta a incidentes com classificação, preservação de evidências, comunicação cabível à ANPD/titulares e registro de incidentes. Verificar o regulamento vigente e condições especiais de agentes de pequeno porte.
- [ ] Revisar CSP sem enfraquecê-la; remover eventos inline incompatíveis e recursos CDN ou hospedá-los localmente onde viável.
- [ ] Implementar scanner periódico de cookies/scripts de terceiros e controle por finalidade quando houver Analytics/Ads/remarketing (com opt-in apropriado).
- [ ] Validar conformidade dos fluxos Forge/catálogo de fornecedores e compartilhamento com APIs antes de ativar novas fontes.

## Observações importantes

- O sistema já contém tratamento de leads Marketing condicionado a aceite e um comando de retenção parcial `marketing:retencao`; isso **não** resolve a guarda de todos os dados da oficina.
- O armazenamento local da escolha do mapa não é recibo auditável de consentimento. Caso se exija prova de consentimento, projetar backend com minimização e retenção adequada.
- A Política de Privacidade é um aviso informativo e não substitui o mapeamento efetivo dos dados pessoais e dos fornecedores.

## Referências oficiais

- ANPD — Guia de cookies: https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia-orientativo-cookies-e-protecao-de-dados-pessoais.pdf
- ANPD — Direitos dos titulares: https://www.gov.br/anpd/pt-br/assuntos/titular-de-dados/direito-dos-titulares
- ANPD — Comunicação de incidentes: https://www.gov.br/anpd/pt-br/canais_atendimento/agente-de-tratamento/comunicado-de-incidente-de-seguranca-cis
