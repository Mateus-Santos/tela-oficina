document.addEventListener('DOMContentLoaded', () => {
  // ----------------------------
  // Tags-input (sem alterar)
  // ----------------------------
  document.querySelectorAll('.tags-input').forEach(container => {
    const input = container.querySelector('input[type="text"]');
    const tagsContainer = container.querySelector('.tags-container');
    const fieldName = container.dataset.name;
    container.tags = [];

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

  // ----------------------------
  // Busca por código de barras
  // ----------------------------
  document.getElementById('codigo_barras')?.addEventListener('blur', async function () {
    const code = this.value.trim();
    if (!code) return;
    try {
      const res = await fetch(`/produtos/barcode/${code}`);
      if (!res.ok) throw new Error;
      const data = await res.json();

      ['nome', 'ano_modelo', 'motor', 'descricao', 'preco_uni'].forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = data[f] || '';
      });

      ['montadora[]','veiculos[]','marcas[]','departamentos[]','valvula[]'].forEach(field => {
        const cont = document.querySelector(`.tags-input[data-name="${field}"]`);
        cont.tags = Array.isArray(data[field.replace('[]','')]) ? data[field.replace('[]','')] : [];
        cont.updateTags();
      });
    } catch {
      console.warn('Produto não encontrado pelo EAN.');
    }
  });

  // ----------------------------
  // Máscara de moeda no preco_uni
  // ----------------------------
  const precoEl = document.getElementById('preco_uni');
  if (precoEl) {
    precoEl.setAttribute('type', 'text');
    precoEl.setAttribute('inputmode', 'numeric');
    precoEl.setAttribute('placeholder', '0,00');

    precoEl.addEventListener('input', () => {
      let v = precoEl.value.replace(/\D/g, '');
      if (!v) {
        precoEl.value = '';
        return;
      }

      // Garante pelo menos 3 dígitos (centavos) e remove zeros à esquerda
      v = v.padStart(3, '0');

      const cents = v.slice(-2);
      let integerPart = v.slice(0, -2).replace(/^0+/, '') || '0';
      integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

      precoEl.value = `${integerPart},${cents}`;
    });
  }

  // ----------------------------
  // Validação e submit
  // ----------------------------
  const form = document.getElementById('form-produto');
  form?.addEventListener('submit', e => {
    const errors = [];

    // Campos obrigatórios
    form.querySelectorAll('[required]').forEach(el => {
      if (!el.value.trim()) {
        const label = document.querySelector(`label[for="${el.id}"]`)?.innerText || el.name;
        errors.push(`O campo "${label}" é obrigatório.`);
      }
    });

    // Converte preco_uni para ponto antes de enviar
    if (precoEl && precoEl.value) {
      const raw = precoEl.value.replace(/\./g, '').replace(',', '.');
      const num = parseFloat(raw);
      if (!isNaN(num)) {
        precoEl.value = num.toFixed(2);
      } else {
        errors.push('Preço inválido.');
      }
    }

    // Campos numéricos puros
    ['ano_modelo','quantidade'].forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value && !/^\d+$/.test(el.value.trim())) {
        const label = document.querySelector(`label[for="${id}"]`)?.innerText || id;
        errors.push(`O campo "${label}" deve conter apenas números.`);
      }
    });

    if (errors.length) {
      e.preventDefault();
      alert(errors.join('\n'));
    }
  });
});
