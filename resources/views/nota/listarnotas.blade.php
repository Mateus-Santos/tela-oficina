<div class="filtros-container mb-4">
    <form method="GET" action="{{ route('notas.index') }}" class="d-flex gap-2 align-items-center">
        
        <input 
            type="text" 
            name="cliente" 
            class="form-control" 
            placeholder="Nome do cliente..." 
            value="{{ request('cliente') }}"
        >

        <select name="status" class="form-select">
            <option value="">Status (Ativos por padrão)</option>
            <option value="Aberto" {{ request('status') == 'Aberto' ? 'selected' : '' }}>Aberto</option>
            <option value="Andamento" {{ request('status') == 'Andamento' ? 'selected' : '' }}>Em Andamento</option>
            <option value="Concluido" {{ request('status') == 'Concluido' ? 'selected' : '' }}>Concluído</option>
            <option value="Cancelado" {{ request('status') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
        </select>

        <button class="btn btn-warning" type="submit">
            <i class="bi bi-funnel"></i> Filtrar
        </button>
        
        <a href="{{ route('notas.index') }}" class="btn btn-secondary">
            <i class="bi bi-filter"></i> Limpar Filtros
        </a>
    </form>
</div>