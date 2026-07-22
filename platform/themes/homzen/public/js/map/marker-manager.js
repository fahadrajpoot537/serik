/**
 * Guards GeoJSON source updates — markers only change when explicitly allowed.
 */
(function (global) {
    'use strict';

    let lastFeatures = [];
    let lastAppliedGeneration = 0;

    function getMap() {
        return global.hsMap || null;
    }

    function getSource() {
        const map = getMap();
        if (!map || !map.getSource) {
            return null;
        }
        try {
            return map.getSource('properties');
        } catch (e) {
            return null;
        }
    }

    function applyFeatures(features, generation) {
        const state = global.HsMapInteractionState;
        if (state && !state.canApplyMarkerData(generation)) {
            return false;
        }

        const source = getSource();
        if (!source) {
            return false;
        }

        const safeFeatures = Array.isArray(features) ? features : [];
        source.setData({
            type: 'FeatureCollection',
            features: safeFeatures,
        });

        lastFeatures = safeFeatures;
        lastAppliedGeneration = generation;
        global.lastMapFeatures = safeFeatures;

        const countEl = document.getElementById('map-property-count');
        if (countEl) {
            countEl.innerText = 'Available Properties : ' + safeFeatures.length;
        }

        if (typeof global.renderMapListCards === 'function') {
            global.renderMapListCards(safeFeatures);
        }

        return true;
    }

    function getLastFeatures() {
        return lastFeatures;
    }

    function clearMarkers(generation) {
        return applyFeatures([], generation);
    }

    global.HsMapMarkerManager = {
        applyFeatures,
        getLastFeatures,
        clearMarkers,
        getLastAppliedGeneration: () => lastAppliedGeneration,
    };
})(typeof window !== 'undefined' ? window : this);
