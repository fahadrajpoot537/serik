const ShortcodeEditPanel = {
    $container: null,
    $content: null,
    $title: null,
    currentShortcode: null,
    updateTimeout: null,
    currentRequest: null,

    init() {
        this.$container = $('#vb-edit-panel')
        this.$content = $('#vb-panel-content')
        this.$title = $('#vb-panel-title')
    },

    render(shortcodeId) {
        const shortcode = VisualBuilderState.getShortcode(shortcodeId)

        if (!shortcode) {
            return
        }

        this.currentShortcode = shortcode

        this.$title.text(`Edit: ${shortcode.name}`)

        this.loadShortcodeForm(shortcode)
    },

    loadShortcodeForm(shortcode) {
        // Abort any pending request
        if (this.currentRequest) {
            this.currentRequest.abort()
            this.currentRequest = null
        }

        // Re-initialize selectors to ensure they're fresh
        if (!this.$content || this.$content.length === 0) {
            this.$content = $('#vb-panel-content')
        }

        if (this.$content.length === 0) {
            return
        }

        const config = window.visualBuilderData || {}
        const available = config.availableShortcodes || []
        const schema = available.find((sc) => sc.key === shortcode.name)

        if (!schema || !schema.url) {
            this.$content.html('<p class="text-danger">Shortcode configuration not found.</p>')
            return
        }

        const url = schema.url

        if (typeof Botble !== 'undefined' && Botble.showLoading) {
            Botble.showLoading(this.$content)
        }

        const formData = {}
        if (shortcode.attributes) {
            Object.assign(formData, shortcode.attributes)
        }
        if (shortcode.content) {
            formData.content = shortcode.content
        }

        this.currentRequest = $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: config.csrfToken,
                ...formData,
            },
            timeout: 30000,
            success: (response) => {
                this.currentRequest = null
                if (typeof Botble !== 'undefined') {
                    Botble.hideLoading(this.$content)
                }

                // Ensure $content still exists
                if (!this.$content || this.$content.length === 0) {
                    this.$content = $('#vb-panel-content')
                }

                if (response && response.data) {
                    this.$content.html('<form id="vb-edit-form">' + response.data + '</form>')

                    if (typeof Botble !== 'undefined') {
                        Botble.initResources()
                        Botble.initMediaIntegrate()
                        Botble.initFieldCollapse()
                    }

                    this.attachEvents()
                } else {
                    this.$content.html('<p class="text-danger">Invalid response format.</p>')
                }
            },
            error: (xhr, status, error) => {
                this.currentRequest = null

                // Don't show error for aborted requests
                if (status === 'abort') {
                    return
                }

                if (typeof Botble !== 'undefined') {
                    Botble.hideLoading(this.$content)
                }

                // Ensure $content still exists
                if (!this.$content || this.$content.length === 0) {
                    this.$content = $('#vb-panel-content')
                }

                let errorMessage = 'Failed to load shortcode form.'
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message
                } else if (xhr.responseText) {
                    errorMessage += ' ' + xhr.status + ': ' + error
                } else if (status === 'timeout') {
                    errorMessage = 'Request timeout. Please try again.'
                }

                this.$content.html('<p class="text-danger">' + errorMessage + '</p>')
            },
        })
    },

    attachEvents() {
        const self = this

        this.$content.off('input change').on('input change', 'input, textarea, select', function () {
            clearTimeout(self.updateTimeout)
            self.updateTimeout = setTimeout(() => {
                self.handleFieldChange()
            }, 300)
        })
    },

    handleFieldChange() {
        if (!this.currentShortcode) return

        const formData = this.getFormData()

        VisualBuilderState.updateShortcode(this.currentShortcode.id, {
            attributes: formData.attributes,
            content: formData.content,
        })

        if (typeof PreviewIframe !== 'undefined') {
            PreviewIframe.updateShortcode(this.currentShortcode.id, formData)
        }
    },

    getFormData() {
        const $form = $('#vb-edit-form')
        const attributes = {}
        let content = ''

        $form.find('input, textarea, select').each(function () {
            const $field = $(this)
            const name = $field.attr('name')
            const shortcodeAttribute = $field.data('shortcode-attribute')

            if (shortcodeAttribute === 'content') {
                content = $field.val()
            } else if (name) {
                let value

                if ($field.attr('type') === 'checkbox') {
                    value = $field.is(':checked') ? 'yes' : 'no'
                } else {
                    value = $field.val()
                }

                if (value) {
                    const cleanName = name.replace('[]', '')
                    attributes[cleanName] = value
                }
            }
        })

        return { attributes, content }
    },
}

window.ShortcodeEditPanel = ShortcodeEditPanel
