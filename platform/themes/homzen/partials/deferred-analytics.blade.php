{{-- Analytics stubs immediately; real tags load on interaction or ~3.5s (all pages). --}}
<script>
(function () {
    if (window.__serikAnalyticsLoaderBound) {
        return;
    }
    window.__serikAnalyticsLoaderBound = true;

    // Stubs so early calls queue (never drop events before scripts arrive).
    window.dataLayer = window.dataLayer || [];
    if (typeof window.gtag !== 'function') {
        window.gtag = function () { window.dataLayer.push(arguments); };
    }
    if (typeof window.fbq !== 'function') {
        var n = function () {
            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
        };
        n.push = n;
        n.loaded = true;
        n.version = '2.0';
        n.queue = [];
        window.fbq = n;
        window._fbq = n;
    }

    function injectScript(src) {
        if (document.querySelector('script[src="' + src + '"]')) {
            return;
        }
        var script = document.createElement('script');
        script.src = src;
        script.async = true;
        document.body.appendChild(script);
    }

    function loadAnalytics() {
        if (window.__serikAnalyticsLoaded) {
            return;
        }
        window.__serikAnalyticsLoaded = true;

        // Facebook Pixel (replace stub with real loader)
        !function (f, b, e, v, n, t, s) {
            if (f.fbq && f.fbq.loaded && f.fbq._loadedReal) return;
            n = f.fbq = function () {
                n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n._loadedReal = !0;
            n.version = '2.0';
            n.queue = n.queue || [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s);
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1789817231630101');
        fbq('track', 'PageView');

        if (!document.querySelector('script[src*="googletagmanager.com/gtag/js"]')) {
            injectScript('https://www.googletagmanager.com/gtag/js?id=G-G0KFZYXM3D');
        }
        window.gtag('js', new Date());
        window.gtag('config', 'G-G0KFZYXM3D');
        if (!window.__serikAdsAwConfigured) {
            window.gtag('config', 'AW-18147434933');
            window.__serikAdsAwConfigured = true;
        }

        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = d.getElementsByTagName(s)[0];
            var j = d.createElement(s);
            var dl = l !== 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-M57VSQWW');
    }

    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (eventName) {
        window.addEventListener(eventName, loadAnalytics, { once: true, passive: true });
    });
    window.setTimeout(loadAnalytics, 3500);
})();
</script>

<noscript>
    <img height="1" width="1" style="display:none" alt=""
        src="https://www.facebook.com/tr?id=1789817231630101&ev=PageView&noscript=1" />
</noscript>
