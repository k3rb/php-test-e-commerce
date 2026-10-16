document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-loading-form]');

    if (!form) {
        return;
    }

    const button = event.submitter ?? form.querySelector('button[type="submit"]');

    if (!button) {
        return;
    }

    const spinner = button.querySelector('[data-loading-spinner]');
    const label = button.querySelector('[data-loading-label]');

    form.setAttribute('aria-busy', 'true');
    button.disabled = true;
    spinner?.classList.remove('d-none');

    if (label) {
        label.textContent = button.dataset.loadingText ?? 'Chargement…';
    }
});
