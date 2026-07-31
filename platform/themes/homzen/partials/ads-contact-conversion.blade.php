{{-- Google Ads contact-lead conversion helper (fires once per successful submission) --}}
<script>
(function () {
    if (window.serikTrackAdsContactConversion) {
        return;
    }

    var SEND_TO = 'AW-18147434933/9HLLCL3a79kcELXDr81D';
    var AW_ID = 'AW-18147434933';
    var lockUntil = 0;

    function ensureGtag(callback) {
        window.dataLayer = window.dataLayer || [];
        if (typeof window.gtag !== 'function') {
            window.gtag = function () { window.dataLayer.push(arguments); };
        }

        if (window.__serikAdsAwConfigured) {
            callback();
            return;
        }

        // gtag.js already present (GA4 or Ads) — only add Ads config, do not load a second library.
        var hasGtagLib = !!document.querySelector('script[src*="googletagmanager.com/gtag/js"]');
        if (hasGtagLib || window.__serikAnalyticsLoaded) {
            window.__serikAdsAwConfigured = true;
            window.gtag('config', AW_ID);
            callback();
            return;
        }

        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(AW_ID);
        script.onload = function () {
            window.__serikAdsAwConfigured = true;
            window.gtag('js', new Date());
            window.gtag('config', AW_ID);
            callback();
        };
        script.onerror = function () {
            // Queue conversion even if script fails to load later via dataLayer.
            window.__serikAdsAwConfigured = true;
            window.gtag('js', new Date());
            window.gtag('config', AW_ID);
            callback();
        };
        document.head.appendChild(script);
    }

    /**
     * Fire Google Ads conversion once per successful Contact Us / inquiry lead.
     * Safe to call multiple times in the same submission flow — only the first call within 4s counts.
     */
    window.serikTrackAdsContactConversion = function () {
        var now = Date.now();
        if (now < lockUntil) {
            return;
        }
        lockUntil = now + 4000;

        ensureGtag(function () {
            window.gtag('event', 'conversion', {
                send_to: SEND_TO,
                value: 1.0,
                currency: 'CAD'
            });
        });
    };
})();
</script>
