const addressForm = document.querySelector("#address-form"); // Formulário de endereço
const cepInput = document.querySelector("#cep"); // CEP
const addressInput = document.querySelector("#address"); // Rua
const cityInput = document.querySelector("#city"); // Cidade
const neighborhoodInput = document.querySelector("#neighborhood"); // Bairro
const regionInput = document.querySelector("#region"); // Estado
const formInputs = document.querySelectorAll("[data-input]");

const closeButton = document.querySelector("#close-message");

// Validate CEP Input
cepInput.addEventListener("keypress", (e) => {
    const onlyNumbers = /[0-9]|\./;
    const key = String.fromCharCode(e.keyCode);
  
    console.log(key);  
    console.log(onlyNumbers.test(key));

    // allow only numbers
    if (!onlyNumbers.test(key)) {
      e.preventDefault();
      return;
    }
  });

// Evento to get address
cepInput.addEventListener("keyup", (e) => {
    const inputValue = e.target.value;

    //   Check if we have a CEP
    if (inputValue.length === 8) {
      getAddress(inputValue);
    }
});

const getAddress = async (cep) => {
    cepInput.blur();
    const apiUrl = `https://viacep.com.br/ws/${cep}/json/`;
    const response = await fetch(apiUrl);
    const data = await response.json();
    console.log(data);
    console.log(formInputs);
    console.log(data.erro);
    // Show error and reset form
    if (data.erro === "true") {
      if (!addressInput.hasAttribute("disabled")) {
        toggleDisabled();
      }
      addressForm.reset();
      toggleMessage("CEP Inválido, tente novamente.");
      return;
    }
    // Activate disabled attribute if form is empty
    if (addressInput.value === "") {
      toggleDisabled();
    }
    addressInput.value = data.logradouro;
    cityInput.value = data.localidade;
    neighborhoodInput.value = data.bairro;
    regionInput.value = data.uf;
  };

// Add or remove disable attribute
const toggleDisabled = () => {
    if (regionInput.hasAttribute("disabled")) {
      formInputs.forEach((input) => {
        input.removeAttribute("disabled");
      });
    } else {
      formInputs.forEach((input) => {
        input.setAttribute("disabled", "disabled");
      });
    }
  };