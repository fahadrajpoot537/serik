/**
 * Marker-anchored popup panel — independent from GeoJSON marker updates.
 */
(function (global) {
    'use strict';

    const PADDING = 16;
    let repositionBound = false;
    let fitInProgress = false;

    function getMap() {
        return global.hsMap || null;
    }

    function getStage() {
        return document.querySelector('.hs-map-stage');
    }

    function getPanel() {
        return document.getElementById('hsMapCenterPanel');
    }

    function getDialog() {
        return getPanel()?.querySelector('.hs-map-center-panel-dialog') || null;
    }

    function bindRepositionListeners() {
        if (repositionBound) {
            return;
        }
        const map = getMap();
        if (!map) {
            return;
        }
        repositionBound = true;

        const reposition = () => {
            const state = global.HsMapInteractionState;
            if (!state || !state.isPanelOpen()) {
                return;
            }
            const anchor = state.getActiveAnchor();
            if (anchor?.coordinates) {
                positionPanel(anchor.coordinates, anchor.placement);
            }
        };

        map.on('move', reposition);
        map.on('zoom', reposition);
        map.on('rotate', reposition);
        map.on('pitch', reposition);
    }

    function choosePlacement(point, dialogW, dialogH, stageRect) {
        const spaceTop = point.y - stageRect.top;
        const spaceBottom = stageRect.bottom - point.y;
        const spaceLeft = point.x - stageRect.left;
        const spaceRight = stageRect.right - point.x;

        const fitsBelow = spaceBottom >= dialogH + PADDING;
        const fitsAbove = spaceTop >= dialogH + PADDING;
        const fitsRight = spaceRight >= dialogW + PADDING;
        const fitsLeft = spaceLeft >= dialogW + PADDING;

        if (fitsBelow) {
            return 'bottom';
        }
        if (fitsAbove) {
            return 'top';
        }
        if (fitsRight) {
            return 'right';
        }
        if (fitsLeft) {
            return 'left';
        }

        const maxSpace = Math.max(spaceBottom, spaceTop, spaceRight, spaceLeft);
        if (maxSpace === spaceBottom) return 'bottom';
        if (maxSpace === spaceTop) return 'top';
        if (maxSpace === spaceRight) return 'right';
        return 'left';
    }

    function applyPlacement(panel, dialog, point, placement, stageRect) {
        const dialogW = dialog.offsetWidth || 320;
        const dialogH = dialog.offsetHeight || 280;
        let left = point.x;
        let top = point.y;
        let transform = '';

        panel.dataset.hsAnchorPlacement = placement;

        switch (placement) {
            case 'top':
                left = point.x - dialogW / 2;
                top = point.y - dialogH - PADDING;
                transform = 'translate(0, 0)';
                break;
            case 'bottom':
                left = point.x - dialogW / 2;
                top = point.y + PADDING;
                transform = 'translate(0, 0)';
                break;
            case 'left':
                left = point.x - dialogW - PADDING;
                top = point.y - dialogH / 2;
                transform = 'translate(0, 0)';
                break;
            case 'right':
            default:
                left = point.x + PADDING;
                top = point.y - dialogH / 2;
                transform = 'translate(0, 0)';
                break;
        }

        left = Math.max(stageRect.left + PADDING, Math.min(left, stageRect.right - dialogW - PADDING));
        top = Math.max(stageRect.top + PADDING, Math.min(top, stageRect.bottom - dialogH - PADDING));

        panel.style.left = (left - stageRect.left) + 'px';
        panel.style.top = (top - stageRect.top) + 'px';
        panel.style.transform = transform;
    }

    function ensureFullyVisible(panel, dialog, coordinates) {
        const map = getMap();
        const stage = getStage();
        if (!map || !stage || !dialog || fitInProgress) {
            return;
        }

        const stageRect = stage.getBoundingClientRect();
        const rect = dialog.getBoundingClientRect();

        let dx = 0;
        let dy = 0;

        if (rect.top < stageRect.top + PADDING) {
            dy = rect.top - (stageRect.top + PADDING);
        } else if (rect.bottom > stageRect.bottom - PADDING) {
            dy = rect.bottom - (stageRect.bottom - PADDING);
        }

        if (rect.left < stageRect.left + PADDING) {
            dx = rect.left - (stageRect.left + PADDING);
        } else if (rect.right > stageRect.right - PADDING) {
            dx = rect.right - (stageRect.right - PADDING);
        }

        if (!dx && !dy) {
            return;
        }

        fitInProgress = true;
        global.autoCenteringMap = true;

        map.panBy([-dx, -dy], { duration: 280, essential: true });

        map.once('moveend', () => {
            fitInProgress = false;
            global.autoCenteringMap = false;
            positionPanel(coordinates);
        });
    }

    function positionPanel(coordinates, preferredPlacement) {
        const map = getMap();
        const stage = getStage();
        const panel = getPanel();
        const dialog = getDialog();

        if (!map || !stage || !panel || !dialog || !coordinates) {
            return;
        }

        if (window.hsMapUsesMobileSheet && window.hsMapUsesMobileSheet()) {
            panel.style.left = '';
            panel.style.top = '';
            panel.style.transform = '';
            const state = global.HsMapInteractionState;
            if (state) {
                state.setActiveAnchor({ coordinates: coordinates.slice(), placement: 'mobile-sheet' });
            }
            return;
        }

        const point = map.project(coordinates);
        const stageRect = stage.getBoundingClientRect();
        const dialogW = dialog.offsetWidth || panel.offsetWidth || 320;
        const dialogH = dialog.offsetHeight || 280;
        const placement = preferredPlacement || choosePlacement(point, dialogW, dialogH, stageRect);

        applyPlacement(panel, dialog, point, placement, stageRect);

        const state = global.HsMapInteractionState;
        if (state) {
            state.setActiveAnchor({ coordinates: coordinates.slice(), placement });
        }
    }

    function mountPanelInStage() {
        const panel = getPanel();
        const stage = getStage();
        if (panel && stage && panel.parentElement !== stage) {
            stage.appendChild(panel);
        }
    }

    function open(options) {
        options = options || {};
        const panel = getPanel();
        const body = document.getElementById('hsMapCenterPanelBody');
        if (!panel || !body || !options.html) {
            return null;
        }

        mountPanelInStage();
        bindRepositionListeners();

        const state = global.HsMapInteractionState;
        const coordinates = options.coordinates
            ? options.coordinates.slice()
            : options.feature?.geometry?.coordinates?.slice();

        const token = options.isCluster
            ? state.openClusterPanel({ coordinates, listingKey: null })
            : state.openPropertyPanel({
                coordinates,
                listingKey: options.props?.external_id || null,
            });

        panel.classList.toggle('is-cluster', !!options.isCluster);
        body.innerHTML = options.html;
        panel.setAttribute('aria-hidden', 'false');
        panel.classList.add('is-open');

        if (coordinates) {
            requestAnimationFrame(() => {
                positionPanel(coordinates);
                requestAnimationFrame(() => {
                    positionPanel(coordinates);
                    ensureFullyVisible(panel, getDialog(), coordinates);
                    if (typeof options.onPositioned === 'function') {
                        options.onPositioned();
                    }
                });
            });
        }

        return { token, body, panel };
    }

    function close() {
        const panel = getPanel();
        const body = document.getElementById('hsMapCenterPanelBody');
        if (!panel) {
            return;
        }

        global.HsMapInteractionState?.closePanel();

        panel.classList.remove('is-open', 'is-cluster');
        panel.setAttribute('aria-hidden', 'true');
        panel.style.left = '';
        panel.style.top = '';
        panel.style.transform = '';

        if (body) {
            body.innerHTML = '';
        }

        window._hsLastClusterLeaves = [];
        window._hsLastClusterCoords = null;
        window._hsLastClusterListHtml = '';
    }

    function reposition() {
        const anchor = global.HsMapInteractionState?.getActiveAnchor();
        if (anchor?.coordinates) {
            positionPanel(anchor.coordinates, anchor.placement);
        }
    }

    global.HsMapAnchoredPopup = {
        open,
        close,
        reposition,
        positionPanel,
        mountPanelInStage,
    };
})(typeof window !== 'undefined' ? window : this);
