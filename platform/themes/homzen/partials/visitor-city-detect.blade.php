<script>
(function () {
    const scriptUrl = @json(Theme::asset()->url('js/visitor-location.js') . '?v=' . get_cms_version());

    function runDetect() {
        if (!window.SerikVisitorLocation) {
            return;
        }

        if (window.SerikVisitorLocation.hasStoredCity() && window.SerikVisitorLocation.getSessionLocation()) {
            return;
        }

        window.SerikVisitorLocation.detectCityInBackground();
    }

    if (window.SerikVisitorLocation) {
        runDetect();
        return;
    }

    function loadVisitorScript() {
        if (document.querySelector('script[data-serik-visitor-location="1"]')) {
            return;
        }

        const script = document.createElement('script');
        script.src = scriptUrl;
        script.defer = true;
        script.dataset.serikVisitorLocation = '1';
        script.onload = runDetect;
        document.head.appendChild(script);
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadVisitorScript, { timeout: 2000 });
    } else {
        setTimeout(loadVisitorScript, 300);
    }
})();
</script>
