(function () {
    var KEY = 'smsa-livewire-419-reloaded';
    var TTL_MS = 15000;

    function shouldReload() {
        try {
            var raw = sessionStorage.getItem(KEY);
            if (!raw) {
                sessionStorage.setItem(KEY, String(Date.now()));
                return true;
            }

            var ts = parseInt(raw, 10);
            if (Number.isNaN(ts)) {
                sessionStorage.setItem(KEY, String(Date.now()));
                return true;
            }

            return (Date.now() - ts) > TTL_MS;
        } catch (e) {
            return true;
        }
    }

    function clearReloadMark() {
        try {
            sessionStorage.removeItem(KEY);
        } catch (e) {
            // noop
        }
    }

    document.addEventListener('livewire:init', function () {
        if (typeof Livewire === 'undefined' || typeof Livewire.hook !== 'function') {
            return;
        }

        Livewire.hook('request', function (_ref) {
            var fail = _ref.fail;

            fail(function (_ref2) {
                var status = _ref2.status;
                var preventDefault = _ref2.preventDefault;

                if (status !== 419) {
                    clearReloadMark();
                    return;
                }

                preventDefault();

                if (shouldReload()) {
                    window.location.reload();
                }
            });
        });
    });
})();
