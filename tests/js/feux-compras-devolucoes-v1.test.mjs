import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    lerQuantidadeMilessimos,
    resumirItensDevolucao,
} from '../../resources/js/compras/devolucoes-documental.js';

const get = arquivo => readFileSync(arquivo, 'utf8');
const linha = (quantidade, modalidade = 'recusado') => ({
    id: '7', modalidade, quantidade, saldoRecusado: '2.500', saldoEstoque: '1.500',
});

test('quantidade exata em milésimos não perde precisão', () => {
    assert.equal(lerQuantidadeMilessimos('1.001'), 1001);
    assert.equal(lerQuantidadeMilessimos('0.010'), 10);
    assert.equal(lerQuantidadeMilessimos('1,250'), 1250);
    assert.equal(lerQuantidadeMilessimos(''), 0);
    assert.equal(lerQuantidadeMilessimos('1.2345'), null);
    assert.equal(lerQuantidadeMilessimos('-2'), null);
    assert.equal(lerQuantidadeMilessimos('abc'), null);
});

test('recusado usa somente saldo recusável, sem estoque', () => {
    const r = resumirItensDevolucao([linha('2.500')]);
    assert.deepEqual([r.itens, r.milessimos, r.valido], [1, 2500, true]);
    assert.deepEqual(r.erros, []);
});

test('estoque possui limite distinto, sem alterar regra documental', () => {
    const r = resumirItensDevolucao([linha('2.000', 'estoque')]);
    assert.equal(r.valido, false);
    assert.match(r.erros[0].mensagem, /supera o saldo/);
});

test('quantidade zerada e modalidade inválida não autorizam envio', () => {
    assert.equal(resumirItensDevolucao([linha('0')]).valido, false);
    assert.equal(resumirItensDevolucao([linha('1', 'outra')]).valido, false);
});

test('Blade mantém confirmação documental e backend como fonte de verdade', () => {
    const documento = get('resources/views/compra/devolucoes/documental.blade.php');
    const resumo = get('resources/views/compra/devolucoes/resumo.blade.php');
    assert.match(documento, /data-feux-devolucao-documental/);
    assert.match(documento, /data-saldo-recusado/);
    assert.match(documento, /data-feux-submit-devolucao/);
    assert.match(documento, /confirmacao_fisica/);
    assert.match(documento, /confirmacao_financeira/);
    assert.match(documento, /não é emissão de NF-e/);
    assert.match(resumo, /sem abatimento financeiro/);
    assert.match(resumo, /Conciliação financeira pendente/);
});
