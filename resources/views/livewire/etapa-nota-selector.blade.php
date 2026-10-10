<div>

    <select
        wire:model.live="etapaId"
        class="form-select form-select-sm @error('etapa') is-invalid @enderror"
        @disabled(
            $nota->status !== 'Aberto'
            || !auth()->check()
            || auth()->user()->permitions == 2
        )
    >

        @foreach($etapas as $etapa)

            <option
                value="{{ $etapa->id }}"
            >
                {{ $etapa->nome }}

                @if(!$etapa->ativo)
                    (inativa)
                @endif
            </option>

        @endforeach

    </select>

    @error('etapa')

        <div class="text-danger small mt-1">

            <i class="bi bi-exclamation-triangle"></i>

            {{ $message }}

        </div>

    @enderror

</div>
