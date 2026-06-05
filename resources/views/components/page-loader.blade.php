<div id="page-loader" aria-hidden="true" aria-live="polite" role="status">
    <div class="page-loader__panel">
        <img
            src="{{ asset('images/loading.gif') }}"
            data-fallback="{{ asset('images/loading.svg') }}"
            class="page-loader__media"
            alt=""
            width="72"
            height="72"
            onerror="if (!this.dataset.fallbackUsed) { this.dataset.fallbackUsed='1'; this.src=this.dataset.fallback; }"
        >
        <p class="page-loader__text">Cargando...</p>
    </div>
</div>
