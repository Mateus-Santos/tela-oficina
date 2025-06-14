@extends('layouts.layout')

@section('content')
  <section class="container cadastro">

    <h1>CADASTRO DE PRODUTOS</h1>

    <form id="form-produto" action="{{ route('produtos.store') }}" enctype="multipart/form-data" method="post" class="row g-3">
      @csrf
      <div class="campos">

        <!-- Montadora(s) -->
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Montadora(s):*</label>
            <div class="tags-input" data-name="montadora[]">
              <input type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <!-- Nome -->
          <div class="col-md-3">
            <label class="form-label" for="nome">Nome:*</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>

          <!-- Ano Modelo -->
          <div class="col-md-1">
            <label class="form-label" for="ano">Ano Modelo:*</label>
            <input type="text" class="form-control" id="ano" name="ano" required>
          </div>

          <!-- Veículo(s) -->
          <div class="col-md-3">
            <label class="form-label">Veículo(s):*</label>
            <div class="tags-input" data-name="veiculos[]">
              <input type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>
        </div>

        <!-- Segunda linha -->
        <div class="row mb-3">
          <!-- Quantidade -->
          <div class="col-md-2">
            <label class="form-label" for="quantidade">Quantidade:*</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" required>
          </div>

          <!-- Marca(s) -->
          <div class="col-md-2">
            <label class="form-label">Marca(s):*</label>
            <div class="tags-input" data-name="marcas[]">
              <input type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <!-- Departamento(s) -->
          <div class="col-md-2">
            <label class="form-label">Departamento(s):*</label>
            <div class="tags-input" data-name="departamentos[]">
              <input type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <!-- Válvula(s) -->
          <div class="col-md-2">
            <label class="form-label">Válvula(s):</label>
            <div class="tags-input" data-name="valvula[]">
              <input type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <!-- Motor -->
          <div class="col-md-2">
            <label class="form-label" for="motor">Motor:</label>
            <input type="text" class="form-control" id="motor" name="motor">
          </div>

          <!-- Descrição -->
          <div class="col-md-2">
            <label class="form-label" for="descricao">Descrição:</label>
            <input type="text" class="form-control" id="descricao" name="descricao">
          </div>
        </div>

        <!-- Terceira linha -->
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label" for="codigo_fabricante">Código do Fabricante:*</label>
            <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="preco_uni">Preço Unitário (R$):*</label>
            <input type="number" step="0.01" class="form-control" id="preco_uni" name="preco_uni" required>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="img">Imagem:</label>
            <input type="file" class="form-control" id="img" name="img">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="codigo_barras">Código de Barras:</label>
            <input type="text"
                   class="form-control"
                   id="codigo_barras"
                   name="codigo_barras"
                   placeholder="Digite e saia do campo para buscar">
          </div>
        </div>

        <div class="col text-center">
          <button type="submit" class="btn btn-success">Cadastrar</button>
        </div>

      </div>
    </form>
  </section>

  {{-- estilos para tags-input --}}
  <style>
    .tags-input {
      border: 1px solid #ced4da;
      padding: 5px 8px;
      border-radius: 4px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 5px;
      min-height: 40px;
    }
    .tags-input input[type=text] {
      border: none; outline: none; flex: 1; min-width: 120px;
    }
    .tags-container { display: flex; flex-wrap: wrap; gap: 5px; width: 100%; }
    .tag {
      background-color: #0d6efd; color: #fff;
      padding: 4px 10px; border-radius: 15px;
      display: flex; align-items: center; gap: 8px;
      user-select: none;
    }
    .tag .remove-tag { cursor: pointer; font-weight: bold; }
  </style>

  {{-- scripts --}}
  <script>
    // Inicializa todos os tags-input
    document.querySelectorAll('.tags-input').forEach(container => {
      const input = container.querySelector('input[type="text"]');
      const tagsContainer = container.querySelector('.tags-container');
      const fieldName = container.dataset.name;
      container.tags = [];

      container.updateTags = () => {
        tagsContainer.innerHTML = '';
        // remove hidden inputs antigos
        container.querySelectorAll('input[type=hidden]').forEach(e => e.remove());
        container.tags.forEach((tag, i) => {
          // cria tag visual
          const el = document.createElement('div'); el.className = 'tag';
          el.innerHTML = `${tag} <span class="remove-tag" data-i="${i}">&times;</span>`;
          tagsContainer.append(el);
          // cria hidden para submissão
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
          const idx = e.target.dataset.i;
          container.tags.splice(idx, 1);
          container.updateTags();
        }
      });
    });

    // Ao sair do campo de código de barras, faz a busca e preenche campos
    document.getElementById('codigo_barras').addEventListener('blur', async function() {
      const code = this.value.trim();
      if (!code) return;

      const res = await fetch(`{{ url('/produtos/barcode') }}/${code}`);
      if (!res.ok) {
        console.warn('Não encontrado');
        return;
      }
      const data = await res.json();

      // campos simples
      document.getElementById('nome').value      = data.nome      || '';
      document.getElementById('ano').value       = data.ano       || '';
      document.getElementById('motor').value     = data.motor     || '';
      document.getElementById('descricao').value = data.descricao || '';
      document.getElementById('preco_uni').value = data.preco_uni || '';

      // campos tags-array
      ['montadora[]','veiculos[]','marcas[]','departamentos[]','valvula[]'].forEach(fieldName => {
        const container = document.querySelector(`.tags-input[data-name="${fieldName}"]`);
        if (!container) return;
        const key = fieldName.replace('[]','');
        container.tags = Array.isArray(data[key]) ? data[key] : [];
        container.updateTags();
      });
    });
  </script>
@endsection
