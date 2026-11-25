import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const get = path => readFileSync(path, 'utf8');

test('D04 documenta sem reescrever a NF historica da compra', () => {
    const action = get('app/Actions/Compra/RegistrarDevolucaoDocumentalCompra.php');
    assert.match(action, /CompraDevolucao::create/);
    assert.doesNotMatch(action, /\$compra->update\(\[/);
    assert.match(action, /assinatura_operacao/);
    assert.match(action, /operacao_uuid/);
    assert.match(action, /DB::transaction/);
    assert.match(action, /RegistrarSaida/);
    assert.match(action, /modalidade'\] === 'estoque'/);
    assert.match(action, /situacao_financeira/);
});

test('D04 nao registra creditos independentes nem pagamentos automaticos', () => {
    const action = get('app/Actions/Compra/RegistrarDevolucaoDocumentalCompra.php');
    assert.match(action, /pagamentos\(\)->exists\(\)/);
    assert.match(action, /where\('compra_id'/);
    assert.match(action, /where\('conta_pagar_id'/);
    assert.doesNotMatch(action, /PagamentoContaPagar::create/);
    assert.doesNotMatch(action, /CreditoFornecedor::create/);
});

test('D04 valor liquido e desconto proporcional cumulativo', () => {
    const saldo = get('app/Services/Compra/SaldoDevolucaoCompra.php');
    const valor = get('app/Services/Compra/ValorDevidoCompra.php');
    assert.match(saldo, /valorIncrementalCentavos/);
    assert.match(saldo, /\$anterior \+ \$novaQtd/);
    assert.match(saldo, /max\(0, \$nf - \$aceita - \$recusa\)/);
    assert.match(valor, /abatimentoAplicadoCentavos/);
    assert.match(valor, /divergenciaCoberta/);
});

test('D04 form e vinculacoes mostram o valor financeiro liquido', () => {
    assert.match(get('resources/views/contas_pagar/_form.blade.php'), /valorLiquidoDisponivel/);
    assert.match(get('app/Http/Controllers/ContaPagarController.php'), /withSum\(\[/);
    assert.match(get('app/Actions/ContasPagar/VincularComprasContaPagar.php'), /valorLiquidoCentavos/);
    assert.match(get('app/Actions/Compra/GerarContaPagarCompra.php'), /divergenciaCoberta/);
    assert.match(get('resources/views/compra/devolucoes/documental.blade.php'), /confirmacao_financeira/);
    assert.match(get('resources/views/compra/devolucoes/resumo.blade.php'), /devolucoes\.documental\.create/);
});

test('D04 limita o acesso a admin, sem emissao fiscal', () => {
    const ctl = get('app/Http/Controllers/CompraDevolucaoDocumentalController.php');
    const req = get('app/Http/Requests/Compra/RegistrarDevolucaoDocumentalRequest.php');
    const routes = get('routes/web.php');
    assert.match(ctl, /user\(\)\?->isAdmin/);
    assert.match(req, /user\(\)\?->isAdmin/);
    assert.match(routes, /compras\.devolucoes\.documental\.store/);
    assert.match(get('resources/views/compra/devolucoes/documental.blade.php'), /não é emissão de NF-e/);
});


test('D04 separa devolucao operacional legada da documental financeira', () => {
    const valor = get('app/Services/Compra/ValorDevidoCompra.php');
    const resumo = get('resources/views/compra/devolucoes/resumo.blade.php');
    const migration = get('database/migrations/2026_11_23_100000_devolucao_documentada_apos_aprovacao.php');
    assert.match(valor, /->where\('modalidade', 'documental'\)/);
    assert.doesNotMatch(valor, /->where\('modalidade', '!=', 'documental'\)/);
    assert.match(resumo, /sem abatimento financeiro/);
    assert.match(migration, /default\('operacional'\)/);
});
