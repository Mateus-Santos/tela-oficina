document.addEventListener('DOMContentLoaded', () => {
    const campoCPF = document.querySelector("#cpf");
    const campoRG = document.querySelector("#rg");
    const telefone1 = document.querySelector("#telefone_1");
    const telefone2 = document.querySelector("#telefone_2");

    // Máscara e Limite de CPF (000.000.000-00)
    if (campoCPF) {
        campoCPF.addEventListener("input", (e) => {
            let value = e.target.value.replace(/\D/g, ""); // Apenas números
            if (value.length > 11) value = value.slice(0, 11);

            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");

            e.target.value = value;
        });
    }

    // Máscara e Limite de RG (00.000.000-0)
    if (campoRG) {
        campoRG.addEventListener("input", (e) => {
            let value = e.target.value.replace(/\D/g, ""); // Apenas números
            if (value.length > 9) value = value.slice(0, 9);

            value = value.replace(/(\d{2})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d{1})$/, "$1-$2");

            e.target.value = value;
        });
    }

    // Função de máscara dinâmica para telefones (8 ou 9 dígitos + DDD)
    function aplicarMascaraTelefone(inputElement) {
        if (!inputElement) return;

        inputElement.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, ""); // Apenas números
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 0) {
                value = "(" + value;
            }
            if (value.length > 3) {
                value = value.slice(0, 3) + ") " + value.slice(3);
            }
            if (value.length > 9) {
                if (value.length === 14) { // Fixos: (00) 0000-0000
                    value = value.slice(0, 9) + "-" + value.slice(9);
                } else { // Celulares: (00) 00000-0000
                    value = value.slice(0, 10) + "-" + value.slice(10);
                }
            }

            e.target.value = value;
        });
    }

    aplicarMascaraTelefone(telefone1);
    aplicarMascaraTelefone(telefone2);
});