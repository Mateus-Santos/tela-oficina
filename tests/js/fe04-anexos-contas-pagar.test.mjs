import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(
    new URL('../../resources/views/contas_pagar/show/_anexos.blade.php', import.meta.url),
    'utf8'
);

const editable = source.split('{{-- Edicao restrita aos vinculos diretos desta conta')[1]
    ?.split('@if ($conta->compra)')[0];

test('FE-04: modais da conta existem somente para vinculos diretos elegiveis', () => {
    assert.ok(editable, 'bloco exclusivo de edicao ausente');
    assert.match(editable, /@if \(\$conta->status !== 'cancelada' && !\$conta->estaPaga\(\)\)/);
    assert.match(editable, /@foreach \(\$conta->anexosVinculos as \$vinculo\)/);
    assert.doesNotMatch(editable, /@foreach \(\$anexosCompra/);
    assert.match(source, /data-bs-target="#modalEditarAnexoPagar\{\{ \$vinculo->id \}\}"/);
    assert.match(editable, /id="modalEditarAnexoPagar\{\{ \$vinculo->id \}\}"/);
});

test('FE-04: formulario usa somente endpoint existente e contrato de validacao', () => {
    assert.match(editable, /route\('anexos.update', \$vinculo\)/);
    assert.match(editable, /@csrf/);
    assert.match(editable, /@method\('PATCH'\)/);
    for (const name of ['tipo', 'arquivo', 'observacoes']) {
        assert.match(editable, new RegExp(`name="${name}"`));
    }
    assert.match(editable, /maxlength="1000"/);
    assert.match(editable, /Máximo de 2 MB/);
    assert.doesNotMatch(editable, /name="arquivo"[^>]*required/);
});

// Estes testes sao estruturais; nao substituem teste de sessao autenticada.
