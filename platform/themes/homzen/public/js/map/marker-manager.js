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
        const enrichedFeatures = (typeof global.enrichMapFeaturesWithPriceLabels === 'function')
            ? global.enrichMapFeaturesWithPriceLabels(safeFeatures)
            : safeFeatures;
        source.setData({
            type: 'FeatureCollection',
            features: enrichedFeatures,
        });

        lastFeatures = enrichedFeatures;
        lastAppliedGeneration = generation;
        global.lastMapFeatures = enrichedFeatures;

        const countEl = document.getElementById('map-property-count');
        if (countEl) {
            countEl.innerText = 'Available Properties : ' + enrichedFeatures.length;
        }

        if (typeof global.renderMapListCards === 'function' && !state?.isClusterPanelOpen?.()) {
            global.renderMapListCards(enrichedFeatures);
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
