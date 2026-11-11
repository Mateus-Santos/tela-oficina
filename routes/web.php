<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\Api\ClienteContaReceberBuscaController;
use App\Http\Controllers\Api\CatalogoBuscaController;
use App\Http\Controllers\Api\NotaContaReceberBuscaController;
use App\Http\Controllers\Api\NotaItemBuscaController;
use App\Http\Controllers\Api\NotaOrdemServicoRapidaController;
use App\Http\Controllers\Api\NotaProdutoRapidoController;
use App\Http\Controllers\Api\ProdutoBuscaController;
use App\Http\Controllers\Api\VeiculosClientesController as ApiVeiculosClientesController;
use App\Http\Controllers\CategoriaFinanceiraController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CatalogoProdutoComplementoController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ContaPagarController;
use App\Http\Controllers\ContaReceberController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\EtapaController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\FinanceiroSociosController;
use App\Http\Controllers\FinanceiroSociosReceberController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\Marketing\OrcamentoPecasController;
use App\Http\Controllers\Marketing\CadastroClienteMarketingController;
use App\Http\Controllers\Marketing\BuscarProdutosOrcamentoController;
use App\Http\Controllers\Marketing\MarketingLeadsController;
use App\Http\Controllers\Marketing\AtualizarStatusLeadController;
use App\Http\Controllers\Marketing\DetalharLeadOrcamentoController;
use App\Http\Controllers\Marketing\ConverterLeadEmNotaController;
use App\Http\Controllers\Marketing\VincularClienteLeadController;
use App\Http\Controllers\Marketing\GerenciarAcessoClienteController;
use App\Http\Middleware\CapturarAtribuicaoMarketing;
use App\Http\Controllers\MontadoraController;
use App\Http\Controllers\MovimentacaoEstoqueController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\NotasItemController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PortalCliente\ProdutosDisponiveisController;
use App\Http\Controllers\PortalCliente\NotasClienteController;
use App\Http\Controllers\PortalCliente\PainelClienteMarketingController;
use App\Http\Controllers\PortalCliente\SolicitacoesMarketingClienteController;
use App\Http\Controllers\PortalCliente\OrdensServicoClienteController;
use App\Http\Controllers\PortalCliente\EnderecosClienteController;
use App\Http\Controllers\RecebimentoController;
use App\Http\Controllers\SetorServicoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\VeiculosClientesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->middleware(CapturarAtribuicaoMarketing::class);

Route::get('/home', function () {
    return view('index');
})->middleware(CapturarAtribuicaoMarketing::class);

// Página comercial: reutiliza a home, o layout e os estilos existentes.
Route::get('/pecas/orcamento', function () {
    return view('index', ['paginaPecas' => true]);
})->middleware(CapturarAtribuicaoMarketing::class)
    ->name('marketing.pecas.orcamento');

// Consulta pública limitada aos produtos operacionais disponíveis.
Route::get('/pecas/orcamento/produtos', BuscarProdutosOrcamentoController::class)
    ->middleware('throttle:30,1')
    ->name('marketing.pecas.produtos');

