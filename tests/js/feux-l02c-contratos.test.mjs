import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
const source=p=>readFileSync(new URL('../../'+p,import.meta.url),'utf8');

test('criacao publica sempre cria Nota com OS; contexto interno nao permite autovinculo solto',()=>{
 const a=source('app/Actions/OrdemServico/CriarOrdemServico.php');
 assert.match(a,/CriarOsComNota::class/);
 assert.match(a,/function criarParaNota\(/);
 assert.match(a,/transactionLevel\(\)/);
 assert.match(a,/nota_id/);
});
test('servico avulso cria Nota e usa AtualizarNota no mesmo transaction',()=>{
 const a=source('app/Services/Nota/CriarOsComNota.php');
 assert.match(a,/DB::transaction/);
 assert.match(a,/criarParaNota/);
 assert.match(a,/atualizarNota->execute/);
});
test('rascunho sempre usa FK da Nota, sem criar nota secundaria',()=>{
 const a=source('app/Services/Nota/OsRascunhoNaNota.php');
 assert.match(a,/criarParaNota\(\$nota/);
});
test('restricoes de banco e unicidade de OS sem prejudicar produtos',()=>{
 const a=source('database/migrations/2026_11_25_160000_feux_l02c_os_nota_obrigatoria.php');
 assert.match(a,/NOT NULL/);assert.match(a,/restrictOnDelete/);assert.match(a,/feux_os_item_unico/);assert.match(a,/GENERATED ALWAYS AS/);
});
test('modelos declaram vinculo canonico',()=>{
 assert.match(source('app/Models/OrdemServico.php'),/function nota\(/);
 assert.match(source('app/Models/NotasItem.php'),/nota_id.*itemable/si);
});
