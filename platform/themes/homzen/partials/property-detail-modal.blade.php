{{-- Shared property detail iframe modal (listing pages). Map view keeps its own copy in style-4. --}}
@once
<style>
.property-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    /* Above sticky header (10001) and listing toolbar (9998) */
    z-index: 10050;
}
.property-modal .modal-content {
    position: absolute;
    top: 2%;
    left: 50%;
    transform: translateX(-50%);
    width: 96%;
    max-width: 1420px;
    height: 96%;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.property-modal .iframe-loader {
    position: absolute;
    inset: 0;
    background: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
}
.property-modal .iframe-loader.is-hidden {
    display: none !important;
}
.property-modal .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #eee;
    border-top: 5px solid #2c7be5;
    border-radius: 50%;
    animation: serikPropModalSpin 0.8s linear infinite;
}
@keyframes serikPropModalSpin {
    100% { transform: rotate(360deg); }
}
.property-modal .modal-content iframe {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    height: 100%;
    border: none;
    display: block;
}
.property-modal .close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 28px;
    cursor: pointer;
    z-index: 10;
    line-height: 1;
}
html.hs-property-modal-open,
html.hs-property-modal-open body {
    overflow: hidden;
}
html.hs-property-modal-open .serik-site-header,
html.hs-property-modal-open .main-header,
html.hs-property-modal-open .serik-listing-toolbar,
html.hs-property-modal-open .serik-mobile-map-fab {
    z-index: 1 !important;
}
@media (min-width: 992px) {
    #propertyModal .modal-content {
        height: 96vh;
        max-height: 96vh;
    }
}
</style>
@endonce

@once
<div id="propertyModal" class="property-modal" style="display:none" aria-hidden="true">
    <div class="modal-content">
        <span class="close-modal" id="clearBtn_popup" role="button" tabindex="0" aria-label="{{ __('Close') }}">&times;</span>
        <div id="iframeLoader" class="iframe-loader is-hidden" aria-hidden="true">
            <div class="spinner"></div>
        </div>
        <iframe id="propertyFrame" src="" title="{{ __('Property details') }}" frameborder="0" allowfullscreen allow="fullscreen; clipboard-write" scrolling="yes"></iframe>
    </div>
</div>
@endonce

