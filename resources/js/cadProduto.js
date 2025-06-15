// Inicializa tags-input
    document.querySelectorAll('.tags-input').forEach(container => {
      const input = container.querySelector('input[type="text"]');
      const tagsContainer = container.querySelector('.tags-container');
      const fieldName = container.dataset.name;
      container.tags = [];

      container.updateTags = () => {
        tagsContainer.innerHTML = '';
        container.querySelectorAll('input[type=hidden]').forEach(e => e.remove());
        container.tags.forEach((tag, i) => {
          const el = document.createElement('div'); el.className = 'tag';
          el.innerHTML = `${tag} <span class="remove-tag" data-i="${i}">&times;</span>`;
          tagsContainer.append(el);
          const hidden = document.createElement('input');
          hidden.type = 'hidden'; hidden.name = fieldName; hidden.value = tag;
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

    // Busca por código de barras ao perder foco
    document.getElementById('codigo_barras')
      .addEventListener('blur', async function() {
      const code = this.value.trim();
      if (!code) return;
      try {
        const res = await fetch(`{{ url('produtos/barcode') }}/${code}`);
        if (!res.ok) throw new Error;
        const data = await res.json();
        // Preenche campos simples
        ['nome','ano_modelo','motor','descricao','preco_uni'].forEach(f => {
          document.getElementById(f).value = data[f] || '';
        });
        // Preenche tags
        ['montadora[]','veiculos[]','marcas[]','departamentos[]','valvula[]']
          .forEach(field => {
          const cont = document.querySelector(`.tags-input[data-name="${field}"]`);
          cont.tags = Array.isArray(data[field.replace('[]','')]) ? data[field.replace('[]','')] : [];
          cont.updateTags();
        });
      } catch {
        console.warn('Produto não encontrado pelo EAN.');
      }
    });