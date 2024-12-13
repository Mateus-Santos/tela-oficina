// Aguarda o carregamento completo do DOM antes de aplicar as máscaras
document.addEventListener("DOMContentLoaded", function () {

  // Seleciona todos os campos que precisam ser habilitados
  const inputs = document.querySelectorAll("input");

  // Habilita os campos
  inputs.forEach(input => {
    input.disabled = false;
    console.log("Campos liberados!");
  });

  // Máscaras
  Inputmask("(99) 9 9999 9999").mask(document.querySelectorAll("#telefone_1, #telefone_2"));
  Inputmask("999.999.999-99").mask(document.querySelectorAll("#cpf"));
  Inputmask("99999-999").mask(document.querySelectorAll("#cep"));

  // Adiciona o ouvinte de evento apenas se o elemento existir
  const telefone1 = document.getElementById("telefone_1");
  const telefone2 = document.getElementById("telefone_2");
  const cpf = document.getElementById("cpf");
  const rg = document.getElementById("rg");
  const dataNascimento = document.getElementById("data_nascimento");
  const valor = document.getElementById("valor");

  if (telefone1) {
    telefone1.addEventListener("blur", function () {
      validatePhone(this);
    });
  }

  if (telefone2) {
    telefone2.addEventListener("blur", function () {
      validatePhone(this);
    });
  }

  if (cpf) {
    cpf.addEventListener("blur", function () {
      validateCpf(this);
    });
  }

  if (rg) {
    rg.addEventListener("blur", function () {
      validateRg(this);
    });
  }

  if (dataNascimento) {
    dataNascimento.addEventListener("blur", function () {
      validateDateOfBirth(this);
    });
  }

  if (valor) {
    valor.addEventListener("blur", function () {
      maskValue(this);
    });
  }

  // Validação para telefone
  function validatePhone(input) {
    const phone = input.value.replace(/\D/g, ''); // Remove qualquer caractere não numérico

    if (phone.length === 0) {
      return;
    }

    if (phone.length !== 11) {
      alert("O número de telefone deve ter exatamente 11 dígitos.");
      input.focus();
    }
  }

  // Validação para CPF:
  function validateCpf(input) {
    const cpf = input.value.replace(/\D/g, ''); // Remove qualquer caractere não numérico

    if (cpf.length === 0) {
      return;
    }

    // Verifica se o CPF tem 11 dígitos
    if (cpf.length !== 11) {
      alert("O CPF deve ter 11 dígitos.");
      input.focus();
      return;
    }

    // Função para verificar CPF (com cálculo dos dois dígitos verificadores)
    if (!isValidCpf(cpf)) {
      alert("CPF inválido.");
      input.focus();
      return;
    }
  }

  function validateRg(input) {
    const rg = input.value.replace(/[^a-zA-Z0-9-]/g, ''); // Remove tudo, exceto letras, números e o caractere "-"
    input.value = rg;
  }

  // Função que realiza a validação dos 11 dígitos e os cálculos dos dois dígitos verificadores
  function isValidCpf(cpf) {
    let sum = 0;
    let remainder;

    // Cálculo do primeiro dígito verificador
    for (let i = 1; i <= 9; i++) {
      sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) {
      remainder = 0;
    }

    if (remainder !== parseInt(cpf.substring(9, 10))) {
      return false;
    }

    // Cálculo do segundo dígito verificador
    sum = 0;
    for (let i = 1; i <= 10; i++) {
      sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) {
      remainder = 0;
    }

    if (remainder !== parseInt(cpf.substring(10, 11))) {
      return false;
    }

    return true;
  }

  // Validação para data de nascimento
  function validateDateOfBirth(input) {
    const inputDate = new Date(input.value);
    const currentDate = new Date();

    // Verificar se a data não é futura
    if (inputDate > currentDate) {
      alert("A data de nascimento não pode ser no futuro.");
      input.focus();
      return;
    }

    // Verificar se a data não é hoje
    if (inputDate.toDateString() === currentDate.toDateString()) {
      alert("A data de nascimento não pode ser hoje.");
      input.focus();
      return;
    }
  }

  function maskValue(input) {
    if (input) {
      input.addEventListener("input", function (e) {
        let value = e.target.value;

        // Remove qualquer caractere não numérico (exceto vírgula)
        value = value.replace(/\D(?!,)/g, '');

        // Coloca a vírgula nos dois últimos dígitos
        value = value.replace(/(\d+)(\d{2})$/, '$1,$2');

        // Coloca o ponto a cada 3 dígitos antes da vírgula
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        e.target.value = value;
      });
    }
  }
});