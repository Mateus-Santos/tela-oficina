document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // TAGS-INPUT (GENÉRICO COM SUPORTE A EDIÇÃO)
    // ============================================================
    document.querySelectorAll('.tags-input').forEach(container => {

        const input = container.querySelector('input[type="text"]');
        const tagsContainer = container.querySelector('.tags-container');
        const fieldName = container.dataset.name;

        container.tags = [];

        const updateTags = () => {
            tagsContainer.innerHTML = '';
            container.querySelectorAll('input[type=hidden]').forEach(e => e.remove());

            container.tags.forEach((tag, index) => {
                const el = document.createElement('div');
                el.className = 'tag';
                el.innerHTML = `
                    ${tag.label}
                    <span class="remove-tag" data-i="${index}">&times;</span>
                `;
                tagsContainer.appendChild(el);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = fieldName;
                hidden.value = tag.id;
                container.appendChild(hidden);
            });
        };

        const addTag = tag => {
            if (!container.tags.some(t => t.id == tag.id)) {
                container.tags.push(tag);
                updateTags();
            }
        };

        if (container.dataset.tags) {
            try {
                JSON.parse(container.dataset.tags).forEach(tag => container.tags.push(tag));
            } catch (e) {
                console.error('Erro ao carregar tags iniciais', e);
            }
        }

        updateTags();

        if (input) {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = input.value.trim();
                    if (!val) return;
                    addTag({ id: val, label: val });
                    input.value = '';
                }
            });
        }

        tagsContainer.addEventListener('click', e => {
            if (e.target.classList.contains('remove-tag')) {
                container.tags.splice(e.target.dataset.i, 1);
                updateTags();
            }
        });

        container.addTag = addTag;
    });


    // ============================================================
    // SELECTS MONTADORA → VEÍCULOS
    // ============================================================
    const montadoraSelect = document.getElementById('montadora_select');
    const veiculoSelect = document.getElementById('veiculo_select');
    const veiculoTags = document.querySelector('[data-name="veiculos[]"]');

    if (montadoraSelect && veiculoSelect && veiculoTags) {

        montadoraSelect.addEventListener('change', async () => {
            const id = montadoraSelect.value;
            veiculoSelect.innerHTML = `<option>Carregando...</option>`;

            if (!id) {
                veiculoSelect.innerHTML = `<option value="">Selecione uma montadora</option>`;
                return;
            }

            try {
                const res = await fetch(`/api/montadora/${id}/veiculos`);
                const lista = await res.json();

                veiculoSelect.innerHTML = `<option value="">Selecione um veículo</option>`;

                lista.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = `${v.nome} (${v.montadora.nome})`;
                    opt.dataset.label = opt.textContent;
                    veiculoSelect.appendChild(opt);
                });

            } catch (e) {
                console.error('Erro ao carregar veículos', e);
                veiculoSelect.innerHTML = `<option value="">Erro ao carregar</option>`;
            }
        });

        veiculoSelect.addEventListener('change', () => {
            const o = veiculoSelect.selectedOptions[0];
            if (!o || !o.value) return;

            veiculoTags.addTag({
                id: o.value,
                label: o.dataset.label
            });

            veiculoSelect.value = "";
        });
    }


    // ============================================================
    // MÁSCARA DE PREÇO
    // ============================================================
    const precoEl = document.getElementById('preco_uni');

    if (precoEl) {
        precoEl.addEventListener('input', () => {
            let v = precoEl.value.replace(/\D/g, '');
            if (!v) return precoEl.value = '';

            v = v.padStart(3, '0');
            const cents = v.slice(-2);
            let int = v.slice(0, -2).replace(/^0+/, '') || '0';
            int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            precoEl.value = `${int},${cents}`;
        });
    }


    /// ============================================================
    // PREVIEW DE IMAGEM
    // ============================================================
    const imgInput = document.getElementById('img');
    const imgPreview = document.getElementById('img-preview');
    const imgCurrent = document.getElementById('img-current');

    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];

            // Nenhum arquivo → restaura imagem atual
            if (!file) {
                imgPreview.style.display = 'none';
                imgPreview.src = '';

                if (imgCurrent) {
                    imgCurrent.style.display = 'block';
                }
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Selecione apenas imagens');
                imgInput.value = '';
                return;
            }

            // Esconde imagem atual
            if (imgCurrent) {
                imgCurrent.style.display = 'none';
            }

            const reader = new FileReader();
            reader.onload = e => {
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        });
    }

});
