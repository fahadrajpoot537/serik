/**
 * Visual Builder - Main Entry Point
 *
 * This file is the webpack entry point that loads all visual builder modules
 * in the correct dependency order.
 */

// Load utilities first (no dependencies)
import './visual-builder/utils/postmessage.js'
import './visual-builder/utils/inject-edit-icons.js'
import './visual-builder/utils/shortcode-serializer.js'

// Load state management
import './visual-builder/state.js'

// Load UI modules (depend on state)
import './visual-builder/shortcode-list.js'
import './visual-builder/edit-panel.js'
import './visual-builder/add-modal.js'
import './visual-builder/preview-iframe.js'

// Load main app last (coordinates everything)
import './visual-builder/app.js'

// Initialize when DOM is ready
$(document).ready(function () {
    if (window.visualBuilderData) {
        VisualBuilderApp.init()
    }
})
