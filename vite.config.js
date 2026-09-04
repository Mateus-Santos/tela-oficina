import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/scss/_app.scss',
                'resources/js/app.js',
                'resources/js/cadColaborador.js',
                'resources/js/cadError.js',
                'resources/js/cadUser.js',
                'resources/js/calcOrcamento.js',
                'resources/js/cep.js',
                'resources/js/main.js',
                'resources/js/bootstrap.js',
                'resources/js/cadOrdemservico.js',
                'resources/js/cadOrdemservicoitem.js',
                'resources/js/cadOsItem.js',
                'resources/js/cadProduto.js',
                'resources/js/cadVeiculo.js',
                'resources/js/calcOrcamento.js',
                'resources/js/validateForm.js',
                'resources/js/gerenciadorItensOs.js',
                'resources/scss/_app.scss',
                'resources/js/compra.js',
                'resources/js/formsWizard.js',
            ],
            refresh: true,
        }),
    ],
});
