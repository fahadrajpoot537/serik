/**
 * Add Shortcode Modal Module for Visual Builder
 * Mimics the behavior of the shortcode package's modal system
 */

const AddShortcodeModal = {
    $listModal: null,
    $formModal: null,
    listModalInstance: null,
    formModalInstance: null,
    selectedShortcode: null,

    /**
     * Initialize the module
     */
    init() {
        this.$listModal = $('#vb-shortcode-list-modal')
        this.$formModal = $('#vb-shortcode-modal')

        // Initialize Bootstrap modals
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            this.listModalInstance = new bootstrap.Modal(this.$listModal[0])
            this.formModalInstance = new bootstrap.Modal(this.$formModal[0])
        }

        this.attachEvents()
    },

    /**
     * Attach event handlers
     */
    attachEvents() {
        const self = this

        // Radio button change - enable use button
        $(document).on('change', '[data-bb-toggle="shortcode-item-radio"]', function () {
            $('[data-bb-toggle="vb-shortcode-use"]').prop('disabled', false).removeClass('disabled')
        })

        // Double click on shortcode card
        $(document).on('dblclick', '[data-bb-toggle="vb-shortcode-select"]', function (event) {
            const $currentTarget = $(event.currentTarget)
            self.triggerShortcode($currentTarget)
        })

        // Use button in footer
        $('[data-bb-toggle="vb-shortcode-use"]').on('click', function () {
            const $shortcodeSelected = self.$listModal
                .find('.shortcode-item-input:checked')
                .closest('.shortcode-item-wrapper')

            self.triggerShortcode($shortcodeSelected)

            // Reset selection
            $('[data-bb-toggle="shortcode-item-radio"]').prop('checked', false)
            $('[data-bb-toggle="vb-shortcode-use"]').prop('disabled', true).addClass('disabled')
        })

        // Use button on each card
        $(document).on('click', '[data-bb-toggle="vb-shortcode-button-use"]', function (event) {
            event.stopPropagation()
            const $shortcodeSelected = $(event.currentTarget).closest('.shortcode-item-wrapper')
            self.triggerShortcode($shortcodeSelected)
        })

        // Add shortcode button (insert into visual builder)
        $('[data-bb-toggle="vb-shortcode-add-single"]').on('click', function (event) {
            event.preventDefault()
            self.insertShortcode()
        })

        // Hide list modal when form modal opens
        this.$formModal.on('show.bs.modal', function () {
            if (self.listModalInstance) {
                self.listModalInstance.hide()
            }
            $('[data-bb-toggle="shortcode-item-radio"]').prop('checked', false)
            $('[data-bb-toggle="vb-shortcode-use"]').prop('disabled', true).addClass('disabled')
        })

        // Search functionality
        $('[data-bb-toggle="shortcode-clear-search"]').on('click', function () {
            const $searchInput = self.$listModal.find('input[name="search"]')
            $searchInput.val('')
            self.filterShortcodes('')
        })

        self.$listModal.find('input[name="search"]').on('input', function () {
            self.filterShortcodes($(this).val())
        })
    },

    /**
     * Show the list modal
     */
    show() {
        if (this.listModalInstance) {
            this.listModalInstance.show()
        } else {
            this.$listModal.modal('show')
        }
    },

    /**
     * Trigger shortcode selection
     */
    triggerShortcode($element) {
        this.loadShortcodeConfig({
            href: $element.data('url'),
            key: $element.data('key'),
            name: $element.data('name'),
            description: $element.data('description'),
        })
    },

    /**
     * Load shortcode configuration form
     */
    loadShortcodeConfig(params = {}) {
        const { href, key, name, description = null, data = {}, update = false } = params

        this.selectedShortcode = { key, name, description }

        // Clear previous config
        $('.shortcode-admin-config').html('')

        // Update button text
        const $addButton = $('.shortcode-modal button[data-bb-toggle="vb-shortcode-add-single"]')
        $addButton.text($addButton.data(update ? 'update-text' : 'add-text'))

        // Update modal title
        $('.shortcode-modal .modal-title').text(name)

        // Show form modal
        if (this.formModalInstance) {
            this.formModalInstance.show()
        } else {
            this.$formModal.modal('show')
        }

        // Show loading
        const $modalContent = this.$formModal.find('.modal-content')
        if (typeof Botble !== 'undefined' && Botble.showLoading) {
            Botble.showLoading($modalContent)
        }

        // Load configuration via AJAX
        $.ajax({
            url: href,
            method: 'POST',
            data: {
                _token: (window.visualBuilderData || {}).csrfToken,
                ...data,
            },
            success: (response) => {
                $('.shortcode-data-form').trigger('reset')
                $('.shortcode-input-key').val(key)
                $('.shortcode-admin-config').html(response.data)

                if (typeof Botble !== 'undefined') {
                    Botble.hideLoading($modalContent)
                    Botble.initResources()
                    Botble.initMediaIntegrate()
                    Botble.initFieldCollapse()
                }
            },
            error: () => {
                if (typeof Botble !== 'undefined') {
                    Botble.hideLoading($modalContent)
                    Botble.showError('Failed to load shortcode configuration')
                }
            },
        })
    },

    /**
     * Insert shortcode into visual builder
     */
    insertShortcode() {
        if (!this.selectedShortcode) return

        const $form = $('.shortcode-modal').find('.shortcode-data-form')
        const formData = $form.serializeObject()
        const attributes = {}
        let content = ''

        // Process form data
        $.each(formData, function (name, value) {
            const $element = $form.find('*[name="' + name + '"]')
            const shortcodeAttribute = $element.data('shortcode-attribute')

            if (shortcodeAttribute === 'content') {
                content = value
            } else if (value) {
                name = name.replace('[]', '')
                attributes[name] = value
            }
        })

        // Create new shortcode object
        const newShortcode = {
            name: this.selectedShortcode.key,
            attributes: attributes,
            content: content || '',
            isSelfClosing: !content,
        }

        // Add to state at the end
        VisualBuilderState.addShortcode(newShortcode)

        // Reload preview
        if (typeof PreviewIframe !== 'undefined') {
            PreviewIframe.reload()
        }

        // Close modal
        if (this.formModalInstance) {
            this.formModalInstance.hide()
        } else {
            this.$formModal.modal('hide')
        }
    },

    /**
     * Filter shortcodes by search term
     */
    filterShortcodes(searchTerm) {
        const term = searchTerm.toLowerCase()
        const $items = this.$listModal.find('.shortcode-item-wrapper')
        let visibleCount = 0

        $items.each(function () {
            const $item = $(this)
            const name = $item.data('name').toLowerCase()
            const description = ($item.data('description') || '').toLowerCase()

            if (name.includes(term) || description.includes(term)) {
                $item.closest('.col-xl-3').show()
                visibleCount++
            } else {
                $item.closest('.col-xl-3').hide()
            }
        })

        // Show/hide empty state
        if (visibleCount === 0) {
            this.$listModal.find('.shortcode-empty').show()
            this.$listModal.find('.shortcode-list').hide()
        } else {
            this.$listModal.find('.shortcode-empty').hide()
            this.$listModal.find('.shortcode-list').show()
        }
    },
}

// Helper function for jQuery form serialization
$.fn.serializeObject = function () {
    const o = {}
    const a = this.serializeArray()

    $.each(a, function () {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]]
            }
            o[this.name].push(this.value || '')
        } else {
            o[this.name] = this.value || ''
        }
    })

    return o
}

// Export to global scope
window.AddShortcodeModal = AddShortcodeModal
