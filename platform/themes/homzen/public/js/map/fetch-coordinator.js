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
    let pendingBuildRequest = null;
    let pendingOptions = null;
    let inFlightKey = '';

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
        inFlightKey = '';
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
        const bounds = map.getBounds();
        const center = map.getCenter();
        const latSpan = Math.abs(bounds.getNorth() - bounds.getSouth());
        const lngSpan = Math.abs(bounds.getEast() - bounds.getWest());
        // 4% viewport — update sooner while still avoiding tiny jitter refetches.
        const panned = movedLatEnough(center.lat, lastFetchCenter.lat, latSpan)
            || movedLngEnough(center.lng, lastFetchCenter.lng, lngSpan);

        if (panned) {
            return true;
        }

        if (lastFetchZoom !== null && zoom !== lastFetchZoom) {
            return true;
        }

        return false;
    }

    function movedLatEnough(a, b, span) {
        return Math.abs(a - b) > span * 0.04;
    }

    function movedLngEnough(a, b, span) {
        return Math.abs(a - b) > span * 0.04;
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
        inFlightKey = '';
    }

    function scheduleLoad(buildRequest, options, delayMs) {
        const state = global.HsMapInteractionState;
        options = options || {};
        if (state && !state.canFetchMarkers() && !options.force) {
            return;
        }

        if (delayMs == null) {
            if (options.fromMapMove) {
                delayMs = 180;
            } else if (options.fromFilters) {
                delayMs = 100;
            } else {
                delayMs = 100;
            }
        }

        pendingBuildRequest = buildRequest;
        pendingOptions = options;
        clearDebounce();
        debounceTimer = setTimeout(() => {
            debounceTimer = null;
            const nextBuild = pendingBuildRequest;
            const nextOpts = pendingOptions || {};
            pendingBuildRequest = null;
            pendingOptions = null;
            executeLoad(nextBuild, nextOpts);
        }, delayMs);
    }

    function executeLoad(buildRequest, options) {
        const state = global.HsMapInteractionState;
        const map = global.hsMap;
        options = options || {};

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

        const fetchKey = built.key || '';
        if (fetchKey && fetchKey === lastFetchKey && !options.force) {
            return;
        }

        // Same bounds already loading — coalesce instead of aborting (abort storms
        // under rapid pan + slow API leave markers stuck on stale data).
        if (isLoading && fetchKey && fetchKey === inFlightKey && !options.force) {
            return;
        }

        // Prefer coalescing: if a different request is in flight, queue latest
        // and let the current one finish unless force=true.
        if (isLoading && !options.force && options.fromMapMove) {
            pendingBuildRequest = buildRequest;
            pendingOptions = Object.assign({}, options, { force: false });
            clearDebounce();
            debounceTimer = setTimeout(() => {
                debounceTimer = null;
                if (!isLoading && pendingBuildRequest) {
                    const nextBuild = pendingBuildRequest;
                    const nextOpts = pendingOptions || {};
                    pendingBuildRequest = null;
                    pendingOptions = null;
                    executeLoad(nextBuild, nextOpts);
                }
            }, 120);
            return;
        }

        abortInFlight();

        const generation = state ? state.beginFetch() : 0;
        controller = new AbortController();
        isLoading = true;
        inFlightKey = fetchKey;
        document.body.classList.add('hs-map-fetching');

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
                inFlightKey = '';
                document.body.classList.remove('hs-map-fetching');

                // Drain coalesced pan request after the current fetch settles.
                if (pendingBuildRequest) {
                    const nextBuild = pendingBuildRequest;
                    const nextOpts = pendingOptions || { fromMapMove: true };
                    pendingBuildRequest = null;
                    pendingOptions = null;
                    clearDebounce();
                    debounceTimer = setTimeout(() => {
                        debounceTimer = null;
                        executeLoad(nextBuild, nextOpts);
                    }, 40);
                }
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
        // isListingOpen is optional — older interaction-state builds omit it.
        // Calling a missing method threw and blocked every drag refetch on live.
        if (state && typeof state.isListingOpen === 'function' && state.isListingOpen()) {
            return;
        }
        if (state && typeof state.isClusterPanelOpen === 'function' && state.isClusterPanelOpen()) {
            return;
        }
        if (global.autoCenteringMap) {
            return;
        }
        const map = global.hsMap;
        if (!map || !movedEnoughToRefetch(map)) {
            return;
        }
        scheduleLoad(buildRequest, { fromMapMove: true }, 180);
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
