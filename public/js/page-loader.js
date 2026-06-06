(function () {
    const loader = document.getElementById('page-loader');
    if (!loader) return;

    let downloadUntil = 0;
    let autoHideTimer = null;

    function showLoader() {
        if (Date.now() < downloadUntil) {
            return;
        }

        loader.classList.add('is-active');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-loading');

        window.clearTimeout(autoHideTimer);
        autoHideTimer = window.setTimeout(() => {
            if (document.visibilityState === 'visible') {
                hideLoader();
            }
        }, 3000);
    }

    function hideLoader() {
        loader.classList.remove('is-active');
        loader.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-loading');
        window.clearTimeout(autoHideTimer);
    }

    function markDownloadIntent() {
        downloadUntil = Date.now() + 8000;
        hideLoader();
    }

    function isDownloadLink(link) {
        if (link.dataset.noLoader !== undefined || link.hasAttribute('download')) {
            return true;
        }

        const href = link.getAttribute('href') || '';

        return /\/(exportar|export|plantilla)(\/|\?|$)/i.test(href);
    }

    window.PageLoader = { show: showLoader, hide: hideLoader };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');

        if (!link) {
            return;
        }

        if (isDownloadLink(link)) {
            markDownloadIntent();

            return;
        }

        if (link.target === '_blank') {
            return;
        }

        const href = link.getAttribute('href');

        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        if (link.origin !== window.location.origin) {
            return;
        }

        showLoader();
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;

        if (form instanceof HTMLFormElement && form.dataset.noLoader !== undefined) {
            return;
        }

        showLoader();
    });

    window.addEventListener('beforeunload', () => {
        if (Date.now() < downloadUntil) {
            return;
        }

        showLoader();
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hideLoader();
        }
    });

    window.addEventListener('focus', () => {
        if (Date.now() < downloadUntil) {
            hideLoader();
        }
    });

    window.addEventListener('load', hideLoader);
})();
