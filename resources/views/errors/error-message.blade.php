@if ($errors->any())
    <div class="error-popup" role="alert">

        <div class="error-popup__header">
            <div class="error-popup__title">
                Ops, algo deu errado.
            </div>

            <button
                type="button"
                class="error-popup__close"
                aria-label="Fechar mensagem"
            >
                &times;
            </button>
        </div>

        <div class="error-popup__body">
            <ul class="error-popup__list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

        <div class="error-popup__footer">
            <button
                type="button"
                class="btn btn-warning error-popup__button"
            >
                Fechar
            </button>
        </div>

    </div>
@endif
