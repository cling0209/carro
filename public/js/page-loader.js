(function () {
    const loader = document.getElementById('page-loader');
    if (!loader) return;

    function showLoader() {
        loader.classList.add('is-active');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-loading');
    }

    function hideLoader() {
        loader.classList.remove('is-active');
        loader.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-loading');
    }

    window.PageLoader = { show: showLoader, hide: hideLoader };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
        if (link.dataset.noLoader !== undefined) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.origin !== window.location.origin) return;
        showLoader();
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (form instanceof HTMLFormElement && form.dataset.noLoader !== undefined) return;
        showLoader();
    });

    window.addEventListener('beforeunload', () => showLoader());

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) hideLoader();
    });

    window.addEventListener('load', hideLoader);
})();
