<script type="text/javascript" async src="https://www.clarity.ms/tag/vo52awk0jq"></script>

<script>
    !function (f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function () {
            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1789817231630101');
    fbq('track', 'PageView');
</script>

<script async src="https://www.googletagmanager.com/gtag/js?id=G-G0KFZYXM3D"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-G0KFZYXM3D');
</script>

<script>
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
</script>

@if(! \App\Support\SerikHomepage::isHomepageRequest())
<script defer src="{{ Theme::asset()->url('js/visitor-location.js') }}?v={{ get_cms_version() }}"></script>
@endif

<noscript>
    <img height="1" width="1" style="display:none" alt=""
        src="https://www.facebook.com/tr?id=1789817231630101&ev=PageView&noscript=1" />
</noscript>
