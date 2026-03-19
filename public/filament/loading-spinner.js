(function () {
    var overlayId = 'smsa-loading-overlay';
    var active = 0;
    var showTimer = null;
    var hideTimer = null;
    var failsafeTimer = null;
    var shownAt = 0;
    var isVisible = false;
    var originalFetch = window.fetch;
    var SHOW_DELAY_MS = 220;
    var HIDE_DELAY_MS = 180;
    var MIN_VISIBLE_MS = 550;
    var FAILSAFE_MS = 12000;

    function getOverlay() {
        var el = document.getElementById(overlayId);
        if (el) return el;

        el = document.createElement('div');
        el.id = overlayId;
        el.className = 'smsa-loading-overlay';
        el.innerHTML = '<div class="smsa-loading-spinner" aria-label="A carregar"></div>';
        document.body.appendChild(el);
        return el;
    }

    function show() {
        clearTimeout(hideTimer);
        if (showTimer) return;
        if (isVisible) return;

        showTimer = setTimeout(function () {
            getOverlay().classList.add('is-visible');
            isVisible = true;
            shownAt = Date.now();
            showTimer = null;
        }, SHOW_DELAY_MS);
    }

    function hide() {
        clearTimeout(showTimer);
        showTimer = null;
        clearTimeout(failsafeTimer);

        clearTimeout(hideTimer);
        var elapsed = isVisible ? Date.now() - shownAt : 0;
        var minRemaining = Math.max(0, MIN_VISIBLE_MS - elapsed);

        hideTimer = setTimeout(function () {
            getOverlay().classList.remove('is-visible');
            isVisible = false;
            shownAt = 0;
        }, Math.max(HIDE_DELAY_MS, minRemaining));
    }

    function start() {
        active += 1;
        show();

        clearTimeout(failsafeTimer);
        failsafeTimer = setTimeout(function () {
            active = 0;
            hide();
        }, FAILSAFE_MS);
    }

    function stop() {
        active = Math.max(0, active - 1);
        if (active === 0) hide();
    }

    function isLivewireRequest(input) {
        var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
        return url.indexOf('/livewire/') !== -1;
    }

    function setupFetchHook() {
        if (typeof originalFetch !== 'function') return;

        window.fetch = function (input, init) {
            var track = isLivewireRequest(input);
            if (track) start();

            return originalFetch(input, init)
                .then(function (response) {
                    if (track) stop();
                    return response;
                })
                .catch(function (error) {
                    if (track) stop();
                    throw error;
                });
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        getOverlay();
        setupFetchHook();

        document.addEventListener('livewire:navigating', start);
        document.addEventListener('livewire:navigated', function () {
            active = 0;
            hide();
        });
    });
})();
