import inicializarParcelas from './parcelas';

export default function inicializarAprovacaoCompra() {
    const modal = document.getElementById('modalAprovarCompra');

    if (!modal) {
        return;
    }

    const formulario = modal.querySelector('#formAprovarCompra');

    const etapaInicial = modal.querySelector(
        '#aprovacao-compra-etapa-inicial'
    );

    const etapaParcelas = modal.querySelector(
        '#aprovacao-compra-etapa-parcelas'
    );

    const acoesIniciais = modal.querySelector(
        '#aprovacao-compra-acoes-iniciais'
    );

    const acoesParcelas = modal.querySelector(
        '#aprovacao-compra-acoes-parcelas'
    );

    const botaoGerar = modal.querySelector(
        '[data-aprovacao="gerar"]'
    );

    const botaoVoltar = modal.querySelector(
        '[data-aprovacao="voltar"]'
    );

    const botaoConfirmarGeracao = modal.querySelector(
        '[data-aprovacao="confirmar-geracao"]'
    );

    const campoGerarConta = modal.querySelector(
        '#aprovacao-compra-gerar-conta'
    );

    const camposParcelas = modal.querySelector(
        '#aprovacao-compra-parcelas-hidden'
    );

    const quantidadeInput = modal.querySelector(
        '#parcelas_quantidade'
    );

    const primeiraDataInput = modal.querySelector(
        '#primeira_data_vencimento'
    );

    const intervaloInput = modal.querySelector(
        '#intervalo_parcelas'
    );

    const previewContainer = modal.querySelector(
        '#aprovacao-compra-preview-parcelas'
    );

    const totalElement = modal.querySelector(
        '#aprovacao-compra-total'
    );

    const valorTotal = Number(modal.dataset.valorTotal);

    if (
        !formulario ||
        !etapaInicial ||
        !etapaParcelas ||
        !acoesIniciais ||
        !acoesParcelas ||
        !botaoGerar ||
        !botaoVoltar ||
        !botaoConfirmarGeracao ||
        !campoGerarConta ||
        !camposParcelas ||
        !quantidadeInput ||
        !primeiraDataInput ||
        !intervaloInput ||
        !previewContainer ||
        !totalElement ||
        !Number.isFinite(valorTotal)
    ) {
        return;
    }

    const configuracaoParcelas = inicializarParcelas({
        valorTotal,
        quantidadeInput,
        primeiraDataInput,
        intervaloInput,
        previewContainer,
        totalElement,
    });

    if (!configuracaoParcelas) {
        return;
    }

    const mostrarEtapaInicial = () => {
        etapaInicial.classList.remove('d-none');
        etapaParcelas.classList.add('d-none');

        acoesIniciais.classList.remove('d-none');
        acoesParcelas.classList.add('d-none');

        campoGerarConta.value = '0';
        camposParcelas.innerHTML = '';
    };

    const mostrarEtapaParcelas = () => {
        etapaInicial.classList.add('d-none');
        etapaParcelas.classList.remove('d-none');

        acoesIniciais.classList.add('d-none');
        acoesParcelas.classList.remove('d-none');

        configuracaoParcelas.atualizar();
    };

    const adicionarCamposParcelas = (parcelas) => {
        camposParcelas.innerHTML = '';

        parcelas.forEach((parcela, indice) => {
            const numero = document.createElement('input');
            numero.type = 'hidden';
            numero.name = `parcelas[${indice}][numero]`;
            numero.value = parcela.numero;

            const valor = document.createElement('input');
            valor.type = 'hidden';
            valor.name = `parcelas[${indice}][valor]`;
            valor.value = parcela.valor.toFixed(2);

            const dataVencimento = document.createElement('input');
            dataVencimento.type = 'hidden';
            dataVencimento.name = `parcelas[${indice}][data_vencimento]`;
            dataVencimento.value = parcela.dataVencimentoInput;

            camposParcelas.appendChild(numero);
            camposParcelas.appendChild(valor);
            camposParcelas.appendChild(dataVencimento);
        });
    };

    botaoGerar.addEventListener('click', () => {
        mostrarEtapaParcelas();
    });

    botaoVoltar.addEventListener('click', () => {
        mostrarEtapaInicial();
    });

    botaoConfirmarGeracao.addEventListener('click', () => {
        const parcelas = configuracaoParcelas.obterParcelas();

        if (!parcelas.length) {
            return;
        }

        adicionarCamposParcelas(parcelas);

        campoGerarConta.value = '1';

        formulario.submit();
    });

    formulario.addEventListener('submit', () => {
        if (campoGerarConta.value === '0') {
            camposParcelas.innerHTML = '';
        }
    });

    modal.addEventListener('hidden.bs.modal', () => {
        mostrarEtapaInicial();
    });

    mostrarEtapaInicial();
}
