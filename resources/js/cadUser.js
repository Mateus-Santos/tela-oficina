const campoCPF =  document.querySelector("#cpf");
const campoRG =  document.querySelector("#rg");
const telefone1 =  document.querySelector("#telefone_1");
const telefone2 =  document.querySelector("#telefone_2");
const password =  document.querySelector("#password");

// Validate CPF
campoCPF.addEventListener("keypress", (e) => {
    let tamanhoCampo = campoCPF.value.length;
    const onlyNumbers = /[0-9]|\./;
    const key = String.fromCharCode(e.keyCode);
    // allow only numbers
    if (!onlyNumbers.test(key)) {
      e.preventDefault();
      return;
    }
    //Formatação visual do CPF
    if(tamanhoCampo == 3 || tamanhoCampo == 7){
        campoCPF.value += ".";
    }
    if(tamanhoCampo == 11){
        campoCPF.value += "-";
    }
});

// Validate RG
campoRG.addEventListener("keypress", (e) => {
    const onlyNumbers = /[0-9]|\./;
    const key = String.fromCharCode(e.keyCode);
    let tamanhoCampo = campoRG.value.length;
    // allow only numbers
    if (!onlyNumbers.test(key)) {
      e.preventDefault();
      return;
    }
    //Formatação visual do CPF
    if(tamanhoCampo == 2 || tamanhoCampo == 6){
        campoRG.value += ".";
    }
    if(tamanhoCampo == 10){
        campoRG.value += "-";
    }
});

// Validate Tell 1
telefone1.addEventListener("input", function(e) {
    let value = telefone1.value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

    if (value.length > 0) {
        value = "(" + value;
    }
    if (value.length > 3) {
        value = value.slice(0, 3) + ") " + value.slice(3);
    }

    if (value.length > 9) {
        if (value[5] == '9') {
            value = value.slice(0, 10) + "-" + value.slice(10, 15);
        } else {
            value = value.slice(0, 9) + "-" + value.slice(9, 13);
        }
    }

    telefone1.value = value;
});

// Validate Tell 2
telefone2.addEventListener("input", function(e) {
    let value = telefone2.value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos

    if (value.length > 0) {
        value = "(" + value;
    }
    if (value.length > 3) {
        value = value.slice(0, 3) + ") " + value.slice(3);
    }

    if (value.length > 9) {
        if (value[5] == '9') {
            value = value.slice(0, 10) + "-" + value.slice(10, 15);
        } else {
            value = value.slice(0, 9) + "-" + value.slice(9, 13);
        }
    }
});

password.addEventListener("input", function(e) {
    const senha = senhaInput.value;
    if (senha.length < 8) {
      alert('A senha deve ter pelo menos 8 caracteres.');
      return false; // Impede o envio do formulário
    }
    return true; // Permite o envio do formulário
  });