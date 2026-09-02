@props([
    'title',
    'icon' => null,
    'createRoute' => null,
    'createText' => 'Cadastrar',
    'createIcon' => 'bi-plus-lg',
    'createParams' => [],
])

<div class="list-header">
    <h1>
        @if ($icon)
            <i class="bi {{ $icon }}"></i>
        @endif

        {{ $title }}
    </h1>

    @if ($createRoute)
        <a
            href="{{ route($createRoute, $createParams) }}"
            class="btn btn-primary"
        >
            <i class="bi {{ $createIcon }}"></i>
            {{ $createText }}
        </a>
    @endif
</div>
