(function () {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    window.addEventListener("load", function () {
        navigator.serviceWorker.register("/sw.js").catch(function () {
            // silent fail on environments where SW registration is restricted
        });
    });
})();

