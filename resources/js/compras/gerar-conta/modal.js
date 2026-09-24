import inicializarParcelas from '../aprovacao/parcelas';

export default function inicializarGeracaoContaCompra() {
    const modal = document.getElementById('modalGerarContaCompra');

    if (!modal) {
        return;
    }

    const formulario = modal.querySelector('#formGerarContaCompra');
    const camposParcelas = modal.querySelector('#gerar-conta-parcelas-hidden');
    const quantidadeInput = modal.querySelector('#gerar_conta_parcelas_quantidade');
    const primeiraDataInput = modal.querySelector('#gerar_conta_primeira_data_vencimento');
    const intervaloInput = modal.querySelector('#gerar_conta_intervalo_parcelas');
    const previewContainer = modal.querySelector('#gerar-conta-preview-parcelas');
    const totalElement = modal.querySelector('#gerar-conta-total');
    const botaoGerar = modal.querySelector('#botaoGerarContaCompra');
    const valorTotal = Number(modal.dataset.valorTotal);

    if (
        !formulario ||
        !camposParcelas ||
        !quantidadeInput ||
        !primeiraDataInput ||
        !intervaloInput ||
        !previewContainer ||
        !totalElement ||
        !botaoGerar ||
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

    formulario.addEventListener('submit', (evento) => {
        const parcelas = configuracaoParcelas.obterParcelas();

        if (!parcelas.length) {
            evento.preventDefault();
            return;
        }

        adicionarCamposParcelas(parcelas);
        botaoGerar.disabled = true;
    });

    modal.addEventListener('hidden.bs.modal', () => {
        camposParcelas.innerHTML = '';
        botaoGerar.disabled = false;
    });

    configuracaoParcelas.atualizar();
}
