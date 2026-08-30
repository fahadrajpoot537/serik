/**
 * Serik frontend keyboard accessibility.
 * Activates existing click handlers. Does not replace filter/map/search logic.
 */
(function () {
    'use strict';

    if (window.__serikKeyboardA11y) {
        return;
    }
    window.__serikKeyboardA11y = true;

    var CUSTOM_SELECTOR = [
        '.mobile-nav-toggler',
        '.close-btn',
        '.mobile-dropdown .dropdown-toggle',
        '.mega-close',
        '#closeMobileSearch',
        '#clearBtn',
        '#mapClearBtn',
        '.dropdown-item',
        '.transaction-item',
        '.location-item',
        '.listing-item',
        '.hs-m-option',
        '.hs-m-radio-option',
        '.hs-m-chips li',
        '.hs-list-item',
        '.hs-cluster-list-item',
        '.ac-cat-load-more',
        '[role="button"]',
        '[role="menuitem"]'
    ].join(',');

    function isNode(el) {
        return el instanceof Element;
    }

    function isNativeInteractive(el) {
        if (!isNode(el)) {
            return false;
        }
        var tag = el.tagName;
        return tag === 'A' || tag === 'BUTTON' || tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA' || tag === 'SUMMARY';
    }

    function isVisible(el) {
        if (!isNode(el)) {
            return false;
        }
        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return false;
        }
        return el.getClientRects().length > 0;
    }

    function isTypingTarget(el) {
        if (!isNode(el)) {
            return false;
        }
        var tag = el.tagName;
        if (tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable) {
            return true;
        }
        if (tag === 'INPUT') {
            var type = (el.type || 'text').toLowerCase();
            return ['button', 'submit', 'checkbox', 'radio', 'file', 'reset', 'range', 'color', 'hidden'].indexOf(type) === -1;
        }
        return false;
    }

    function isMapSurface(el) {
        return isNode(el) && !!el.closest('.maplibregl-canvas-container, .mapboxgl-canvas-container, canvas.maplibregl-canvas, .maplibregl-map');
    }

    function enhanceElement(el) {
        if (!isNode(el) || el.__serikA11y) {
            return;
        }
        if (isNativeInteractive(el) || el.closest('a[href], button') || el.querySelector('input, select, textarea, button, a[href]')) {
            el.__serikA11y = true;
            return;
        }
        el.__serikA11y = true;
        if (!el.hasAttribute('tabindex')) {
            el.setAttribute('tabindex', el.closest('.dropdown-menu') ? '-1' : '0');
        }
        if (!el.getAttribute('role')) {
            el.setAttribute('role', 'button');
        }
    }

    function enhance(root) {
        var scope = root && root.querySelectorAll ? root : document;
        try {
            scope.querySelectorAll(CUSTOM_SELECTOR).forEach(enhanceElement);
        } catch (err) {
            /* ignore */
        }
    }

    function moveFocus(items, current, delta) {
        if (!items.length) {
            return;
        }
        var index = items.indexOf(current);
        if (index < 0) {
            index = delta > 0 ? -1 : 0;
        }
        var next = items[(index + delta + items.length) % items.length];
        if (next && typeof next.focus === 'function') {
            next.focus();
        }
    }

    function visibleItems(container, selector) {
        if (!container) {
            return [];
        }
        return Array.prototype.filter.call(container.querySelectorAll(selector), isVisible);
    }

    function closeSearchDropdowns() {
        var closed = false;
        ['searchDropdown', 'mapSearchDropdown'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.style.display === 'block') {
                el.style.display = 'none';
                closed = true;
            }
        });
        return closed;
    }

    function closeFilterDropdowns() {
        var closed = false;
        document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
            if (menu.style.display === 'block' || menu.classList.contains('active-mobile')) {
                menu.style.display = 'none';
                menu.classList.remove('active-mobile');
                closed = true;
            }
        });
        var overlay = document.getElementById('mobileOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
        return closed;
    }

    function closeOpenOverlays() {
        var closed = false;
        if (document.querySelector('.hs-m-sheet.open')) {
            if (typeof window.closeMobileSheets === 'function') {
                window.closeMobileSheets();
                closed = true;
            } else if (typeof window.closeMobileSheetsGlobal === 'function') {
                window.closeMobileSheetsGlobal();
                closed = true;
            }
        }
        if (closeSearchDropdowns()) {
            closed = true;
            if (typeof window.serikHeaderSearchSync === 'function') {
                window.serikHeaderSearchSync();
            }
        }
        var panel = document.getElementById('mobileSearchPanel');
        if (panel && panel.classList.contains('active')) {
            panel.classList.remove('active');
            closed = true;
        }
        if (closeFilterDropdowns()) {
            closed = true;
        }
        return closed;
    }

    function handleActivate(e, target) {
        if (e.defaultPrevented || isNativeInteractive(target) || target.closest('a[href], button') || isTypingTarget(target)) {
            return;
        }
        var widget = target.closest(CUSTOM_SELECTOR);
        if (!widget || isNativeInteractive(widget) || widget.closest('a[href], button')) {
            return;
        }
        e.preventDefault();
        widget.click();
    }

    function handleArrows(e, target) {
        if (isMapSurface(target)) {
            return;
        }

        var key = e.key;
        var isArrow = key === 'ArrowDown' || key === 'ArrowUp' || key === 'ArrowLeft' || key === 'ArrowRight';
        var isHomeEnd = key === 'Home' || key === 'End';
        if (!isArrow && !isHomeEnd) {
            return;
        }

        var searchBox = null;
        if (target.id === 'smartInput' || target.id === 'mapSmartInput' || target.closest('#searchDropdown, #mapSearchDropdown')) {
            var headerOpen = document.getElementById('searchDropdown');
            var mapOpen = document.getElementById('mapSearchDropdown');
            if (headerOpen && headerOpen.style.display === 'block') {
                searchBox = headerOpen;
            } else if (mapOpen && mapOpen.style.display === 'block') {
                searchBox = mapOpen;
            } else {
                searchBox = target.closest('#searchDropdown, #mapSearchDropdown');
            }
        }

        if (isTypingTarget(target) && !searchBox) {
            return;
        }

        var hGroup = target.closest('.hs-map-status-bar, .hs-map-txn-bar, #hsMobileViewBar, .filter-group');
        if (hGroup && (key === 'ArrowLeft' || key === 'ArrowRight' || isHomeEnd)) {
            var hItems = visibleItems(hGroup, 'button, a[href], [tabindex]:not([tabindex="-1"])');
            if (!hItems.length) {
                return;
            }
            e.preventDefault();
            if (key === 'Home') {
                hItems[0].focus();
                return;
            }
            if (key === 'End') {
                hItems[hItems.length - 1].focus();
                return;
            }
            moveFocus(hItems, target.closest('button, a, [tabindex]'), key === 'ArrowRight' ? 1 : -1);
            return;
        }

        if (target.closest('.navigation, .main-menu') && (key === 'ArrowLeft' || key === 'ArrowRight')) {
            var navItems = visibleItems(document, '.navigation > li > a, .main-menu > .menu-item > .menu-link');
            if (navItems.length) {
                e.preventDefault();
                moveFocus(navItems, target.closest('a'), key === 'ArrowRight' ? 1 : -1);
            }
            return;
        }

        var mega = target.closest('.mega-dropdown');
        if (mega && (key === 'ArrowDown' || key === 'ArrowUp' || isHomeEnd)) {
            var megaLinks = visibleItems(mega, 'a[href]');
            if (!megaLinks.length) {
                return;
            }
            e.preventDefault();
            if (key === 'Home') {
                megaLinks[0].focus();
                return;
            }
            if (key === 'End') {
                megaLinks[megaLinks.length - 1].focus();
                return;
            }
            moveFocus(megaLinks, target.closest('a'), key === 'ArrowDown' ? 1 : -1);
            return;
        }

        var dropToggle = target.closest('.dropdown-toggle, .hs-split-value');
        var dropMenu = target.closest('.dropdown-menu');
        if ((dropToggle || dropMenu) && (key === 'ArrowDown' || key === 'ArrowUp' || isHomeEnd)) {
            var wrap = target.closest('.dropdown');
            var menu = dropMenu || (wrap && wrap.querySelector('.dropdown-menu'));
            if (!menu) {
                return;
            }
            if (dropToggle && menu.style.display !== 'block') {
                dropToggle.click();
            }
            var dropItems = visibleItems(menu, '.dropdown-item, .transaction-item, .radio-item, label.checkbox-item');
            dropItems.forEach(function (item) {
                if (!isNativeInteractive(item) && !item.hasAttribute('tabindex')) {
                    item.setAttribute('tabindex', '-1');
                }
            });
            if (!dropItems.length) {
                return;
            }
            e.preventDefault();
            if (key === 'Home') {
                dropItems[0].focus();
                return;
            }
            if (key === 'End') {
                dropItems[dropItems.length - 1].focus();
                return;
            }
            var currentDrop = dropMenu ? target.closest('.dropdown-item, .transaction-item, .radio-item, label.checkbox-item') : null;
            if (!currentDrop) {
                dropItems[0].focus();
                return;
            }
            moveFocus(dropItems, currentDrop, key === 'ArrowDown' ? 1 : -1);
            return;
        }

        if (searchBox && (key === 'ArrowDown' || key === 'ArrowUp')) {
            var suggestions = visibleItems(searchBox, '[role="button"], .location-item, .listing-item, .ac-cat-load-more');
            if (!suggestions.length) {
                return;
            }
            e.preventDefault();
            var currentSug = target.closest('[role="button"], .location-item, .listing-item, .ac-cat-load-more');
            if (!currentSug || isTypingTarget(target)) {
                suggestions[key === 'ArrowDown' ? 0 : suggestions.length - 1].focus();
                return;
            }
            moveFocus(suggestions, currentSug, key === 'ArrowDown' ? 1 : -1);
            return;
        }

        var sheet = target.closest('.hs-m-sheet.open');
        if (sheet && (key === 'ArrowDown' || key === 'ArrowUp')) {
            var sheetItems = visibleItems(sheet, '.hs-m-option, .hs-m-radio-option, .hs-m-chips li, button');
            if (!sheetItems.length) {
                return;
            }
            e.preventDefault();
            var currentSheet = target.closest('.hs-m-option, .hs-m-radio-option, .hs-m-chips li, button') || target;
            moveFocus(sheetItems, currentSheet, key === 'ArrowDown' ? 1 : -1);
        }
    }

    document.addEventListener('keydown', function (e) {
        var target = e.target;
        if (!isNode(target)) {
            return;
        }
        if (e.key === 'Escape') {
            if (closeOpenOverlays()) {
                e.preventDefault();
            }
            return;
        }
        if (e.key === 'Enter' || e.key === ' ') {
            handleActivate(e, target);
            return;
        }
        handleArrows(e, target);
    });

    document.addEventListener('click', function (e) {
        if (e.detail !== 0) {
            return;
        }
        var opener = e.target && e.target.closest && e.target.closest('#hsMobPropertyBtn, #hsMobDateBtn, #hsMobFiltersBtn, #hsMobWatchBtn');
        if (!opener) {
            return;
        }
        window.requestAnimationFrame(function () {
            var sheet = document.querySelector('.hs-m-sheet.open');
            var first = sheet && sheet.querySelector('.hs-m-option, .hs-m-radio-option, button, [tabindex="0"]');
            if (first && typeof first.focus === 'function') {
                first.focus();
            }
        });
    });

    function boot() {
        enhance(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    var scheduled = null;
    var observer = new MutationObserver(function () {
        if (scheduled) {
            return;
        }
        scheduled = window.setTimeout(function () {
            scheduled = null;
            enhance(document);
        }, 80);
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
