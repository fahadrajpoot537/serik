/**
 * Map interaction state machine — coordinates popup lifecycle vs marker updates.
 */
(function (global) {
    'use strict';

    const STATE = {
        IDLE: 'idle',
        PROPERTY_PANEL: 'property_panel',
        CLUSTER_PANEL: 'cluster_panel',
    };

    let currentState = STATE.IDLE;
    let fetchGeneration = 0;
    let panelToken = 0;
    let activeAnchor = null;

    function isPanelOpen() {
        return currentState === STATE.PROPERTY_PANEL || currentState === STATE.CLUSTER_PANEL;
    }

    function isClusterPanelOpen() {
        return currentState === STATE.CLUSTER_PANEL;
    }

    function canFetchMarkers() {
        return !isPanelOpen();
    }

    function canApplyMarkerData(generation) {
        if (isPanelOpen()) {
            return false;
        }
        return generation === fetchGeneration;
    }

    function beginFetch() {
        fetchGeneration += 1;
        return fetchGeneration;
    }

    function openPropertyPanel(anchor) {
        currentState = STATE.PROPERTY_PANEL;
        panelToken += 1;
        fetchGeneration += 1;
        activeAnchor = anchor ? { ...anchor } : null;
        return panelToken;
    }

    function openClusterPanel(anchor) {
        currentState = STATE.CLUSTER_PANEL;
        panelToken += 1;
        fetchGeneration += 1;
        activeAnchor = anchor ? { ...anchor } : null;
        return panelToken;
    }

    function closePanel() {
        currentState = STATE.IDLE;
        panelToken += 1;
        activeAnchor = null;
    }

    function getPanelToken() {
        return panelToken;
    }

    function getActiveAnchor() {
        return activeAnchor;
    }

    function setActiveAnchor(anchor) {
        activeAnchor = anchor ? { ...anchor } : null;
    }

    global.HsMapInteractionState = {
        STATE,
        isPanelOpen,
        isClusterPanelOpen,
        canFetchMarkers,
        canApplyMarkerData,
        beginFetch,
        openPropertyPanel,
        openClusterPanel,
        closePanel,
        getPanelToken,
        getActiveAnchor,
        setActiveAnchor,
    };
})(typeof window !== 'undefined' ? window : this);
