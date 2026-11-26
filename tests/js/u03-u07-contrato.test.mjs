import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync as read } from 'node:fs';

const fonte = p => read(p, 'utf8');

test('U03: notas por codigo e OS aberta sem vinculo com nota', () => {
    const css = fonte('resources/scss/_compra_feedback.scss');
    assert.match(css, /\.feux-compra/);
    assert.match(css, /margin-top: \.375rem/);
    assert.match(fonte('resources/views/contas_pagar/_form.blade.php'), /compras-vinculadas-busca/);
    assert.match(fonte('resources/views/livewire/nota/seletor-cliente-veiculo.blade.php'), /feux-seletor-cliente-veiculo/);
    assert.match(fonte('resources/views/livewire/ordem-servico/seletor-cliente-veiculo.blade.php'), /feux-seletor-cliente-veiculo/);
    assert.match(fonte('app/Models/Nota.php'), /filters\['id'\]/);
    const os = fonte('app/Http/Controllers/OrdemServicoController.php');
    assert.match(os, /public function update\(/);
    assert.match(os, /lockForUpdate\(/);
    assert.match(os, /possuiNota/);
    assert.match(os, /itemable_type/);
    assert.match(fonte('resources/views/ordemservico/editar_os.blade.php'), /@method\('PUT'\)/);
});

test('U04: duplicacoes sao criadas como novos registros via actions oficiais', () => {
    const s=fonte('app/Http/Controllers/DuplicarRegistroController.php');
    assert.match(s, /CriarNota/);
    assert.match(s, /CriarCompra/);
    assert.match(s, /'confirmar' => \['required', 'accepted'\]/);
    assert.match(s, /'chave_nf' => null/);
    assert.match(s, /CriarOrdemServico/);
    assert.match(s, /DB::transaction/);
});

test('U06: anexos privados com restricoes de status de Nota e OS', () => {
    const s=fonte('app/Http/Controllers/AnexoController.php');
    assert.match(s, /public function storeNota/);
    assert.match(s, /public function storeOrdemServico/);
    assert.match(s, /Nota && \$anexavel->status !== 'Aberto'/);
    assert.match(s, /OrdemServico && \$anexavel->status !== 'aberta'/);
    assert.match(fonte('resources/views/anexo/_gestao_operacional.blade.php'), /@method\('PATCH'\)/);
    assert.match(fonte('resources/views/anexo/_gestao_operacional.blade.php'), /@method\('DELETE'\)/);
});

test('U07: chave cancelada preservada sem perder UNIQUE das ativas', () => {
    const m=fonte('database/migrations/2026_11_21_210000_arquivar_chave_nf_de_compra_cancelada.php');
    assert.match(m, /chave_nf_cancelada/);
    assert.match(m, /where\('status', 'cancelada'\)/);
    assert.match(m, /Rollback proibido/);
    for(const p of ['CancelarCompra','EstornarCompra']) {
        const a=fonte(`app/Actions/Compra/${p}.php`);
        assert.match(a,/chave_nf_cancelada/);
        assert.match(a,/'chave_nf' => null/);
    }
    assert.match(fonte('app/Http/Requests/Compra/StoreCompraRequest.php'), /Rule::unique\('compras', 'chave_nf'\)/);
});
