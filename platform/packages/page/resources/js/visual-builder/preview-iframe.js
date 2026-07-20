const PreviewIframe = {
    $iframe: null,
    $container: null,
    $loading: null,
    $error: null,
    $preview: null,
    iframe: null,
    isReady: false,
    deviceMode: 'desktop',

    init() {
        this.$iframe = $('#vb-preview-iframe')
        this.$container = $('#vb-preview-frame-container')
        this.$loading = $('#vb-preview-loading')
        this.$error = $('#vb-preview-error')
        this.$preview = $('#vb-preview')
        this.iframe = this.$iframe[0]

        this.$iframe.on('load', () => {
            this.handleLoad()
        })

        this.$iframe.on('error', () => {
            this.showError('Failed to load preview')
        })

        if (typeof PostMessageUtil !== 'undefined') {
            PostMessageUtil.listenToIframe((type, payload) => {
                this.handleMessage(type, payload)
            })
        }

        this.setDeviceMode('desktop')
    },

    handleLoad() {
        this.isReady = true
        this.hideLoading()
        this.$preview.addClass('loaded')

        setTimeout(() => {
            this.injectEditIcons()
        }, 500)
    },

    handleMessage(type, payload) {
        switch (type) {
            case 'ready':
                this.handleLoad()
                break

            case 'edit-shortcode':
                if (payload && payload.id) {
                    this.handleEditRequest(payload.id)
                }
                break

            case 'delete-shortcode':
                if (payload && payload.id) {
                    this.handleDeleteRequest(payload.id)
                }
                break
        }
    },

    handleEditRequest(shortcodeId) {
        if (typeof ShortcodeList !== 'undefined') {
            ShortcodeList.openEditPanel(shortcodeId)
        }
    },

    handleDeleteRequest(shortcodeId) {
        if (typeof ShortcodeList !== 'undefined') {
            ShortcodeList.deleteShortcode(shortcodeId)
        }
    },

    injectEditIcons() {
        if (!this.iframe || !this.iframe.contentDocument) {
            return
        }

        try {
            const iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document

            if (typeof EditIconInjector !== 'undefined') {
                EditIconInjector.inject(iframeDoc)
            }

            this.injectEditIconStyles(iframeDoc)
        } catch (e) {}
    },

    injectEditIconStyles(iframeDoc) {
        if (iframeDoc.getElementById('vb-edit-icon-styles')) {
            return
        }

        const style = iframeDoc.createElement('style')
        style.id = 'vb-edit-icon-styles'
        style.textContent = `
            /* Shortcode block wrapper */
            .vb-shortcode-block {
                position: relative !important;
                min-height: 20px;
                transition: all 0.2s ease;
            }

            /* Toolbar */
            .vb-shortcode-toolbar {
                position: absolute;
                top: 10px;
                right: 10px;
                z-index: 9999;
                display: flex;
                gap: 4px;
                opacity: 0;
                transition: all 0.2s ease;
                pointer-events: none;
            }

            /* Show toolbar on hover or when active */
            .vb-shortcode-block:hover .vb-shortcode-toolbar,
            .vb-shortcode-block.vb-active .vb-shortcode-toolbar {
                opacity: 1;
                pointer-events: all;
            }

            /* Toolbar buttons */
            .vb-toolbar-btn {
                width: 32px;
                height: 32px;
                border: none;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                font-size: 16px;
            }

            .vb-toolbar-btn i {
                color: #fff;
                font-size: 16px;
                line-height: 1;
            }

            /* Edit button */
            .vb-toolbar-btn.vb-edit-btn {
                background: #206bc4;
            }

            .vb-toolbar-btn.vb-edit-btn:hover {
                background: #1a5ba7;
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(32, 107, 196, 0.3);
            }

            /* Delete button */
            .vb-toolbar-btn.vb-delete-btn {
                background: #d63939;
            }

            .vb-toolbar-btn.vb-delete-btn:hover {
                background: #c72e2e;
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(214, 57, 57, 0.3);
            }

            /* Hover effect on shortcode block */
            .vb-shortcode-block:hover {
                outline: 2px dashed #206bc4;
                outline-offset: 2px;
            }

            /* Active/selected state */
            .vb-shortcode-block.vb-active {
                outline: 2px solid #206bc4 !important;
                outline-offset: 2px;
                background: rgba(32, 107, 196, 0.05);
            }

            /* Prevent toolbar from affecting layout */
            .vb-shortcode-toolbar * {
                box-sizing: border-box;
            }
        `

        iframeDoc.head.appendChild(style)
    },

    reload() {
        this.isReady = false
        this.showLoading()
        this.$preview.removeClass('loaded error')

        const shortcodes = VisualBuilderState.getAllShortcodes()
        const config = window.visualBuilderData || {}

        const previewUrl = config.previewUrl + (config.previewUrl.includes('?') ? '&' : '?') + 'visual_builder=1'

        const $form = $('<form>', {
            method: 'POST',
            action: previewUrl,
            target: 'vb-preview-iframe',
            style: 'display: none;',
        })

        $form.append(
            $('<input>', {
                type: 'hidden',
                name: '_token',
                value: config.csrfToken,
            })
        )

        $form.append(
            $('<input>', {
                type: 'hidden',
                name: 'shortcodes',
                value: JSON.stringify(shortcodes),
            })
        )

        $form.appendTo('body').submit().remove()
    },

    setDeviceMode(mode) {
        this.deviceMode = mode

        this.$container.removeClass('device-desktop device-tablet device-mobile')
        this.$container.addClass('device-' + mode)
    },

    highlightShortcode(shortcodeId) {
        if (!this.iframe || !this.iframe.contentDocument) {
            return
        }

        try {
            const iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document

            if (typeof EditIconInjector !== 'undefined') {
                EditIconInjector.highlight(iframeDoc, shortcodeId)
            }
        } catch (e) {}
    },

    unhighlightShortcode() {
        if (!this.iframe || !this.iframe.contentDocument) {
            return
        }

        try {
            const iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document

            if (typeof EditIconInjector !== 'undefined') {
                EditIconInjector.unhighlight(iframeDoc)
            }
        } catch (e) {}
    },

    updateShortcode(shortcodeId, data) {
        this.reload()
    },

    showLoading() {
        this.$loading.show()
        this.$error.hide()
        this.$container.hide()
    },

    hideLoading() {
        this.$loading.hide()
        this.$container.show()
    },

    showError(message) {
        this.isReady = false
        this.$loading.hide()
        this.$container.hide()
        this.$error.show()
        this.$preview.addClass('error')

        if (message) {
            $('#vb-error-message').text(message)
        }
    },
}

window.PreviewIframe = PreviewIframe
