import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const source = readFileSync(resolve(dirname(fileURLToPath(import.meta.url)), '../../resources/js/consentimento-mapa.js'), 'utf8');

function ambiente(valorInicial = null) {
    const storage = new Map();
    if (valorInicial) storage.set('sos_privacidade_google_maps_v1', valorInicial);
    let frame = null;
    const botao = (acao = null) => ({
        handlers: {},
        getAttribute: () => acao,
        addEventListener(nome, callback) { this.handlers[nome] = callback; },
        click() { this.handlers.click(); },
        focus() { this.focused = true; },
    });
    const permitirMapa = botao('permitir');
    const permitirBanner = botao('permitir');
    const recusar = botao('recusar');
    const preferencias = botao();
    const placeholder = { hidden: false };
    const mapa = {
        getAttribute: () => 'https://www.google.com/maps?q=oficina&output=embed',
        querySelector(selector) {
            if (selector === '[data-sos-map-placeholder]') return placeholder;
            if (selector === '[data-sos-map-preferences]') return preferencias;
            if (selector === 'iframe[data-sos-map-frame]') return frame;
            return null;
        },
        insertBefore(element) { frame = element; },
    };
    const aviso = {
        hidden: true,
        querySelector: () => recusar,
    };
    const document = {
        readyState: 'complete',
        querySelector(selector) {
            return selector === '[data-sos-map]' ? mapa : selector === '[data-sos-cookie-banner]' ? aviso : null;
        },
        querySelectorAll: () => [permitirMapa, permitirBanner, recusar],
        createElement() {
            return {
                attrs: {},
                setAttribute(name, value) { this.attrs[name] = value; },
                remove() { frame = null; },
            };
        },
    };
    const window = {
        localStorage: {
            getItem(key) { return storage.get(key) ?? null; },
            setItem(key, value) { storage.set(key, value); },
        },
    };
    runInNewContext(source, { document, window });
    return {
        get frame() { return frame; },
        storage, aviso, placeholder, preferencias, permitirMapa, permitirBanner, recusar,
    };
}

test('sem escolha, o iframe não existe e o aviso é exibido', () => {
    const a = ambiente();
    assert.equal(a.frame, null);
    assert.equal(a.aviso.hidden, false);
    assert.equal(a.placeholder.hidden, false);
});

test('rejeitar mantém mapa ausente; permitir cria iframe somente após clique', () => {
    const a = ambiente();
    a.recusar.click();
    assert.equal(a.frame, null);
    assert.equal(a.storage.get('sos_privacidade_google_maps_v1'), 'recusado');
    a.preferencias.click();
    assert.equal(a.aviso.hidden, false);
    a.permitirBanner.click();
    assert.ok(a.frame);
    assert.equal(a.frame.src, 'https://www.google.com/maps?q=oficina&output=embed');
    assert.equal(a.placeholder.hidden, true);
    assert.equal(a.aviso.hidden, true);
    a.preferencias.click();
    a.recusar.click();
    assert.equal(a.frame, null);
    assert.equal(a.placeholder.hidden, false);
});

test('preferência recusada persiste sem iframe; permitida carrega mapa', () => {
    const rejeitado = ambiente('recusado');
    assert.equal(rejeitado.aviso.hidden, true);
    assert.equal(rejeitado.frame, null);
    const permitido = ambiente('permitido');
    assert.ok(permitido.frame);
    assert.equal(permitido.aviso.hidden, true);
});
