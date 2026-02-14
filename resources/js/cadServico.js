document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // MÁSCARA DE PREÇO
    // ============================================================

    const precoEl = document.getElementById('valor');

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

});