@extends('layouts.layout')

@section('content')
  <section class="container cadastro">

    <h1>⚙️CADASTRO DE PRODUTOS</h1>

    {{-- Exibe erros de validação --}}
    @if ($errors->any())
      <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Exibe mensagem de sucesso --}}
    @if (session('success'))
      <div class="alert alert-success">
        {{ session('success') }}
      </div>
    @endif

    <form id="form-produto"
          action="{{ route('produtos.store') }}"
          enctype="multipart/form-data"
          method="POST"
          class="row g-3">
      @csrf
      <div class="campos">
        <div class="row mb-3">
          <div class="col-md-2">
            <label class="form-label" for="codigo_barras">Código de Barras:</label>
            <input type="text"
                    class="form-control"
                    id="codigo_barras"
                    name="codigo_barras"
                    placeholder="Digite e saia do campo para buscar">
          </div>
        </div>
        
        {{-- Linha 1 --}}
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label" for="nome">Nome:*</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>

          <div class="col-md-2">
            <label class="form-label" for="ano_modelo">Ano Modelo:*</label>
            <input type="number" class="form-control" id="ano_modelo" name="ano_modelo" required>
          </div>

          <div class="col-md-3">
            <label class="form-label" for="preco_uni">Preço Unitário (R$):*</label>
            <input type="number" step="0.01" class="form-control" id="preco_uni" name="preco_uni" required>
          </div>

          <div class="col-md-2">
            <label class="form-label" for="quantidade">Quantidade:*</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" required>
          </div>

        </div>

        {{-- Linha 2 --}}
        <div class="row mb-3">
          <div class="col-md-2">
            <label class="form-label" for="descricao">Descrição:</label>
            <input type="text" class="form-control" id="descricao" name="descricao">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="motor">Motor:</label>
            <input type="text" class="form-control" id="motor" name="motor">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="codigo_fabricante">Cod. do Fabricante:*</label>
            <input type="text" class="form-control" id="codigo_fabricante" name="codigo_fabricante" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="img">Imagem:</label>
            <input type="file" class="form-control" id="img" name="img">
          </div>
        </div>
        
        {{-- Linha 3 --}}
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Veículo(s):*</label>
            <div class="tags-input" data-name="veiculos[]">
              <input class="form-control" type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Marca(s):*</label>
            <div class="tags-input" data-name="marcas[]">
              <input class="form-control" type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Departamento(s):*</label>
            <div class="tags-input" data-name="departamentos[]">
              <input class="form-control" type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label">Válvula(s):</label>
            <div class="tags-input" data-name="valvula[]">
              <input class="form-control" type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Montadora(s):*</label>
            <div class="tags-input" data-name="montadora[]">
              <input class="form-control" type="text" placeholder="Digite e use vírgula ou Enter" autocomplete="off">
              <div class="tags-container"></div>
            </div>
          </div>
            
          </div>
        </div>

        {{-- Botão --}}
        <div class="col text-center">
          <button type="submit" class="btn btn-success">Cadastrar</button>
        </div>
      </div>
    </form>
  </section>

  <script>
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
  </script>
@endsection
