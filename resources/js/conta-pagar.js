document.addEventListener('DOMContentLoaded', function () {
    const parcelaSelect = document.getElementById('parcela_conta_pagar_id');
    const valorInput = document.getElementById('valor_pagamento');
    const saldoParcela = document.getElementById('saldo-parcela');

    if (parcelaSelect && valorInput && saldoParcela) {
        const atualizarSaldoParcela = function () {
            const option = parcelaSelect.options[parcelaSelect.selectedIndex];
            const saldo = option && option.dataset ? option.dataset.saldo : null;

            if (!saldo) {
                valorInput.removeAttribute('max');
                saldoParcela.textContent =
                    'Selecione uma parcela para visualizar o saldo disponível.';
                return;
            }

            const saldoNumerico = parseFloat(saldo);
            valorInput.max = saldo;
            saldoParcela.textContent =
                'Saldo da parcela: R$ ' +
                saldoNumerico.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

            const valorAtual = parseFloat(valorInput.value || 0);

            if (valorAtual > saldoNumerico) {
                valorInput.value = saldo;
            }
        };

        parcelaSelect.addEventListener('change', atualizarSaldoParcela);
        valorInput.addEventListener('input', atualizarSaldoParcela);
        atualizarSaldoParcela();
    }

    const parcelasContainer = document.getElementById('parcelas-container');
    const formParcelas = document.getElementById('form-parcelas');
    const btnAdicionarParcela = document.getElementById('btn-adicionar-parcela');
    const parcelasTotal = document.getElementById('parcelas-total');

    if (!parcelasContainer || !formParcelas) {
        return;
    }

    const formatarMoeda = function (valor) {
        return valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const obterLinhasParcelas = function () {
        return Array.prototype.slice.call(
            parcelasContainer.querySelectorAll('.parcela-row')
        );
    };

    const obterValorParcela = function (linha) {
        const input = linha.querySelector('.parcela-valor');
        const valor = parseFloat(input ? input.value || 0 : 0);

        return isFinite(valor) ? valor : 0;
    };

    const obterValorPagoParcela = function (linha) {
        const celulas = Array.prototype.slice.call(linha.children);
        const celulaPago = celulas[3];

        if (!celulaPago) {
            return 0;
        }

        const valorPagoTexto = celulaPago.textContent || '';
        const valorPagoMatch = valorPagoTexto.match(/R\$\s*([\d.,]+)/);

        if (!valorPagoMatch) {
            return 0;
        }

        const valorPago = parseFloat(
            valorPagoMatch[1]
                .replace(/\./g, '')
                .replace(',', '.')
        );

        return isFinite(valorPago) ? valorPago : 0;
    };

    const atualizarTotalParcelas = function () {
        const total = obterLinhasParcelas().reduce(function (soma, linha) {
            return soma + obterValorParcela(linha);
        }, 0);

        if (parcelasTotal) {
            parcelasTotal.textContent = 'R$ ' + formatarMoeda(total);
        }

        return Math.round(total * 100) / 100;
    };

    const renumerarParcelas = function () {
        obterLinhasParcelas().forEach(function (linha, index) {
            const numero = index + 1;
            const numeroElemento = linha.querySelector('.parcela-numero');

            if (numeroElemento) {
                numeroElemento.textContent = numero;
            }

            linha.querySelectorAll('[name]').forEach(function (elemento) {
                elemento.name = elemento.name.replace(
                    /parcelas\[\d+\]/,
                    'parcelas[' + index + ']'
                );
            });
        });
    };

    const atualizarSaldosVisuais = function () {
        obterLinhasParcelas().forEach(function (linha) {
            const valor = obterValorParcela(linha);
            const valorPago = obterValorPagoParcela(linha);
            const saldoElemento = linha.querySelector('.parcela-saldo');

            if (!saldoElemento) {
                return;
            }

            const saldo = Math.max(0, valor - valorPago);
            saldoElemento.textContent = 'R$ ' + formatarMoeda(saldo);
        });
    };

    const obterProximaDataVencimento = function () {
        const linhas = obterLinhasParcelas();

        if (linhas.length === 0) {
            const hoje = new Date();

            return hoje.getFullYear() +
                '-' +
                String(hoje.getMonth() + 1).padStart(2, '0') +
                '-' +
                String(hoje.getDate()).padStart(2, '0');
        }

        const ultimaLinha = linhas[linhas.length - 1];
        const ultimoVencimento =
            ultimaLinha.querySelector('.parcela-vencimento');

        if (!ultimoVencimento || !ultimoVencimento.value) {
            const hoje = new Date();

            return hoje.getFullYear() +
                '-' +
                String(hoje.getMonth() + 1).padStart(2, '0') +
                '-' +
                String(hoje.getDate()).padStart(2, '0');
        }

        const partes = ultimoVencimento.value.split('-');
        const data = new Date(
            parseInt(partes[0], 10),
            parseInt(partes[1], 10) - 1,
            parseInt(partes[2], 10)
        );

        data.setMonth(data.getMonth() + 1);

        return data.getFullYear() +
            '-' +
            String(data.getMonth() + 1).padStart(2, '0') +
            '-' +
            String(data.getDate()).padStart(2, '0');
    };

    const criarParcela = function () {
        const linhas = obterLinhasParcelas();
        const index = linhas.length;
        const dataFormatada = obterProximaDataVencimento();
        const linha = document.createElement('tr');

        linha.className = 'parcela-row';

        linha.innerHTML =
            '<td>' +
                '<input type="hidden" name="parcelas[' + index + '][id]" value="" class="parcela-id">' +
                '<span class="badge bg-secondary parcela-numero">' + (index + 1) + '</span>' +
            '</td>' +
            '<td>' +
                '<input type="date" name="parcelas[' + index + '][data_vencimento]" value="' + dataFormatada + '" class="form-control parcela-vencimento">' +
            '</td>' +
            '<td>' +
                '<div class="input-group">' +
                    '<span class="input-group-text">R$</span>' +
                    '<input type="number" name="parcelas[' + index + '][valor]" value="0.00" min="0.01" step="0.01" class="form-control parcela-valor">' +
                '</div>' +
            '</td>' +
            '<td>R$ 0,00</td>' +
            '<td><span class="parcela-saldo">R$ 0,00</span></td>' +
            '<td>' +
                '<span class="badge bg-secondary">' +
                    '<i class="bi bi-clock"></i> Aberta' +
                '</span>' +
            '</td>' +
            '<td class="text-end">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-remover-parcela" title="Remover parcela">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</td>';

        parcelasContainer.appendChild(linha);

        renumerarParcelas();
        atualizarTotalParcelas();
        atualizarSaldosVisuais();

        const valor = linha.querySelector('.parcela-valor');

        if (valor) {
            valor.focus();
            valor.select();
        }
    };

    const removerParcela = function (botao) {
        const linha = botao.closest('.parcela-row');

        if (!linha) {
            return;
        }

        const linhas = obterLinhasParcelas();

        if (linhas.length <= 1) {
            return;
        }

        const valorPago = obterValorPagoParcela(linha);

        if (valorPago > 0) {
            return;
        }

        linha.remove();

        renumerarParcelas();
        atualizarTotalParcelas();
        atualizarSaldosVisuais();
    };

    if (btnAdicionarParcela) {
        btnAdicionarParcela.addEventListener('click', criarParcela);
    }

    parcelasContainer.addEventListener('click', function (event) {
        const botao = event.target.closest('.btn-remover-parcela');

        if (!botao) {
            return;
        }

        removerParcela(botao);
    });

    parcelasContainer.addEventListener('input', function (event) {
        if (!event.target.classList.contains('parcela-valor')) {
            return;
        }

        atualizarTotalParcelas();
        atualizarSaldosVisuais();
    });

    parcelasContainer.addEventListener('change', function (event) {
        if (
            event.target.classList.contains('parcela-valor') ||
            event.target.classList.contains('parcela-vencimento')
        ) {
            atualizarTotalParcelas();
            atualizarSaldosVisuais();
        }
    });

    renumerarParcelas();
    atualizarTotalParcelas();
    atualizarSaldosVisuais();
});