@once
<script>
(function () {
    if (window.__serikListingPropertyModalInit) {
        return;
    }
    window.__serikListingPropertyModalInit = true;

    function showLoader() {
        var loader = document.getElementById('iframeLoader');
        if (!loader) return;
        loader.classList.remove('is-hidden');
        loader.style.display = 'flex';
        loader.setAttribute('aria-hidden', 'false');
    }

    function hideLoader() {
        var loader = document.getElementById('iframeLoader');
        if (!loader) return;
        loader.style.display = 'none';
        loader.classList.add('is-hidden');
        loader.setAttribute('aria-hidden', 'true');
    }

    function ensureOnBody() {
        var modal = document.getElementById('propertyModal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    // Only define if map view has not already registered the manager.
    if (!window.PropertyDetailModalManager) {
        var isOpen = false;
        var savedScrollY = 0;
        var loaderTimeoutId = null;

        var mapLock = window.HsMapInteractionLock || {
            lock: function () {},
            forceUnlockAll: function () {},
        };

        window.PropertyDetailModalManager = {
            open: function (url) {
                var modal = document.getElementById('propertyModal');
                var iframe = document.getElementById('propertyFrame');
                if (!modal || !iframe || !url) return false;

                ensureOnBody();

                if (!isOpen) {
                    savedScrollY = window.scrollY || 0;
                    document.documentElement.classList.add('hs-property-modal-open');
                    document.body.dataset.hsModalScrollY = String(savedScrollY);
                    if (window.innerWidth <= 991) {
                        document.body.style.position = 'fixed';
                        document.body.style.top = '-' + savedScrollY + 'px';
                        document.body.style.left = '0';
                        document.body.style.right = '0';
                        document.body.style.width = '100%';
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.documentElement.style.overflow = 'hidden';
                    }
                    if (mapLock.lock) mapLock.lock();
                    isOpen = true;
                }

                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('is-open');

                if (iframe.dataset.hsLoadedUrl === url && iframe.contentDocument && iframe.contentDocument.body && iframe.contentDocument.body.childElementCount > 0) {
                    hideLoader();
                    return true;
                }

                showLoader();
                if (loaderTimeoutId) clearTimeout(loaderTimeoutId);
                loaderTimeoutId = setTimeout(hideLoader, 4000);

                iframe.onload = function () {
                    if (loaderTimeoutId) clearTimeout(loaderTimeoutId);
                    hideLoader();
                };
                iframe.onerror = function () {
                    if (loaderTimeoutId) clearTimeout(loaderTimeoutId);
                    hideLoader();
                };

                iframe.dataset.hsLoadedUrl = url;
                iframe.setAttribute('fetchpriority', 'high');
                iframe.src = url;
                return true;
            },
            close: function () {
                var modal = document.getElementById('propertyModal');
                if (loaderTimeoutId) clearTimeout(loaderTimeoutId);
                hideLoader();

                if (modal) {
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.classList.remove('is-open');
                }

                var iframe = document.getElementById('propertyFrame');
                if (iframe) {
                    iframe.src = 'about:blank';
                    delete iframe.dataset.hsLoadedUrl;
                }

                if (!isOpen) {
                    if (mapLock.forceUnlockAll) mapLock.forceUnlockAll();
                    return;
                }

                isOpen = false;
                document.documentElement.classList.remove('hs-property-modal-open');
                var scrollY = parseInt(document.body.dataset.hsModalScrollY || String(savedScrollY), 10) || 0;
                document.documentElement.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                delete document.body.dataset.hsModalScrollY;
                window.scrollTo(0, scrollY);
                if (mapLock.forceUnlockAll) mapLock.forceUnlockAll();
            },
            isOpen: function () {
                var modal = document.getElementById('propertyModal');
                return isOpen || (modal && modal.style.display === 'block');
            },
            onContentSettled: function () {
                if (loaderTimeoutId) clearTimeout(loaderTimeoutId);
                hideLoader();
            },
        };
    }

    if (typeof window.openPropertyDetailUrl !== 'function') {
        window.openPropertyDetailUrl = function (url) {
            return window.PropertyDetailModalManager.open(url);
        };
    }
    if (typeof window.closePropertyDetailModal !== 'function') {
        window.closePropertyDetailModal = function () {
            window.PropertyDetailModalManager.close();
        };
    }

    function toIframeUrl(url) {
        if (!url) return '';
        try {
            var u = new URL(url, window.location.origin);
            u.searchParams.set('iframe', '1');
            return u.toString();
        } catch (e) {
            return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'iframe=1';
        }
    }

    function initLifecycle() {
        var modal = document.getElementById('propertyModal');
        var closeBtn = document.getElementById('clearBtn_popup');
        if (!modal || modal.dataset.serikLifecycleBound === '1') return;
        modal.dataset.serikLifecycleBound = '1';

        closeBtn && closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.closePropertyDetailModal();
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                window.closePropertyDetailModal();
            }
        });

        var content = modal.querySelector('.modal-content');
        content && content.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && window.PropertyDetailModalManager.isOpen()) {
                e.preventDefault();
                window.closePropertyDetailModal();
            }
        });

        window.addEventListener('message', function (e) {
            if (e.data && e.data.type === 'hs-property-iframe-ready') {
                if (window.PropertyDetailModalManager && window.PropertyDetailModalManager.onContentSettled) {
                    window.PropertyDetailModalManager.onContentSettled();
                }
            }
        });
    }

    function bindListingClicks() {
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
                return;
            }

            var link = e.target.closest('a.js-property-modal-link, .serik-prop-card a[href*="/properties/"], .property-item.list-style-1 a[href*="/properties/"]');
            if (!link) return;
            if (link.classList.contains('js-auth-open-login')) return;
            if (link.getAttribute('data-bs-toggle') === 'modal') return;

            var href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
            if (href.indexOf('/properties/') === -1 && href.indexOf('/property/') === -1) return;

            e.preventDefault();
            e.stopPropagation();
            window.openPropertyDetailUrl(toIframeUrl(href));
        }, true);

        // Prefetch modal HTML on hover so open feels instant.
        var prefetchTimer = null;
        var prefetched = Object.create(null);
        document.addEventListener('pointerover', function (e) {
            var link = e.target.closest('a.js-property-modal-link, .serik-prop-card a[href*="/properties/"], .property-item.list-style-1 a[href*="/properties/"]');
            if (!link) return;
            var href = link.getAttribute('href') || '';
            if (!href || href.indexOf('/properties/') === -1) return;
            var url = toIframeUrl(href);
            if (prefetched[url]) return;
            clearTimeout(prefetchTimer);
            prefetchTimer = setTimeout(function () {
                if (prefetched[url]) return;
                prefetched[url] = true;
                if (window.fetch) {
                    fetch(url, { credentials: 'same-origin', headers: { 'Purpose': 'prefetch', 'X-Requested-With': 'XMLHttpRequest' } }).catch(function () {});
                }
                var tip = document.createElement('link');
                tip.rel = 'prefetch';
                tip.href = url;
                tip.as = 'document';
                document.head.appendChild(tip);
            }, 120);
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initLifecycle();
            bindListingClicks();
        });
    } else {
        initLifecycle();
        bindListingClicks();
    }
})();
</script>
@endonce
