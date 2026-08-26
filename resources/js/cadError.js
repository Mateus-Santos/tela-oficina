document.addEventListener('DOMContentLoaded', function () {
    const popup = document.querySelector('.error-popup');

    if (!popup) {
        return;
    }

    const closeButtons = popup.querySelectorAll(
        '.error-popup__close, .error-popup__button'
    );

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            popup.remove();
        });
    });
});
