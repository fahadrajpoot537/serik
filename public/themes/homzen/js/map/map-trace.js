/**
 * Opt-in map runtime tracer. Enable: ?hs_map_trace=1 or localStorage.hs_map_trace=1
 * Dump: window.HsMapTrace.dump()
 */
(function (global) {
    'use strict';

    const enabled = (() => {
        try {
            if (/[?&]hs_map_trace=1/.test(global.location?.search || '')) {
                return true;
            }
            return global.localStorage?.getItem('hs_map_trace') === '1';
        } catch (e) {
            return false;
        }
    })();

    if (!enabled) {
        global.HsMapTrace = { enabled: false };
        return;
    }

    const t0 = performance.now();
    const log = [];

    function entry(type, detail) {
        const row = {
            ms: Math.round(performance.now() - t0),
            ts: new Date().toISOString(),
            type,
            detail: detail || {},
            stack: new Error().stack?.split('\n').slice(2, 10).map((s) => s.trim()) || [],
        };
        log.push(row);
        console.log('[HsMapTrace]', type, detail || '');
        return row;
    }

    function wrap(obj, method, type) {
        if (!obj || typeof obj[method] !== 'function' || obj[method].__hsTraced) {
            return;
        }
        const orig = obj[method].bind(obj);
        obj[method] = function (...args) {
            entry(type, { args: args.length === 1 ? args[0] : args });
            return orig(...args);
        };
        obj[method].__hsTraced = true;
    }

    function install() {
        wrap(global.HsMapFetchCoordinator, 'scheduleLoad', 'scheduleLoad');
        wrap(global.HsMapFetchCoordinator, 'executeLoad', 'executeLoad');
        wrap(global.HsMapFetchCoordinator, 'abortInFlight', 'abortInFlight');
        wrap(global.HsMapMarkerManager, 'applyFeatures', 'applyFeatures');
        wrap(global.HsMapMarkerManager, 'clearMarkers', 'clearMarkers');
        wrap(global.HsMapInteractionState, 'beginFetch', 'beginFetch');
        wrap(global.HsMapInteractionState, 'openClusterPanel', 'openClusterPanel');
        wrap(global.HsMapInteractionState, 'closePanel', 'closePanel');

        if (typeof global.loadProperties === 'function' && !global.loadProperties.__hsTraced) {
            const origLp = global.loadProperties;
            global.loadProperties = function (opts) {
                entry('loadProperties', { options: opts || {} });
                return origLp(opts);
            };
            global.loadProperties.__hsTraced = true;
        }

        if (typeof global.closeHsMapCenterPanel === 'function' && !global.closeHsMapCenterPanel.__hsTraced) {
            const origClose = global.closeHsMapCenterPanel;
            global.closeHsMapCenterPanel = function () {
                entry('closeHsMapCenterPanel', {});
                return origClose();
            };
            global.closeHsMapCenterPanel.__hsTraced = true;
        }

        const map = global.hsMap;
        if (map && !map.__hsTraceBound) {
            map.__hsTraceBound = true;
            ['moveend', 'idle', 'render', 'sourcedata', 'zoomend', 'dragend'].forEach((ev) => {
                map.on(ev, (e) => {
                    const d = { event: ev };
                    if (ev === 'sourcedata' && e?.sourceId) {
                        d.sourceId = e.sourceId;
                    }
                    entry('map.' + ev, d);
                });
            });
            if (typeof map.resize === 'function' && !map.resize.__hsTraced) {
                const origResize = map.resize.bind(map);
                map.resize = function () {
                    entry('map.resize', {});
                    return origResize();
                };
                map.resize.__hsTraced = true;
            }
        }

        const origFetch = global.fetch.bind(global);
        if (!origFetch.__hsTraced) {
            global.fetch = function (input, init) {
                const u = typeof input === 'string' ? input : input?.url || '';
                if (u.includes('/api/v1/map-properties')) {
                    const id = Math.random().toString(36).slice(2, 8);
                    entry('fetch.map-properties.start', { id, url: u });
                    return origFetch(input, init).then((res) => {
                        entry('fetch.map-properties.end', { id, status: res.status });
                        return res;
                    });
                }
                return origFetch(input, init);
            };
            global.fetch.__hsTraced = true;
        }
    }

    const timer = setInterval(() => {
        install();
        if (global.hsMap && global.HsMapFetchCoordinator && global.HsMapMarkerManager) {
            clearInterval(timer);
            entry('trace.ready', {});
        }
    }, 50);

    global.HsMapTrace = {
        enabled: true,
        log,
        entry,
        dump() {
            return JSON.stringify(log, null, 2);
        },
        download(filename) {
            const blob = new Blob([this.dump()], { type: 'application/json' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename || 'hs-map-trace.json';
            a.click();
        },
    };
})(typeof window !== 'undefined' ? window : this);
