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

        .property-page-nav {
            top: 0 !important;
        }

        section.flat-property-detail {
            padding-top: 0 !important;
            padding-bottom: 8px !important;
        }

        .flat-property-detail .container {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .header-property-detail {
            margin: 0 !important;
            padding: 6px 0 0 !important;
        }

        .header-property-detail .content-top {
            gap: 4px !important;
            margin-bottom: 0 !important;
        }

        .header-property-detail .title {
            margin-bottom: 0 !important;
        }

        .single-property-overview,
        .single-property-element {
            margin-top: 0 !important;
            padding-top: 0 !important;
            margin-bottom: 10px !important;
            padding-bottom: 10px !important;
        }

        .single-property-overview .info-box1 {
            margin-top: 0 !important;
            padding-top: 6px !important;
            padding-bottom: 6px !important;
        }

        .flat-property-detail .widget-box,
        .flat-property-detail .box,
        .flat-property-detail .single-property-desc {
            margin-top: 8px !important;
            margin-bottom: 10px !important;
        }
    }

    section.flat-property-detail ~ .flat-latest-property {
        display: block !important;
    }

    .single-property-map,
    #location {
        display: block !important;
    }

    #map,
    [data-bb-toggle="detail-map"] {
        display: block !important;
        min-height: 320px;
    }
</style>
<script>
(function () {
    function notifyParentReady() {
        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'hs-property-iframe-ready' }, '*');
            }
        } catch (e) {}
    }

    // Signal as soon as this script runs (before full DOMContentLoaded).
    notifyParentReady();

    function hydrateDeferredRelated() {
        const section = document.getElementById('similarProperties');
        const propertyId = section?.dataset?.relatedDefer;
        if (!section || !propertyId) {
            return;
        }

        fetch('/api/v1/related-properties/' + encodeURIComponent(propertyId), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((data) => {
                if (!data || !data.success || !data.html) {
                    section.remove();
                    return;
                }

                const titleEl = section.querySelector('.section-title');
                if (titleEl && data.sectionTitle) {
                    titleEl.textContent = data.sectionTitle;
                }

                const status = section.querySelector('.hs-related-defer-status');
                if (status) {
                    status.remove();
                }

                const swiperRoot = section.querySelector('.swiper.tf-latest-property');
                const wrapper = swiperRoot?.querySelector('.swiper-wrapper');
                if (!swiperRoot || !wrapper) {
                    section.remove();
                    return;
                }

                wrapper.innerHTML = data.html;
                swiperRoot.hidden = false;
                section.classList.remove('is-loading');
                section.removeAttribute('aria-busy');
                section.removeAttribute('data-related-defer');

                if (window.Swiper && !swiperRoot.swiper) {
                    try {
                        new Swiper(swiperRoot, {
                            slidesPerView: 1.15,
                            spaceBetween: 16,
                            loop: data.count > 2,
                            breakpoints: {
                                576: { slidesPerView: 2, spaceBetween: 20 },
                                992: { slidesPerView: 3, spaceBetween: 30 },
                            },
                        });
                    } catch (e) {}
                }

                notifyParentReady();
            })
            .catch(() => {
                section.remove();
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const gallery = document.getElementById('galleryContainer');
        if (gallery) {
            gallery.style.display = 'block';
        }
        notifyParentReady();
        hydrateDeferredRelated();

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

        // Ensure detail map initializes inside the modal iframe after Leaflet loads.
        function refreshDetailMap() {
            const mapEl = document.getElementById('map');
            if (!mapEl || typeof L === 'undefined') {
                return;
            }
            if (mapEl._leaflet_id) {
                try {
                    window.dispatchEvent(new Event('resize'));
                } catch (e) {}
                return;
            }
            if (!mapEl.dataset.center) {
                return;
            }
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
        }

        window.addEventListener('load', function () {
            setTimeout(refreshDetailMap, 200);
            setTimeout(refreshDetailMap, 800);
        });
    });
})();
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
