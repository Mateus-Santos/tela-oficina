<div>
    <select 
        wire:model.live="status" 
        class="form-select form-select-sm {{ $status === 'Cancelado' ? 'text-danger fw-bold' : '' }}"
    >
        @foreach($opcoesStatus as $valor => $rotulo)
            <option value="{{ $valor }}">{{ $rotulo }}</option>
        @endforeach
    </select>
</div>