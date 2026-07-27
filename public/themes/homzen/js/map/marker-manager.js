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
        const seenIds = new Set();
        const dedupedFeatures = enrichedFeatures.filter((feature) => {
            const idKey = String(feature?.properties?.id ?? feature?.properties?.property_id ?? '').trim();
            if (idKey === '') {
                return true;
            }
            if (seenIds.has(idKey)) {
                return false;
            }
            seenIds.add(idKey);
            return true;
        });
        source.setData({
            type: 'FeatureCollection',
            features: dedupedFeatures,
        });

        lastFeatures = dedupedFeatures;
        lastAppliedGeneration = generation;
        global.lastMapFeatures = dedupedFeatures;

        const countEl = document.getElementById('map-property-count');
        if (countEl) {
            countEl.innerText = 'Available Properties : ' + dedupedFeatures.length;
        }

        if (typeof global.renderMapListCards === 'function' && !state?.isClusterPanelOpen?.()) {
            global.renderMapListCards(dedupedFeatures);
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