Route::post('/pecas/orcamento', [OrcamentoPecasController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('marketing.pecas.store');

// Cadastro opt-in de cliente, protegido por verificacao de posse do e-mail.
// O recurso fica desabilitado ate a oficina validar o envio de e-mails.
Route::get('/minha-conta/cadastro', [CadastroClienteMarketingController::class, 'create'])
    ->middleware('guest')->name('marketing.cadastro.create');
Route::post('/minha-conta/cadastro', [CadastroClienteMarketingController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('marketing.cadastro.store');
Route::get('/minha-conta/cadastro/confirmar/{cadastro}/{token}', [CadastroClienteMarketingController::class, 'confirm'])
    ->whereNumber('cadastro')->middleware(['signed', 'throttle:10,1'])
    ->name('marketing.cadastro.confirmar');

// Os dados pessoais dos interessados ficam disponíveis só para administradores.
Route::middleware(['auth', 'check.blocked', 'admin'])->group(function () {
    Route::get('/marketing/clientes/acessos', [GerenciarAcessoClienteController::class, 'index'])
        ->name('marketing.clientes-acessos.index');
    Route::patch('/marketing/clientes/{cliente}/acessos', [GerenciarAcessoClienteController::class, 'update'])
        ->whereNumber('cliente')->middleware('throttle:10,1')
        ->name('marketing.clientes-acessos.update');
    Route::get('/marketing/leads', MarketingLeadsController::class)
        ->name('marketing.leads.index');
    Route::get('/marketing/leads/{lead}', DetalharLeadOrcamentoController::class)
        ->whereNumber('lead')->name('marketing.leads.show');
    Route::post('/marketing/leads/{lead}/vincular-cliente', [VincularClienteLeadController::class, 'vincular'])
        ->whereNumber('lead')->middleware('throttle:10,1')
        ->name('marketing.leads.vincular-cliente');
    Route::delete('/marketing/leads/{lead}/desvincular-cliente', [VincularClienteLeadController::class, 'desvincular'])
        ->whereNumber('lead')->middleware('throttle:10,1')
        ->name('marketing.leads.desvincular-cliente');
    Route::post('/marketing/leads/{lead}/converter-nota', ConverterLeadEmNotaController::class)
        ->whereNumber('lead')->middleware('throttle:10,1')
        ->name('marketing.leads.converter-nota');
    Route::patch('/marketing/leads/{lead}/status', AtualizarStatusLeadController::class)
        ->whereNumber('lead')
        ->name('marketing.leads.status.update');
});

Route::middleware([
    'auth',
    'check.blocked',
])->group(function () {
    // Catálogo público do cliente autenticado.
    // Autorização específica no FormRequest.
    Route::get(
        '/minha-area/produtos',
        ProdutosDisponiveisController::class
    )->name('cliente.produtos.index');

    // Consulta de notas: acesso apenas ao perfil cliente.
    Route::middleware('cliente')->group(function () {
        // Inicio progressivo opcional; permissoes dos modulos existentes preservadas.
        Route::get('/minha-area', PainelClienteMarketingController::class)
            ->name('cliente.area.index');

        // Solicitações vinculadas por cliente_id; nunca por telefone ou e-mail.
        Route::get(
            '/minha-area/solicitacoes',
            [SolicitacoesMarketingClienteController::class, 'index']
        )->name('cliente.solicitacoes.index');

        Route::get(
            '/minha-area/solicitacoes/{solicitacao}',
            [SolicitacoesMarketingClienteController::class, 'show']
        )->whereNumber('solicitacao')->name('cliente.solicitacoes.show');

        Route::get(
            '/minha-area/notas',
            [NotasClienteController::class, 'index']
        )->name('cliente.notas.index');

        Route::get(
            '/minha-area/notas/{nota}',
            [NotasClienteController::class, 'show']
        )->whereNumber('nota')->name('cliente.notas.show');

        Route::get(
            '/minha-area/ordens-servico',
            [OrdensServicoClienteController::class, 'index']
        )->name('cliente.os.index');

        Route::get(
            '/minha-area/ordens-servico/{ordemServico}',
            [OrdensServicoClienteController::class, 'show']
        )->whereNumber('ordemServico')->name('cliente.os.show');

        Route::get(
            '/minha-area/veiculos',
            [VeiculosClientesController::class, 'index']
        )->name('cliente.veiculos.index');

        Route::get(
            '/minha-area/veiculos/{veiculo}',
            [VeiculosClientesController::class, 'showCliente']
        )->whereNumber('veiculo')->name('cliente.veiculos.show');

        Route::get(
            '/minha-area/enderecos',
            [EnderecosClienteController::class, 'index']
        )->name('cliente.enderecos.index');

        Route::get(
            '/minha-area/enderecos/novo',
            [EnderecosClienteController::class, 'create']
        )->name('cliente.enderecos.create');

        Route::post(
            '/minha-area/enderecos',
            [EnderecosClienteController::class, 'store']
        )->name('cliente.enderecos.store');

        Route::get(
            '/minha-area/enderecos/{endereco}/editar',
            [EnderecosClienteController::class, 'edit']
        )->whereNumber('endereco')->name('cliente.enderecos.edit');

        Route::put(
            '/minha-area/enderecos/{endereco}',
            [EnderecosClienteController::class, 'update']
        )->whereNumber('endereco')->name('cliente.enderecos.update');
    });

    Route::get(
        '/perfil',
        [UserController::class, 'perfil']
    )->name('perfil');

    Route::put(
        '/perfil',
        [
            UserController::class,
            'atualizarPerfil',
        ]
    )->name('perfil.update');

    Route::resource(
        'veiculosclientes',
        VeiculosClientesController::class
    )->except(['show']);

    Route::get(
        '/veiculos/montadora/{id}',
        [VeiculoController::class, 'porMontadora']
    );

    /*
     * Compatibilidade com JavaScript antigo.
     *
     * Continua usando /api/... na URL, porém agora
     * passa pela sessão web e exige autenticação.
     */
    Route::get(
        '/api/montadora/{id}/veiculos',
        [VeiculoController::class, 'porMontadora']
    );

    Route::middleware([
        'admin',
    ])->group(function () {
        // Estoque

        Route::get(
            '/estoque',
            [EstoqueController::class, 'index']
        )->name('estoque.index');

        Route::get(
            '/estoque/movimentacoes',
            [MovimentacaoEstoqueController::class, 'index']
        )->name('estoque.movimentacoes.index');

        Route::get(
            '/estoque/{produto}/saida',
            [EstoqueController::class, 'saida']
        )->name('estoque.saida');

        Route::post(
            '/estoque/{produto}/saida',
            [EstoqueController::class, 'registrarSaida']
        )->name('estoque.registrarSaida');

        Route::get(
            '/estoque/{produto}/ajuste',
            [EstoqueController::class, 'ajuste']
        )->name('estoque.ajuste');

        Route::post(
            '/estoque/{produto}/ajuste',
            [EstoqueController::class, 'registrarAjuste']
        )->name('estoque.registrarAjuste');

        // Notas

        Route::get(
            '/notas/{id}/pdf',
            [NotaController::class, 'gerarpdf']
        )->name('notas.pdf');

        Route::get(
            '/notas/{id}/pdf/download',
            [NotaController::class, 'baixarPdf']
        )->name('notas.pdf.download');

        Route::post(
            '/notas/{nota}/pdf-interno/download',
            [NotaController::class, 'baixarPdfInterno']
        )->name('notas.pdf.interno.download');

        Route::post(
            '/notas/{nota}/finalizar',
            [NotaController::class, 'finalizar']
        )->name('notas.finalizar');

        Route::post(
            '/notas/{nota}/cancelar',
            [NotaController::class, 'cancelar']
        )->name('notas.cancelar');

        Route::resource(
            'notas',
            NotaController::class
        )->only([
            'index',
            'show',
            'destroy',
        ]);

        // APIs internas

        Route::get(
            '/api/veiculo/placa/{placa}',
            [
                ApiVeiculosClientesController::class,
                'buscarPorPlaca',
            ]
        )->name('api.veiculos.buscar-por-placa');

        Route::get(
            '/api/notas-itens/buscar',
            NotaItemBuscaController::class
        )->name('api.notas-itens.buscar');

        Route::get('/api/produtos/catalogo/buscar', CatalogoBuscaController::class)
            ->name('api.produtos.catalogo.buscar');

        Route::get(
            '/api/produtos/buscar',
            ProdutoBuscaController::class
        )->name('api.produtos.buscar');

        Route::get(
            '/api/contas-receber/clientes/buscar',
            ClienteContaReceberBuscaController::class
        )->name(
            'api.contas-receber.clientes.buscar'
        );

        Route::get(
            '/api/contas-receber/notas/buscar',
            NotaContaReceberBuscaController::class
        )->name(
            'api.contas-receber.notas.buscar'
        );

        Route::get(
            '/api/notas-itens/setores-servico',
            [
                NotaOrdemServicoRapidaController::class,
                'setores',
            ]
        )->name(
            'api.notas-itens.setores-servico'
        );

        Route::post(
            '/api/notas-itens/ordens-servico',
            [
                NotaOrdemServicoRapidaController::class,
                'store',
            ]
        )->name(
            'api.notas-itens.ordens-servico.store'
        );

        Route::get(
            '/api/notas-itens/marcas-produto',
            [
                NotaProdutoRapidoController::class,
                'marcas',
            ]
        )->name(
            'api.notas-itens.marcas-produto'
        );

        Route::post(
            '/api/notas-itens/produtos',
            [
                NotaProdutoRapidoController::class,
                'store',
            ]
        )->name(
            'api.notas-itens.produtos.store'
        );

        // Ordens de Serviço

        Route::post(
            '/ordemservicos/{ordemservico}/finalizar',
            [
                OrdemServicoController::class,
                'finalizar',
            ]
        )->name(
            'ordemservicos.finalizar'
        );

        Route::post(
            '/ordemservicos/{ordemservico}/cancelar',
            [
                OrdemServicoController::class,
                'cancelar',
            ]
        )->name(
            'ordemservicos.cancelar'
        );

        Route::resource(
            'ordemservicos',
            OrdemServicoController::class
        );

        // Itens das Notas

        Route::resource(
            'notasitem',
            NotasItemController::class
        )->except([
            'show',
        ]);

        // Usuários

        Route::resource(
            'users',
            UserController::class
        );

        // Clientes

        Route::resource(
            'clientes',
            ClienteController::class
        );

        // Produtos

        Route::delete(
            '/produtos/{produto}/imagens/{imagem}',
            [
                ProdutoController::class,
                'destroyImagem',
            ]
        )->name(
            'produtos.imagens.destroy'
        );

        // Deve vir antes de /produtos/{produto} para evitar colisao de rotas.
        Route::get(
            '/produtos/catalogo/{catalogo}',
            [ProdutoController::class, 'showCatalogo']
        )->name('produtos.catalogo.show');

        Route::get('/produtos/catalogo/{catalogo}/similares',
            [CatalogoProdutoComplementoController::class, 'similares'])
            ->name('produtos.catalogo.similares');
        Route::get('/produtos/catalogo/{catalogo}/editar',
            [CatalogoProdutoComplementoController::class, 'edit'])
            ->name('produtos.catalogo.edit');
        Route::put('/produtos/catalogo/{catalogo}',
            [CatalogoProdutoComplementoController::class, 'update'])
            ->name('produtos.catalogo.update');
        Route::post('/produtos/catalogo/{catalogo}/habilitar',
            [CatalogoProdutoComplementoController::class, 'habilitar'])
            ->name('produtos.catalogo.habilitar');

        Route::resource(
            'produtos',
            ProdutoController::class
        );

        // Marcas

        Route::resource(
            'marcas',
            MarcaController::class
        );

        // Colaboradores

        Route::resource(
            'colaboradores',
            ColaboradorController::class
        )->parameters([
            'colaboradores' => 'colaborador',
        ])->except([
            'show',
        ]);

        // Endereços

        Route::resource(
            'enderecos',
            EnderecoController::class
        );

        Route::get(
            '/endereco/create/{id}',
            [
                EnderecoController::class,
                'create',
            ]
        );

        // Veículos

        Route::resource(
            'veiculos',
            VeiculoController::class
        );

        // Montadoras

        Route::resource(
            'montadoras',
            MontadoraController::class
        );

        // Setores de Serviço

        Route::resource(
            'setor-servicos',
            SetorServicoController::class
        );

        // Formas de Pagamento

        Route::resource(
            'formas-pagamento',
            FormaPagamentoController::class
        )->parameters([
            'formas-pagamento' => 'formaPagamento',
        ]);

        // Categorias Financeiras

        Route::resource(
            'categorias-financeiras',
            CategoriaFinanceiraController::class
        )->parameters([
            'categorias-financeiras' => 'categoriaFinanceira',
        ]);

        // Contas a Receber

        Route::resource(
            'contas-receber',
            ContaReceberController::class
        )->parameters([
            'contas-receber' => 'contaReceber',
        ]);

        Route::get(
            'contas-receber/{contaReceber}/parcelas/{parcela}/recebimentos/create',
            [
                RecebimentoController::class,
                'create',
            ]
        )->name(
            'recebimentos.create'
        );

        Route::post(
            'recebimentos',
            [
                RecebimentoController::class,
                'store',
            ]
        )->name(
            'recebimentos.store'
        );

        Route::post(
            'contas-receber/{contaReceber}/recebimentos/{recebimento}/estornar',
            [
                RecebimentoController::class,
                'estornar',
            ]
        )->name(
            'recebimentos.estornar'
        );

        // Compras

        Route::post(
            'compras/{compra}/iniciar-conferencia',
            [
                CompraController::class,
                'iniciarConferencia',
            ]
        )->name(
            'compras.iniciar-conferencia'
        );

        Route::post(
            'compras/{compra}/conferir',
            [
                CompraController::class,
                'conferir',
            ]
        )->name(
            'compras.conferir'
        );

        Route::post(
            'compras/{compra}/aprovar',
            [
                CompraController::class,
                'aprovar',
            ]
        )->name(
            'compras.aprovar'
        );

        Route::post(
            'compras/{compra}/gerar-conta',
            [
                CompraController::class,
                'gerarConta',
            ]
        )->name(
            'compras.gerar-conta'
        );

        Route::post(
            'compras/{compra}/cancelar',
            [
                CompraController::class,
                'cancelar',
            ]
        )->name(
            'compras.cancelar'
        );

        Route::post(
            'compras/{compra}/estoque',
            [
                CompraController::class,
                'registrarEstoque',
            ]
        )->name(
            'compras.estoque'
        );

        Route::post(
            'compras/{compra}/estornar',
            [
                CompraController::class,
                'estornar',
            ]
        )->name(
            'compras.estornar'
        );

        Route::resource(
            'compras',
            CompraController::class
        );

        // Fornecedores

        Route::resource(
            'fornecedores',
            FornecedorController::class
        )->parameters([
            'fornecedores' => 'fornecedor',
        ]);

        // Anexos

        Route::post(
            'compras/{compra}/anexos',
            [
                AnexoController::class,
                'storeCompra',
            ]
        )->name(
            'compras.anexos.store'
        );

        Route::post(
            'contas-pagar/{conta}/anexos',
            [
                AnexoController::class,
                'storeContaPagar',
            ]
        )->name(
            'contas-pagar.anexos.store'
        );

        Route::post(
            'contas-receber/{contaReceber}/anexos',
            [
                AnexoController::class,
                'storeContaReceber',
            ]
        )->name(
            'contas-receber.anexos.store'
        );

        Route::patch(
            'anexos/{vinculo}',
            [
                AnexoController::class,
                'update',
            ]
        )->name(
            'anexos.update'
        );

        Route::get(
            'anexos/{anexo}',
            [
                AnexoController::class,
                'show',
            ]
        )->name(
            'anexos.show'
        );

        Route::get(
            'anexos/{anexo}/preview',
            [
                AnexoController::class,
                'preview',
            ]
        )->name(
            'anexos.preview'
        );

        Route::get(
            'anexos/{anexo}/download',
            [
                AnexoController::class,
                'download',
            ]
        )->name(
            'anexos.download'
        );

        Route::delete(
            'anexos/{vinculo}',
            [
                AnexoController::class,
                'destroy',
            ]
        )->name(
            'anexos.destroy'
        );

        // Financeiro pessoal dos socios (somente administradores)
        Route::get('financeiro/socios', [FinanceiroSociosController::class, 'index'])->name('socios-financeiro.index');
        Route::post('financeiro/socios', [FinanceiroSociosController::class, 'storeSocio'])->name('socios-financeiro.store');
        Route::get('financeiro/socios/{socio}', [FinanceiroSociosController::class, 'show'])->name('socios-financeiro.show');
        Route::post('financeiro/socios/{socio}/contas', [FinanceiroSociosController::class, 'storeConta'])->name('socios-financeiro.contas.store');
        Route::get('financeiro/socios/{socio}/contas/{conta}', [FinanceiroSociosController::class, 'showConta'])->name('socios-financeiro.contas.show');
        Route::post('financeiro/socios/{socio}/contas/{conta}/pagamentos', [FinanceiroSociosController::class, 'storePagamento'])->name('socios-financeiro.pagamentos.store');
        Route::post('financeiro/socios/{socio}/contas/{conta}/pagamentos/{pagamento}/estornar', [FinanceiroSociosController::class, 'estornarPagamento'])->name('socios-financeiro.pagamentos.estornar');
        Route::post('financeiro/socios/{socio}/retiradas', [FinanceiroSociosController::class, 'storeRetirada'])->name('socios-financeiro.retiradas.store');
        Route::post('financeiro/socios/{socio}/retiradas/{retirada}/estornar', [FinanceiroSociosController::class, 'estornarRetirada'])->name('socios-financeiro.retiradas.estornar');

        // Contas a receber pessoais dos sócios: rotas protegidas pelo grupo admin.
        Route::get('financeiro/socios/{socio}/receber', [FinanceiroSociosReceberController::class, 'index'])->name('socios-financeiro.receber.index');
        Route::post('financeiro/socios/{socio}/receber', [FinanceiroSociosReceberController::class, 'store'])->name('socios-financeiro.receber.store');
        Route::get('financeiro/socios/{socio}/receber/{recebivel}', [FinanceiroSociosReceberController::class, 'show'])->name('socios-financeiro.receber.show');
        Route::post('financeiro/socios/{socio}/receber/{recebivel}/recebimentos', [FinanceiroSociosReceberController::class, 'registrar'])->name('socios-financeiro.receber.recebimentos.store');
        Route::post('financeiro/socios/{socio}/receber/{recebivel}/recebimentos/{recebimento}/estornar', [FinanceiroSociosReceberController::class, 'estornar'])->name('socios-financeiro.receber.recebimentos.estornar');

        // Contas a Pagar

        Route::resource(
            'contas-pagar',
            ContaPagarController::class
        )->parameters([
            'contas-pagar' => 'conta',
        ])->except([
            'destroy',
        ]);

        Route::post(
            'contas-pagar/{conta}/parcelas',
            [
                ContaPagarController::class,
                'atualizarParcelas',
            ]
        )->name(
            'contas-pagar.parcelas.update'
        );

        Route::post(
            'contas-pagar/{conta}/pagamentos',
            [
                ContaPagarController::class,
                'registrarPagamento',
            ]
        )->name(
            'contas-pagar.pagamentos.store'
        );

        Route::post(
            'contas-pagar/{conta}/pagamentos/{pagamento}/estornar',
            [
                ContaPagarController::class,
                'estornarPagamento',
            ]
        )->name(
            'contas-pagar.pagamentos.estornar'
        );

        Route::post(
            'contas-pagar/{conta}/cancelar',
            [
                ContaPagarController::class,
                'cancelar',
            ]
        )->name(
            'contas-pagar.cancelar'
        );

        // Etapas

        Route::resource(
            'etapas',
            EtapaController::class
        )->only([
            'index',
            'store',
            'update',
        ]);
    });
});

Route::get(
    '/erro-autenticacao',
    function () {
        return view(
            'errors.403'
        );
    }
)->name(
    'erro-autenticacao'
);

Route::get(
    '/termos-de-uso',
    function () {
        return view(
            'termos/termosdeuso'
        );
    }
)->name(
    'termos'
);
