// FEUX-07: rascunho voluntario, somente na aba atual; nunca inclui arquivos ou tokens.
export const PRAZO_RASCUNHO_MS = 30 * 60 * 1000;
const PREFIXO = 'sosmecanica:feux07:compra:v1:';
const CAMPOS_CABECALHO = [
    'fornecedor_id', 'numero_nf', 'serie_nf', 'chave_nf', 'data_emissao',
    'data_entrada', 'desconto', 'frete', 'outras_despesas', 'observacoes',
];
const CAMPOS_ITEM = ['produto_id', 'descricao', 'quantidade', 'valor_unitario', 'desconto'];
const CAMPOS_ANEXO = ['tipo', 'observacoes'];

// Pureza permite testar vencimento e saneamento sem navegador.
export function rascunhoValido(objeto, agora = Date.now()) {
    return Boolean(objeto && objeto.versao === 1 &&
        Number.isSafeInteger(objeto.criadoEm) &&
        objeto.criadoEm <= agora && agora - objeto.criadoEm < PRAZO_RASCUNHO_MS &&
        objeto.cabecalho && typeof objeto.cabecalho === 'object' && !Array.isArray(objeto.cabecalho) &&
        Array.isArray(objeto.itens) && objeto.itens.length <= 100 &&
        Array.isArray(objeto.anexos) && objeto.anexos.length <= 30);
}
export function filtrarCampos(objeto, permitidos) {
    return Object.fromEntries(permitidos.map(c => [c, String(objeto?.[c] ?? '').slice(0, 2000)]));
}
export function saneamentoRascunho(rascunho) {
    return {
        versao: 1, criadoEm: rascunho.criadoEm,
        cabecalho: filtrarCampos(rascunho.cabecalho, CAMPOS_CABECALHO),
        itens: rascunho.itens.slice(0, 100).map(x => ({
            ...filtrarCampos(x, CAMPOS_ITEM),
            busca: String(x?.busca ?? '').slice(0, 255),
        })),
        anexos: rascunho.anexos.slice(0, 30).map(x => filtrarCampos(x, CAMPOS_ANEXO)),
    };
}
function valorCampo(container, nome) {
    const elemento = container.querySelector(`[name="${nome}"]`);
    return elemento?.value ?? '';
}
function porSufixo(linha, sufixo) {
    return linha.querySelector(`[name$="[${sufixo}]"]`);
}
function valorLinha(linha, campo) { return porSufixo(linha, campo)?.value ?? ''; }
function coletar(form) {
    const cabecalho = Object.fromEntries(CAMPOS_CABECALHO.map(c => [c, valorCampo(form, c)]));
    const itens = Array.from(form.querySelectorAll('.compra-item')).map(linha => ({
        ...Object.fromEntries(CAMPOS_ITEM.map(c => [c, valorLinha(linha, c)])),
        busca: linha.querySelector('.item-produto-busca')?.value ?? '',
    }));
    const anexos = Array.from(form.querySelectorAll('.anexo-item')).map(linha =>
        Object.fromEntries(CAMPOS_ANEXO.map(c => [c, valorLinha(linha, c)])));
    if (itens.length > 100 || anexos.length > 30) throw Error('Limite de linhas excedido');
    return saneamentoRascunho({ versao: 1, criadoEm: Date.now(), cabecalho, itens, anexos });
}
function atribuir(elemento, valor) {
    if (!elemento || elemento.type === 'file' || elemento.type === 'hidden' &&
        (elemento.name === '_token' || elemento.name === '_method')) return;
    elemento.value = String(valor ?? '');
    elemento.dispatchEvent(new Event('input', { bubbles: true }));
    elemento.dispatchEvent(new Event('change', { bubbles: true }));
}
function restaurarLinhas(form, seletor, botaoId, dados, campos) {
    if (!dados.length) return;
    const botao = document.getElementById(botaoId);
    const atuais = () => Array.from(form.querySelectorAll(seletor));
    if (!atuais().length || dados.length > 100) throw Error('Formulario de itens diferente');
    while (atuais().length < dados.length) {
        if (!botao || botao.disabled) throw Error('Nao foi possivel restaurar linhas');
        const antes = atuais().length;
        botao.click();
        if (atuais().length <= antes) throw Error('Nao foi possivel adicionar linha');
    }
    while (atuais().length > dados.length && atuais().length > 1) {
        const linhas = atuais();
        const remover = linhas[linhas.length - 1].querySelector(
            seletor === '.compra-item' ? '.btn-remover-item' : '.btn-remover-anexo');
        if (!remover) throw Error('Nao foi possivel remover linha excedente');
        remover.click();
        if (atuais().length >= linhas.length) throw Error('Linha nao removida');
    }
    atuais().forEach((linha, indice) => {
        for (const campo of campos) atribuir(porSufixo(linha, campo), dados[indice]?.[campo]);
        if (seletor === '.compra-item') {
            atribuir(linha.querySelector('.item-produto-busca'), dados[indice]?.busca);
        }
    });
}
function restaurar(form, dados) {
    if (!rascunhoValido(dados)) throw Error('Rascunho expirado ou invalido');
    const r = saneamentoRascunho(dados);
    for (const campo of CAMPOS_CABECALHO) {
        atribuir(form.querySelector(`[name="${campo}"]`), r.cabecalho[campo]);
    }
    restaurarLinhas(form, '.compra-item', 'btn-adicionar-item', r.itens, CAMPOS_ITEM);
    if (r.anexos.length && form.querySelector('.anexo-item')) {
        restaurarLinhas(form, '.anexo-item', 'btn-adicionar-anexo', r.anexos, CAMPOS_ANEXO);
    }
    // Campos file nunca sao lidos, armazenados ou restaurados.
}
export function inicializarRascunhoCompra() {
    const form = document.getElementById('compra-form');
    if (!form) return;
    // Sem cookies ou armazenamento duradouro nem redirecionamentos externos.
    let store;
    try { store = window.sessionStorage; } catch { return; }
    const chave = PREFIXO + window.location.pathname;
    const area = document.createElement('div');
    area.className = 'alert alert-light border mt-3 feux07-rascunho';
    area.setAttribute('role', 'region');
    area.setAttribute('aria-label', 'Rascunho temporario da compra');
    const texto = document.createElement('p');
    texto.className = 'small mb-2';
    texto.textContent = 'Rascunho temporário nesta aba (30 min). Salve manualmente antes de enviar. Inclui dados da compra, mas nunca arquivos. Em dispositivo compartilhado, descarte ao terminar.';
    const aviso = document.createElement('div');
    aviso.className = 'small mt-2';
    aviso.setAttribute('role', 'status');
    aviso.setAttribute('aria-live', 'polite');
    const botoes = document.createElement('div');
    botoes.className = 'd-flex flex-wrap gap-2';
    function botao(rotulo, estilo, acao) {
        const b = document.createElement('button');
        b.type = 'button'; b.className = `btn btn-sm ${estilo}`;
        b.textContent = rotulo; b.addEventListener('click', acao);
        botoes.appendChild(b); return b;
    }
    function ler() {
        let valor;
        try { valor = JSON.parse(store.getItem(chave) || 'null'); } catch { valor = null; }
        if (!rascunhoValido(valor)) { try { store.removeItem(chave); } catch {} return null; }
        return valor;
    }
    const salvar = botao('Guardar rascunho nesta aba', 'btn-outline-primary', () => {
        try {
            store.setItem(chave, JSON.stringify(coletar(form)));
            aviso.textContent = 'Rascunho salvo nesta aba por até 30 minutos. Arquivos precisam ser selecionados novamente.';
        } catch { aviso.textContent = 'Não foi possível guardar o rascunho. Não feche esta página antes de copiar seus dados.'; }
        atualizar();
    });
    const recuperar = botao('Recuperar rascunho', 'btn-outline-secondary', () => {
        const valor = ler();
        if (!valor) { aviso.textContent = 'Rascunho inexistente ou expirado.'; atualizar(); return; }
        if (!window.confirm('Substituir os dados atuais pelos do rascunho desta aba? Arquivos não serão restaurados.')) return;
        try { restaurar(form, valor); aviso.textContent = 'Rascunho restaurado. Confira valores, produtos e selecione novamente os arquivos antes de enviar.'; }
        catch { aviso.textContent = 'Restauração incompleta. Revise os campos antes de salvar.'; }
    });
    const descartar = botao('Descartar rascunho', 'btn-outline-danger', () => {
        try { store.removeItem(chave); } catch {}
        aviso.textContent = 'Rascunho descartado.'; atualizar();
    });
    function atualizar() {
        const existe = Boolean(ler()); recuperar.hidden = !existe; descartar.hidden = !existe;
    }
    area.append(texto, botoes, aviso);
    form.before(area);
    atualizar();
}
