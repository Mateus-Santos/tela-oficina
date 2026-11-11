<div class="sos-lead-form" id="solicitar-orcamento">
    @if (session('marketing_protocolo'))
        <div class="sos-lead-success" role="status">
            <h2>Solicitação registrada</h2>
            <p class="mb-2">Seu protocolo: <strong>{{ session('marketing_protocolo') }}</strong></p>
            <p class="mb-3">Clique abaixo para enviar o resumo à equipe e continuar o atendimento pelo WhatsApp.</p>
            <a class="sos-button sos-button--primary" href="{{ session('marketing_whatsapp') }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> Continuar no WhatsApp</a>
        </div>
        <p class="mt-3 mb-0"><a href="{{ route('marketing.pecas.orcamento') }}">Solicitar outro orçamento</a></p>
        @if(config('marketing.cadastro_cliente_habilitado'))
            <p class="mt-3 mb-0"><a href="{{ route('marketing.cadastro.create') }}">Criar conta para acompanhar meus atendimentos</a></p>
        @endif
    @else
        <h2 class="mb-2">Solicitar orçamento</h2>
        <p class="text-secondary small mb-3">Conte qual peça você precisa. A equipe confirma compatibilidade, disponibilidade e valor.</p>
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Revise os campos destacados antes de enviar.</div>
        @endif
        <form method="POST" action="{{ route('marketing.pecas.store') }}">
            @csrf
            @php($campanhaRegistrada = app(\App\Services\Marketing\AtribuicaoCampanha::class)->registrada(request()))
            @foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid'] as $parametro)
                @php($valorCampanha = $campanhaRegistrada !== [] ? ($campanhaRegistrada[$parametro] ?? '') : old($parametro, request()->query($parametro)))
                <input type="hidden" name="{{ $parametro }}" value="{{ is_string($valorCampanha) ? $valorCampanha : '' }}">
            @endforeach
            <div class="visually-hidden" aria-hidden="true">
                <label for="mkt-website">Deixe este campo vazio</label>
                <input id="mkt-website" type="text" name="website" tabindex="-1" autocomplete="off" value="">
            </div>
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <label class="form-label" for="mkt-nome">Seu nome *</label>
                    <input class="form-control @error('nome') is-invalid @enderror" id="mkt-nome" name="nome" type="text" value="{{ old('nome') }}" maxlength="120" autocomplete="name" required>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label" for="mkt-whatsapp">Seu WhatsApp *</label>
                    <input class="form-control @error('whatsapp') is-invalid @enderror" id="mkt-whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" maxlength="25" inputmode="tel" autocomplete="tel" placeholder="(75) 99999-9999" aria-describedby="mkt-whatsapp-ajuda" required>
                    @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="mkt-whatsapp-ajuda" class="form-text">Digite o DDD e número. A formatação é automática.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="mkt-peca">Peça que você procura (ou selecione no catálogo)</label>
                    <input class="form-control @error('peca') is-invalid @enderror" id="mkt-peca" name="peca" type="text" value="{{ old('peca') }}" maxlength="180" placeholder="Ex.: pivô de suspensão">
                    @error('peca')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="mkt-busca-produto">Buscar peças do catálogo (opcional)</label>
                    <div class="input-group">
                        <input id="mkt-busca-produto" class="form-control" type="search" maxlength="60" placeholder="Ex.: filtro de óleo ou referência W712/95" autocomplete="off">
                        <button id="mkt-botao-buscar" class="btn btn-outline-secondary" type="button">Buscar</button>
                    </div>
                    <p class="form-text mb-2">Selecione até 10 peças, confira a referência do fabricante e ajuste a quantidade de cada uma (1 a 99). Não exibimos preços nem garantimos compatibilidade pelo catálogo.</p>
                    <div id="mkt-produtos-resultado" aria-live="polite" class="small"></div>
                    <ul id="mkt-produtos-lista" class="list-group mt-2"></ul>
                </div>
                <div class="col-12">
                    <strong class="small">Peças selecionadas</strong>
                    <div id="mkt-itens-selecionados" class="mt-2" aria-live="polite"><p class="small text-secondary mb-0">Nenhuma peça selecionada.</p></div>
                    @error('itens')<div class="text-danger small">{{ $message }}</div>@enderror
                    @if ($errors->has('itens.*.produto_id') || $errors->has('itens.*.quantidade'))
                        <div class="text-danger small">Revise as peças e quantidades selecionadas.</div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="mkt-veiculo">Marca e modelo do veículo</label>
                    <input class="form-control @error('veiculo') is-invalid @enderror" id="mkt-veiculo" name="veiculo" type="text" value="{{ old('veiculo') }}" maxlength="160" placeholder="Ex.: Chevrolet Onix" >
                    @error('veiculo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label" for="mkt-ano">Ano</label>
                    <input class="form-control @error('ano') is-invalid @enderror" id="mkt-ano" name="ano" type="text" value="{{ old('ano') }}" maxlength="20" placeholder="Ex.: 2018">
                    @error('ano')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label" for="mkt-motor">Motorização</label>
                    <input class="form-control @error('motor') is-invalid @enderror" id="mkt-motor" name="motor" type="text" value="{{ old('motor') }}" maxlength="80" placeholder="Ex.: 1.4">
                    @error('motor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-8">
                    <label class="form-label" for="mkt-cidade">Cidade (opcional)</label>
                    <input class="form-control @error('cidade') is-invalid @enderror" id="mkt-cidade" name="cidade" type="text" value="{{ old('cidade') }}" maxlength="120" autocomplete="address-level2">
                    @error('cidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-4">
                    <label class="form-label" for="mkt-uf">UF</label>
                    <input class="form-control @error('uf') is-invalid @enderror" id="mkt-uf" name="uf" type="text" value="{{ old('uf') }}" maxlength="2" placeholder="BA" autocomplete="address-level1">
                    @error('uf')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="mkt-detalhes">Mais detalhes (opcional)</label>
                    <textarea class="form-control @error('detalhes') is-invalid @enderror" id="mkt-detalhes" name="detalhes" rows="2" maxlength="600" placeholder="Posição, marca, referência ou outras informações">{{ old('detalhes') }}</textarea>
                    @error('detalhes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input @error('aceite_privacidade') is-invalid @enderror" type="checkbox" id="mkt-aceite" name="aceite_privacidade" value="1" {{ old('aceite_privacidade') ? 'checked' : '' }} required>
                        <label class="form-check-label small" for="mkt-aceite">Autorizo a SOS Mecânica a usar os dados informados para responder a esta solicitação de orçamento e entrar em contato comigo. *</label>
                        @error('aceite_privacidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <p class="form-text small mb-0">Os dados de contato e do veículo são utilizados para responder ao orçamento. A origem comercial da visita pode ser registrada para análise das campanhas.</p>
                    @if(config('marketing.captura_ip_habilitada', false))
                        <p class="form-text small mb-0">Para prevenção de abuso, o endereço IP poderá ser armazenado de forma criptografada e sujeito à rotina de retenção configurada.</p>
                    @endif
                </div>
                <div class="col-12">
                    <button class="sos-button sos-button--primary" type="submit"><i class="bi bi-send"></i> Registrar solicitação</button>
                    <p class="form-text mt-2 mb-0">Não é necessário ter cadastro. A disponibilidade e a compatibilidade serão verificadas pela equipe.</p>
                    @if(config('marketing.cadastro_cliente_habilitado'))
                        <p class="small mt-2"><a href="{{ route('marketing.cadastro.create') }}">Prefere criar sua conta de cliente?</a></p>
                    @endif
                </div>
            </div>
        </form>
        <script>
        (() => {
            const root = document.getElementById('solicitar-orcamento');
            const manual = root?.querySelector('#mkt-peca');
            const buscar = root?.querySelector('#mkt-busca-produto');
            const botao = root?.querySelector('#mkt-botao-buscar');
            const resultado = root?.querySelector('#mkt-produtos-resultado');
            const lista = root?.querySelector('#mkt-produtos-lista');
            const escolhidos = root?.querySelector('#mkt-itens-selecionados');
            const telefone = root?.querySelector('#mkt-whatsapp');
            if (!manual || !buscar || !botao || !resultado || !lista || !escolhidos) return;

            // Máscara visual; a validação no servidor continua usando os dígitos.
            const formatarTelefone = valor => {
                const digitos = valor.replace(/\D/g, '').slice(0, 13);
                const internacional = digitos.startsWith('55') && digitos.length > 11;
                const prefixo = internacional ? '+55 ' : '';
                const local = internacional ? digitos.slice(2) : digitos;
                if (local.length === 0) return prefixo.trimEnd();
                if (local.length <= 2) return `${prefixo}(${local}`;
                const ddd = local.slice(0, 2);
                const numero = local.slice(2);
                if (numero.length <= 4) return `${prefixo}(${ddd}) ${numero}`;
                const inicio = numero.length > 8 ? 5 : 4;
                return `${prefixo}(${ddd}) ${numero.slice(0, inicio)}-${numero.slice(inicio)}`;
            };
            if (telefone) {
                telefone.value = formatarTelefone(telefone.value);
                telefone.addEventListener('input', () => {
                    // Evita jogar o cursor para o final durante uma edição no meio.
                    const posicao = telefone.selectionStart ?? telefone.value.length;
                    const digitosAntes = telefone.value.slice(0, posicao).replace(/\D/g, '').length;
                    telefone.value = formatarTelefone(telefone.value);
                    if (digitosAntes > 0) {
                        let encontrados = 0;
                        let cursor = telefone.value.length;
                        for (let i = 0; i < telefone.value.length; i++) {
                            if (/\d/.test(telefone.value[i])) encontrados++;
                            if (encontrados === digitosAntes) {
                                cursor = i + 1;
                                break;
                            }
                        }
                        telefone.setSelectionRange(cursor, cursor);
                    }
                });
            }

            const selecionados = new Map();
            const anteriores = @json(old('itens', []));
            const endpoint = @json(route('marketing.pecas.produtos'));
            if (Array.isArray(anteriores)) {
                for (const anterior of anteriores) {
                    const id = Number(anterior?.produto_id);
                    const quantidade = Number(anterior?.quantidade);
                    if (Number.isSafeInteger(id) && id > 0 && !selecionados.has(id)
                        && selecionados.size < 10) {
                        selecionados.set(id, {
                            id, nome: `Peça selecionada nº ${id} (confira novamente)`,
                            quantidade: Number.isInteger(quantidade) && quantidade >= 1 && quantidade <= 99
                                ? quantidade : 1,
                        });
                    }
                }
            }

            const elemento = (tag, classe, texto) => {
                const node = document.createElement(tag);
                if (classe) node.className = classe;
                if (texto !== undefined) node.textContent = texto;
                return node;
            };

            const nomeProduto = produto => {
                const partes = [produto.nome];
                if (produto.marca) partes.push(produto.marca);
                if (produto.referencia) partes.push(`Ref.: ${produto.referencia}`);
                else partes.push(`Item #${produto.id} (sem referência cadastrada)`);
                return partes.join(' — ');
            };

            const renderizar = () => {
                escolhidos.replaceChildren();
                manual.required = selecionados.size === 0;
                if (selecionados.size === 0) {
                    escolhidos.append(elemento('p', 'small text-secondary mb-0',
                        'Nenhuma peça selecionada. Descreva a peça no campo acima.'));
                    return;
                }
                for (const [indice, item] of [...selecionados.values()].entries()) {
                    const linha = elemento('div', 'd-flex flex-wrap gap-2 align-items-center mb-2');
                    const titulo = elemento('span', 'small flex-grow-1', item.nome);
                    const id = elemento('input');
                    id.type = 'hidden';
                    id.name = `itens[${indice}][produto_id]`;
                    id.value = String(item.id);
                    const grupoQuantidade = elemento('div', 'd-flex align-items-center gap-1');
                    const rotuloQuantidade = elemento('label', 'small mb-0', 'Qtd.');
                    const quantidade = elemento('input', 'form-control form-control-sm');
                    quantidade.type = 'number';
                    quantidade.id = `mkt-quantidade-${item.id}`;
                    rotuloQuantidade.htmlFor = quantidade.id;
                    quantidade.name = `itens[${indice}][quantidade]`;
                    quantidade.min = '1';
                    quantidade.max = '99';
                    quantidade.step = '1';
                    quantidade.required = true;
                    quantidade.value = String(item.quantidade);
                    quantidade.style.width = '65px';
                    quantidade.setAttribute('aria-label', `Quantidade de ${item.nome}`);
                    quantidade.addEventListener('change', () => {
                        const valor = Number(quantidade.value);
                        if (Number.isInteger(valor) && valor >= 1 && valor <= 99) {
                            item.quantidade = valor;
                        } else {
                            quantidade.value = String(item.quantidade);
                        }
                    });
                    const ajustarQuantidade = delta => {
                        const digitado = Number(quantidade.value);
                        const atual = Number.isInteger(digitado) && digitado >= 1 && digitado <= 99
                            ? digitado : item.quantidade;
                        item.quantidade = Math.max(1, Math.min(99, atual + delta));
                        quantidade.value = String(item.quantidade);
                    };
                    const menos = elemento('button', 'btn btn-sm btn-outline-secondary', '−');
                    menos.type = 'button';
                    menos.setAttribute('aria-label', `Diminuir quantidade de ${item.nome}`);
                    menos.addEventListener('click', () => ajustarQuantidade(-1));
                    const mais = elemento('button', 'btn btn-sm btn-outline-secondary', '+');
                    mais.type = 'button';
                    mais.setAttribute('aria-label', `Aumentar quantidade de ${item.nome}`);
                    mais.addEventListener('click', () => ajustarQuantidade(1));
                    grupoQuantidade.append(rotuloQuantidade, menos, quantidade, mais);
                    const remover = elemento('button', 'btn btn-sm btn-outline-danger', 'Remover');
                    remover.type = 'button';
                    remover.addEventListener('click', () => {
                        selecionados.delete(item.id);
                        renderizar();
                    });
                    linha.append(titulo, id, grupoQuantidade, remover);
                    escolhidos.append(linha);
                }
            };
            renderizar();

            const pesquisar = async () => {
                const termo = buscar.value.trim();
                lista.replaceChildren();
                if (termo.length < 2) {
                    resultado.textContent = 'Digite ao menos duas letras para buscar.';
                    return;
                }
                resultado.textContent = 'Consultando peças disponíveis...';
                botao.disabled = true;
                try {
                    const url = `${endpoint}?busca=${encodeURIComponent(termo)}`;
                    const resposta = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!resposta.ok) throw new Error('Busca indisponível');
                    const dados = await resposta.json();
                    resultado.textContent = dados.data.length
                        ? 'Escolha uma peça para adicioná-la ao pedido.'
                        : 'Nenhuma peça disponível encontrada. Descreva sua necessidade acima.';
                    // IDs distintos podem ter mesmo nome, marca e referência.
                    // Não mesclar produtos do catálogo: desambiguar visualmente.
                    const titulos = dados.data.map(nomeProduto);
                    const repeticoes = new Map();
                    for (const titulo of titulos) {
                        repeticoes.set(titulo, (repeticoes.get(titulo) ?? 0) + 1);
                    }
                    for (const produto of dados.data) {
                        const linha = elemento('li', 'list-group-item d-flex gap-2 align-items-center');
                        const titulo = nomeProduto(produto);
                        const exibicao = repeticoes.get(titulo) > 1
                            ? `${titulo} — Cadastro #${produto.id}` : titulo;
                        const nome = elemento('span', 'flex-grow-1 small', exibicao);
                        const adicionar = elemento('button', 'btn btn-sm btn-outline-primary', 'Adicionar');
                        adicionar.type = 'button';
                        adicionar.addEventListener('click', () => {
                            const id = Number(produto.id);
                            if (selecionados.has(id)) {
                                resultado.textContent = 'Essa peça já está no seu pedido.';
                                return;
                            }
                            if (selecionados.size >= 10) {
                                resultado.textContent = 'Limite de dez peças por solicitação.';
                                return;
                            }
                            selecionados.set(id, {id, nome: exibicao, quantidade: 1});
                            renderizar();
                        });
                        linha.append(nome, adicionar);
                        lista.append(linha);
                    }
                } catch (_) {
                    resultado.textContent = 'Não foi possível consultar agora. Descreva a peça no campo acima.';
                } finally {
                    botao.disabled = false;
                }
            };
            botao.addEventListener('click', pesquisar);
            buscar.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    pesquisar();
                }
            });
        })();
        </script>
    @endif
</div>
