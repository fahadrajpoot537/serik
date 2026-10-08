<div
    class="modal fade newsletter-popup"
    id="newsletter-popup"
    tabindex="-1"
    aria-hidden="true"
    data-delay="{{ theme_option('newsletter_popup_delay', 5) }}"
    title="{{ theme_option('newsletter_popup_title') }}"
>
    @include('plugins/newsletter::partials.popup')
</div>

<script>
(function () {
    'use strict';

    // Prevent newsletter.js from scheduling a second open (was causing popup flash/reopen).
    window.__serikNewsletterPopupInline = true;

    var popupId = 'newsletter-popup';
    var cookieName = 'newsletter_popup';

    function hasDismissCookie() {
        return document.cookie.split(';').some(function (c) {
            return c.trim().indexOf(cookieName + '=') === 0;
        });
    }

    function setDismissCookie(days) {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = cookieName + '=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax' + secure;
    }

    function showPopup() {
        var popup = document.getElementById(popupId);
        if (!popup || hasDismissCookie() || !popup.querySelector('.newsletter-popup-content')) {
            return;
        }

        popup.classList.add('show');
        popup.style.display = 'block';
        popup.style.zIndex = '99999999';
        popup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open', 'newsletter-popup-open');
        // Avoid Bootstrap scrollbar compensation shifting the whole page (CLS).
        document.body.style.paddingRight = '0';
        document.body.style.overflow = 'hidden';

        if (!document.querySelector('.newsletter-popup-backdrop')) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show newsletter-popup-backdrop';
            backdrop.style.zIndex = '99999998';
            document.body.appendChild(backdrop);
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            try {
                var existing = window.bootstrap.Modal.getInstance(popup);
                if (existing) {
                    existing.dispose();
                }
                window.bootstrap.Modal.getOrCreateInstance(popup, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                }).show();
                document.body.style.paddingRight = '0';
            } catch (e) {}
        }
    }

    function hidePopup() {
        var popup = document.getElementById(popupId);
        if (!popup) {
            return;
        }

        var dontShow = popup.querySelector('input[name="dont_show_again"]');
        if (dontShow && dontShow.checked) {
            setDismissCookie(30);
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            var instance = window.bootstrap.Modal.getInstance(popup);
            if (instance) {
                instance.hide();
            }
        }

        popup.classList.remove('show');
        popup.style.display = 'none';
        popup.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open', 'newsletter-popup-open');
        document.querySelectorAll('.newsletter-popup-backdrop, .modal-backdrop').forEach(function (el) {
            el.remove();
        });
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    function schedulePopup() {
        if (hasDismissCookie()) {
            return;
        }

        var popup = document.getElementById(popupId);
        if (!popup) {
            return;
        }

        var delay = parseInt(popup.getAttribute('data-delay'), 10);
        if (!delay || delay < 0) {
            delay = 5;
        }
        // Never open during the first paint/LCP window (mobile PSI).
        var ms = Math.max(delay * 1000, 8000);

        var run = function () {
            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(showPopup, { timeout: ms + 2000 });
            } else {
                setTimeout(showPopup, ms);
            }
        };
        setTimeout(run, ms);
    }

    function bindEvents() {
        document.addEventListener('click', function (e) {
            if (
                e.target.closest('#' + popupId + ' .js-newsletter-popup-close') ||
                e.target.closest('#' + popupId + ' [data-bs-dismiss="modal"]')
            ) {
                e.preventDefault();
                hidePopup();
            }
        });

        var popup = document.getElementById(popupId);
        if (popup) {
            popup.addEventListener('hide.bs.modal', function () {
                var dontShow = popup.querySelector('input[name="dont_show_again"]');
                if (dontShow && dontShow.checked) {
                    setDismissCookie(30);
                }
            });
        }
    }

    function init() {
        bindEvents();
        if (document.readyState === 'complete') {
            schedulePopup();
        } else {
            window.addEventListener('load', schedulePopup, { once: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

    window.__showNewsletterPopup = showPopup;
    window.__hideNewsletterPopup = hidePopup;
})();
</script>
