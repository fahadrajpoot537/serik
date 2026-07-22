/**
 * Map interaction state — cluster panel open blocks marker GeoJSON updates.
 */
(function (global) {
    'use strict';

    const STATE = {
        IDLE: 'idle',
        CLUSTER_PANEL: 'cluster_panel',
    };

    let currentState = STATE.IDLE;
    let fetchGeneration = 0;
    let panelToken = 0;

    function isPanelOpen() {
        return currentState === STATE.CLUSTER_PANEL;
    }

    function isClusterPanelOpen() {
        return currentState === STATE.CLUSTER_PANEL;
    }

    function canFetchMarkers() {
        return !isClusterPanelOpen();
    }

    function canApplyMarkerData(generation) {
        if (isClusterPanelOpen()) {
            return false;
        }
        return generation === fetchGeneration;
    }

    function beginFetch() {
        fetchGeneration += 1;
        return fetchGeneration;
    }

    function openClusterPanel() {
        currentState = STATE.CLUSTER_PANEL;
        panelToken += 1;
        fetchGeneration += 1;
        return panelToken;
    }

    function closePanel() {
        currentState = STATE.IDLE;
        panelToken += 1;
    }

    function getPanelToken() {
        return panelToken;
    }

    global.HsMapInteractionState = {
        STATE,
        isPanelOpen,
        isClusterPanelOpen,
        canFetchMarkers,
        canApplyMarkerData,
        beginFetch,
        openClusterPanel,
        closePanel,
        getPanelToken,
    };
})(typeof window !== 'undefined' ? window : this);
