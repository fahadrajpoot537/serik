/**
 * Centralizes map property API requests — dedup, abort, generation tokens.
 */
(function (global) {
    'use strict';

    let controller = null;
    let debounceTimer = null;
    let isLoading = false;
    let lastFetchKey = '';
    let lastFetchCenter = null;
    let lastFetchZoom = null;
    let pendingInitFetch = false;
    let initFetchScheduled = false;

    function abortInFlight() {
        if (controller) {
            try {
                controller.abort();
            } catch (e) {
                /* ignore */
            }
            controller = null;
        }
        isLoading = false;
    }

    function clearDebounce() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
    }

    function movedEnoughToRefetch(map) {
        if (!map || !lastFetchCenter) {
            return true;
        }
        const zoom = Math.round(map.getZoom());
        if (lastFetchZoom !== null && zoom !== lastFetchZoom) {
            return true;
        }
        const bounds = map.getBounds();
        const center = map.getCenter();
        const latSpan = Math.abs(bounds.getNorth() - bounds.getSouth());
        const lngSpan = Math.abs(bounds.getEast() - bounds.getWest());
        const movedLat = Math.abs(center.lat - lastFetchCenter.lat);
        const movedLng = Math.abs(center.lng - lastFetchCenter.lng);
        return movedLat > latSpan * 0.2 || movedLng > lngSpan * 0.2;
    }

    function rememberFetchMeta(map) {
        if (!map) {
            return;
        }
        lastFetchCenter = map.getCenter();
        lastFetchZoom = Math.round(map.getZoom());
    }

    function bustCache() {
        lastFetchKey = '';
    }

    function scheduleLoad(buildRequest, options, delayMs) {
        const state = global.HsMapInteractionState;
        if (state && !state.canFetchMarkers() && !options.force) {
            return;
        }

        clearDebounce();
        debounceTimer = setTimeout(() => {
            debounceTimer = null;
            executeLoad(buildRequest, options);
        }, delayMs == null ? 200 : delayMs);
    }

    function executeLoad(buildRequest, options) {
        const state = global.HsMapInteractionState;
        const map = global.hsMap;

        if (!map || typeof buildRequest !== 'function') {
            return;
        }
        if (state && !state.canFetchMarkers() && !options.force) {
            return;
        }

        const built = buildRequest(map);
        if (!built || built.skip) {
            return;
        }

        const fetchKey = built.key;
        if (fetchKey && fetchKey === lastFetchKey && !options.force) {
            return;
        }

        abortInFlight();

        const generation = state ? state.beginFetch() : 0;
        controller = new AbortController();
        isLoading = true;

        fetch(built.url, {
            signal: controller.signal,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => {
                if (!res.ok) {
                    throw new Error('Network error');
                }
                return res.json();
            })
            .then((data) => {
                if (!global.HsMapMarkerManager) {
                    return;
                }
                if (state && !state.canApplyMarkerData(generation)) {
                    return;
                }
                if (!data || data.type !== 'FeatureCollection' || !Array.isArray(data.features)) {
                    console.error('Invalid map properties response', data);
                    global.HsMapMarkerManager.clearMarkers(generation);
                    lastFetchKey = '';
                    return;
                }

                let features = data.features;
                if (typeof built.postProcess === 'function') {
                    features = built.postProcess(features);
                }

                if (global.HsMapMarkerManager.applyFeatures(features, generation)) {
                    lastFetchKey = fetchKey;
                    rememberFetchMeta(map);
                }
            })
            .catch((err) => {
                if (err.name !== 'AbortError') {
                    console.error('Map properties API error:', err);
                    lastFetchKey = '';
                }
            })
            .finally(() => {
                isLoading = false;
                controller = null;
            });
    }

    function scheduleInitialFetch(buildRequest) {
        if (initFetchScheduled) {
            return;
        }
        initFetchScheduled = true;
        pendingInitFetch = true;

        const map = global.hsMap;
        if (!map) {
            return;
        }

        const runOnce = () => {
            if (!pendingInitFetch) {
                return;
            }
            pendingInitFetch = false;
            executeLoad(buildRequest, { fromInit: true });
        };

        if (map.loaded && map.loaded()) {
            runOnce();
            return;
        }

        map.once('idle', runOnce);
    }

    function cancelInitialFetch() {
        pendingInitFetch = false;
        initFetchScheduled = false;
    }

    function onMapMoveEnd(buildRequest) {
        const state = global.HsMapInteractionState;
        if (state && state.isPanelOpen()) {
            return;
        }
        if (global.autoCenteringMap) {
            return;
        }
        const map = global.hsMap;
        if (!map || !movedEnoughToRefetch(map)) {
            return;
        }
        scheduleLoad(buildRequest, { fromMapMove: true }, 500);
    }

    global.HsMapFetchCoordinator = {
        scheduleLoad,
        executeLoad,
        scheduleInitialFetch,
        cancelInitialFetch,
        onMapMoveEnd,
        abortInFlight,
        clearDebounce,
        bustCache,
        movedEnoughToRefetch,
        isLoading: () => isLoading,
        getLastFetchKey: () => lastFetchKey,
    };
})(typeof window !== 'undefined' ? window : this);
