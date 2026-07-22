@if(request()->boolean('iframe'))
<style>
    #header, .footer, .icon-bar, .top-header, .lc_text-widget, .mobile-bottom-nav,
    .breadcrumb-wrap, .flat-breadcrumb {
        display: none !important;
    }

    html {
        overflow-x: hidden !important;
        overflow-y: scroll !important;
        -webkit-overflow-scrolling: touch;
        height: auto !important;
        min-height: 100%;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff;
        overflow-x: hidden !important;
        overflow-y: visible !important;
        height: auto !important;
        min-height: 100%;
        position: relative !important;
        touch-action: pan-y;
    }

    #wrapper,
    #wrapper > .clearfix {
        overflow: visible !important;
        min-height: 0;
    }

    #galleryContainer {
        display: block !important;
    }

    .property-page-nav {
        display: block !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 50;
        background: #fff;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
    }

    @media (min-width: 768px) {
        section.flat-property-detail,
        section.flat-property-detail > .container {
            overflow: visible !important;
        }

        .flat-property-detail .row {
            align-items: flex-start !important;
        }

        .flat-property-detail .row > .col-lg-4 {
            align-self: flex-start !important;
            position: relative !important;
            z-index: 4;
        }

        .flat-property-detail .row > .col-lg-4 .widget-sidebar {
            max-height: none !important;
            overflow: visible !important;
        }
    }

    @media (max-width: 767px) {
        .flat-property-detail .row > .col-lg-4 {
            position: static !important;
            top: auto !important;
        }
    }

    section.flat-property-detail ~ .flat-latest-property {
        display: none !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const gallery = document.getElementById('galleryContainer');
    if (gallery) {
        gallery.style.display = 'block';
    }

    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'hs-property-iframe-ready' }, '*');
        }
    } catch (e) {}

    (function initIframeFormPin() {
        const mq = window.matchMedia('(min-width: 768px)');
        const formCol = document.querySelector('.flat-property-detail .row > .col-lg-4');
        const formSidebar = formCol?.querySelector('.widget-sidebar');
        const formRow = formCol?.closest('.flat-property-detail .row');

        if (!formCol || !formSidebar || !formRow) {
            return;
        }

        let pinLeft = null;
        let pinWidth = null;

        function navTop() {
            const nav = document.querySelector('.property-page-nav');
            return (nav ? nav.offsetHeight : 0) + 4;
        }

        function resetFormPin() {
            pinLeft = null;
            pinWidth = null;
            formSidebar.style.position = '';
            formSidebar.style.top = '';
            formSidebar.style.left = '';
            formSidebar.style.right = '';
            formSidebar.style.bottom = '';
            formSidebar.style.width = '';
            formSidebar.style.zIndex = '';
        }

        function capturePinMetrics() {
            if (pinLeft !== null) {
                return;
            }

            const rect = formSidebar.getBoundingClientRect();
            pinLeft = rect.left;
            pinWidth = rect.width;
        }

        function updateFormPin() {
            if (!mq.matches) {
                resetFormPin();
                return;
            }

            const top = navTop();
            const rowRect = formRow.getBoundingClientRect();
            const sidebarHeight = formSidebar.offsetHeight;

            if (rowRect.top >= top) {
                resetFormPin();
                return;
            }

            capturePinMetrics();

            if (rowRect.bottom <= top + sidebarHeight) {
                formSidebar.style.position = 'absolute';
                formSidebar.style.top = 'auto';
                formSidebar.style.bottom = '0';
                formSidebar.style.left = '0';
                formSidebar.style.right = 'auto';
                formSidebar.style.width = '100%';
                formSidebar.style.zIndex = '4';
                return;
            }

            formSidebar.style.position = 'fixed';
            formSidebar.style.top = top + 'px';
            formSidebar.style.left = pinLeft + 'px';
            formSidebar.style.width = pinWidth + 'px';
            formSidebar.style.bottom = 'auto';
            formSidebar.style.right = 'auto';
            formSidebar.style.zIndex = '4';
        }

        window.addEventListener('scroll', updateFormPin, { passive: true });
        window.addEventListener('resize', function () {
            resetFormPin();
            updateFormPin();
        });
        mq.addEventListener('change', function () {
            resetFormPin();
            updateFormPin();
        });

        updateFormPin();

        window.addEventListener('load', function () {
            resetFormPin();
            setTimeout(updateFormPin, 100);
            setTimeout(updateFormPin, 500);
        });
    })();

    function refreshDetailMap() {
        const mapEl = document.getElementById('map');
        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        if (!mapEl._leaflet_id && mapEl.dataset.center) {
            let center = mapEl.dataset.center;
            try {
                center = JSON.parse(center);
            } catch (e) {}

            const map = L.map(mapEl, {
                attributionControl: false,
                scrollWheelZoom: true,
                dragging: !L.Browser.mobile,
                touchZoom: true,
            }).setView(center, 14);

            L.tileLayer(mapEl.dataset.tileLayer || '', {
                maxZoom: mapEl.dataset.maxZoom || 22,
            }).addTo(map);

            L.marker(center, {
                icon: L.divIcon({
                    iconSize: L.point(50, 50),
                    className: 'map-marker-home',
                }),
            }).addTo(map);

            return;
        }

        if (mapEl._leaflet_id) {
            window.dispatchEvent(new Event('resize'));
        }
    }

    window.addEventListener('load', function () {
        setTimeout(refreshDetailMap, 300);
        setTimeout(refreshDetailMap, 1000);
    });
});
</script>
@endif
<style>
    .hs-desc-truncated .hs-desc-full {
        display: none !important;
    }
    .hs-desc-expanded .hs-desc-short {
        display: none !important;
    }
    .hs-desc-expanded .hs-desc-full {
        display: block !important;
    }
    .hs-desc-toggle {
        color: #0255a1;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        margin-top: 4px;
        display: inline-block;
        border: none;
        background: none;
        padding: 0;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.hs-desc-toggle').forEach((btn) => {
        btn.addEventListener('click', function () {
            const wrap = this.closest('.hs-desc-wrap');
            const body = wrap?.querySelector('.hs-desc-body');
            if (!body) return;
            const expanded = body.classList.toggle('hs-desc-expanded');
            body.classList.toggle('hs-desc-truncated', !expanded);
            this.textContent = expanded ? 'Show Less' : 'Show More';
        });
    });
});
</script>
