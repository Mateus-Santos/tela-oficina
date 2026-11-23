import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { PRAZO_RASCUNHO_MS, rascunhoValido, saneamentoRascunho } from '../../resources/js/compras/rascunho-sessao.js';

test('rascunho vence em 30 minutos, nao aceita objetos malformados ou futuros', () => {
    const agora = Date.now();
    const base = { versao: 1, criadoEm: agora - 1000, cabecalho: {}, itens: [], anexos: [] };
    assert.equal(rascunhoValido(base, agora), true);
    assert.equal(rascunhoValido({ ...base, criadoEm: agora - PRAZO_RASCUNHO_MS }, agora), false);
    assert.equal(rascunhoValido({ ...base, criadoEm: agora + 1 }, agora), false);
    assert.equal(rascunhoValido({ ...base, versao: 2 }, agora), false);
    assert.equal(rascunhoValido({ ...base, itens: Array(101).fill({}) }, agora), false);
});
test('saneamento preserva dados textuais e exclui tokens, arquivos e campos desconhecidos', () => {
    const base = { criadoEm: 10, cabecalho: { numero_nf: '123', _token: 'segredo' },
        itens: [{ produto_id: '5', quantidade: '2', arquivo: 'NUNCA' }],
        anexos: [{ tipo: 'nf', observacoes: 'nota', arquivo: 'NUNCA' }], segredos: 'NUNCA' };
    const resultado = saneamentoRascunho(base);
    assert.equal(resultado.cabecalho.numero_nf, '123');
    assert.equal(resultado.itens[0].produto_id, '5');
    assert.equal(resultado.anexos[0].tipo, 'nf');
    assert.doesNotMatch(JSON.stringify(resultado), /segredo|_token|NUNCA|arquivo/);
});
test('rascunho e voluntario, somente em sessionStorage e isolado por caminho', () => {
    const s = readFileSync('resources/js/compras/rascunho-sessao.js','utf8');
    const entrada = readFileSync('resources/js/compra.js','utf8');
    assert.match(s, /window\.sessionStorage/);
    assert.match(s, /window\.location\.pathname/);
    assert.match(s, /window\.confirm\(/);
    assert.match(s, /Guardar rascunho nesta aba/);
    assert.doesNotMatch(s, /localStorage|fetch\(/);
    assert.match(entrada, /inicializarRascunhoCompra\(\)/);
});
