export function inicializarPreviewImagem() {
    const imgInput = document.getElementById('img');
    const imgPreview = document.getElementById('img-preview');
    const imgCurrent = document.getElementById('img-current');

    if (!imgInput) {
        return;
    }

    imgInput.addEventListener('change', () => {
        const file = imgInput.files[0];

        if (!file) {
            if (imgPreview) {
                imgPreview.src = '';
                imgPreview.style.display = 'none';
            }

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

        if (imgCurrent) {
            imgCurrent.style.display = 'none';
        }

        if (imgPreview) {
            const reader = new FileReader();

            reader.onload = e => {
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        }
    });
}
