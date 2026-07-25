(() => {
    const form = document.getElementById('shop-search-form');
    const input = document.getElementById('shop-search-input');
    const panel = document.getElementById('shop-search-panel');

    if (!form || !input || !panel) {
        return;
    }

    const suggestUrl = form.dataset.suggestUrl;
    const catalogUrl = form.getAttribute('action') || '/catalogo';
    const minChars = 2;
    const debounceMs = 250;

    let debounceTimer = null;
    let abortController = null;
    let activeIndex = -1;
    let items = [];

    function hidePanel() {
        panel.hidden = true;
        panel.innerHTML = '';
        activeIndex = -1;
        items = [];
        input.setAttribute('aria-expanded', 'false');
    }

    function showPanel(html) {
        panel.innerHTML = html;
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function setActive(index) {
        const links = panel.querySelectorAll('[data-suggest-index]');
        links.forEach((el) => el.classList.remove('is-active'));
        if (index < 0 || index >= links.length) {
            activeIndex = -1;
            return;
        }
        activeIndex = index;
        links[activeIndex].classList.add('is-active');
        links[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    function renderSuggestions(data, query) {
        items = Array.isArray(data) ? data : [];

        if (items.length === 0) {
            showPanel(`<div class="shop-search__empty">Sin coincidencias para “${escapeHtml(query)}”</div>`);
            return;
        }

        const list = items.map((item, index) => {
            return `<a class="shop-search__item" role="option" href="${escapeAttr(item.url)}" data-suggest-index="${index}">
                <span class="shop-search__name">${escapeHtml(item.name)}</span>
                <span class="shop-search__price">${escapeHtml(item.price_label || '')}</span>
            </a>`;
        }).join('');

        const footer = `<a class="shop-search__footer" href="${escapeAttr(catalogUrl + '?q=' + encodeURIComponent(query))}">
            Ver todos los resultados
        </a>`;

        showPanel(list + footer);
        activeIndex = -1;
    }

    async function fetchSuggestions(query) {
        if (!suggestUrl || query.length < minChars) {
            hidePanel();
            return;
        }

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        showPanel('<div class="shop-search__hint">Buscando…</div>');

        try {
            const url = new URL(suggestUrl, window.location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                signal: abortController.signal,
            });
            if (!response.ok) {
                throw new Error('suggest failed');
            }
            const payload = await response.json();
            if (input.value.trim() !== query) {
                return;
            }
            renderSuggestions(payload.data || [], query);
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            hidePanel();
        }
    }

    function scheduleFetch() {
        const query = input.value.trim();
        clearTimeout(debounceTimer);
        if (query.length < minChars) {
            hidePanel();
            return;
        }
        debounceTimer = setTimeout(() => fetchSuggestions(query), debounceMs);
    }

    input.addEventListener('input', scheduleFetch);
    input.addEventListener('focus', () => {
        if (input.value.trim().length >= minChars && panel.hidden) {
            scheduleFetch();
        }
    });

    input.addEventListener('keydown', (event) => {
        if (panel.hidden) {
            return;
        }

        const links = panel.querySelectorAll('[data-suggest-index]');
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(Math.min(activeIndex + 1, links.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        } else if (event.key === 'Enter' && activeIndex >= 0 && links[activeIndex]) {
            event.preventDefault();
            window.location.href = links[activeIndex].href;
        } else if (event.key === 'Escape') {
            hidePanel();
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            hidePanel();
        }
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#96;');
    }
})();
