// resources/js/cadProduto.js

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-produto');
  const errorContainer = document.createElement('div');
  errorContainer.className = 'alert alert-danger mensseger_error_container';
  errorContainer.style.display = 'none';
  form.prepend(errorContainer);

  // campos de número que só aceitam dígitos (e ponto para preço)
  const onlyDigits = el => {
    el.addEventListener('input', () => {
      let v = el.value;
      // para quantidade e ano, só números
      if (el.matches('#quantidade, #ano_modelo')) {
        el.value = v.replace(/\D+/g, '');
      }
      // para preço, números e no máximo um ponto
      if (el.matches('#preco_uni')) {
        // remove tudo que não for dígito ou ponto
        v = v.replace(/[^\d.]/g, '');
        // deixa só o primeiro ponto
        const parts = v.split('.');
        el.value = parts.shift() + (parts.length ? '.' + parts.join('') : '');
      }
    });
  };

  document.querySelectorAll('#quantidade, #ano_modelo, #preco_uni').forEach(onlyDigits);

  // inicializa todas as tags-input como antes, mas registra quais são obrigatórias
  const tagsWidgets = [];
  document.querySelectorAll('.tags-input').forEach(container => {
    const input = container.querySelector('input[type="text"]');
    const tagsContainer = container.querySelector('.tags-container');
    const fieldName = container.dataset.name; // ex: 'veiculos[]'
    const isRequired = ['veiculos[]','marcas[]','departamentos[]','montadora[]']
      .includes(fieldName);

    container.tags = [];
    tagsWidgets.push({ container, isRequired, fieldName });

    container.updateTags = () => {
      tagsContainer.innerHTML = '';
      container.querySelectorAll('input[type=hidden]').forEach(e => e.remove());
      container.tags.forEach((tag, i) => {
        const el = document.createElement('div');
        el.className = 'tag';
        el.innerHTML = `${tag} <span class="remove-tag" data-i="${i}">&times;</span>`;
        tagsContainer.append(el);
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = fieldName;
        hidden.value = tag;
        container.appendChild(hidden);
      });
    };

    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = input.value.trim();
        if (val && !container.tags.includes(val)) {
          container.tags.push(val);
          container.updateTags();
        }
        input.value = '';
      }
    });

    input.addEventListener('blur', () => {
      const val = input.value.trim();
      if (val && !container.tags.includes(val)) {
        container.tags.push(val);
        container.updateTags();
      }
      input.value = '';
    });

    tagsContainer.addEventListener('click', e => {
      if (e.target.matches('.remove-tag')) {
        container.tags.splice(e.target.dataset.i, 1);
        container.updateTags();
      }
    });
  });

  // debounce helper
  const debounce = (fn, ms = 300) => {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn(...args), ms);
    };
  };

  // busca de barcode ao digitar (debounced) e ao sair do campo
  const barcodeInput = document.getElementById('codigo_barras');
  const preencheBarcode = async () => {
    const code = barcodeInput.value.trim();
    if (!code) return;
    try {
      const res = await fetch(`/produtos/barcode/${code}`);
      if (!res.ok) throw new Error('Não encontrado');
      const data = await res.json();
      // campos simples
      ['nome','ano_modelo','motor','descricao','preco_uni'].forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = data[f] ?? '';
      });
      // preenche tags
      tagsWidgets.forEach(w => {
        const key = w.fieldName.replace('[]','');
        if (Array.isArray(data[key])) {
          w.container.tags = data[key];
          w.container.updateTags();
        }
      });
    } catch (err) {
      console.warn(err);
    }
  };
  barcodeInput.addEventListener('blur', debounce(preencheBarcode));
  barcodeInput.addEventListener('input', debounce(preencheBarcode, 800));

  // validação antes de submit
  form.addEventListener('submit', e => {
    const errors = [];

    // verifica campos obrigatórios nativos
    form.querySelectorAll('[required]').forEach(el => {
      if (!el.value.trim()) {
        const label = document.querySelector(`label[for="${el.id}"]`)?.innerText || el.name;
        errors.push(`O campo "${label}" é obrigatório.`);
      }
    });

    // verifica tags obrigatórias
    tagsWidgets.forEach(w => {
      if (w.isRequired && w.container.tags.length === 0) {
        errors.push(`Preencha ao menos uma opção para "${w.fieldName.replace('[]','')}".`);
      }
    });

    // verifica ano_modelo entre 1900 e ano atual+1
    const ano = parseInt(document.getElementById('ano_modelo').value, 10);
    const anoAtual = new Date().getFullYear();
    if (ano && (ano < 1900 || ano > anoAtual + 1)) {
      errors.push(`O "Ano Modelo" deve estar entre 1900 e ${anoAtual + 1}.`);
    }

    // se houver erros, bloqueia submit e mostra
    if (errors.length) {
      e.preventDefault();
      errorContainer.innerHTML = '<ul>' + errors.map(msg => `<li>${msg}</li>`).join('') + '</ul>';
      errorContainer.style.display = 'block';
      window.scrollTo({ top: errorContainer.offsetTop - 20, behavior: 'smooth' });
    }
  });
});
