;(function () {
    'use strict'

    const onReady = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true })
        } else {
            fn()
        }
    }

    const hasDontShowCookie = () =>
        document.cookie.split(';').some((cookie) => cookie.trim().startsWith('newsletter_popup='))
        || localStorage.getItem('newsletter_popup_dismissed') === '1'

    const dontShowAgain = (time) => {
        const date = new Date()
        date.setTime(date.getTime() + time)
        const secure = window.location.protocol === 'https:' ? '; Secure' : ''
        document.cookie = `newsletter_popup=1; expires=${date.toUTCString()}; path=/; SameSite=Lax${secure}`
        localStorage.setItem('newsletter_popup_dismissed', '1')
    }

    const getPopupElement = () => document.getElementById('newsletter-popup')

    const cleanupBackdrops = () => {
        const openModals = document.querySelectorAll('.modal.show')
        const popup = getPopupElement()
        const otherOpenModals = Array.from(openModals).filter((modal) => modal !== popup)

        if (!otherOpenModals.length) {
            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove())
            document.body.classList.remove('modal-open')
            document.body.style.overflow = ''
            document.body.style.paddingRight = ''
        }

        document.body.classList.remove('newsletter-popup-open')
        document.querySelectorAll('.newsletter-popup-backdrop').forEach((el) => el.remove())
    }

    const raisePopupLayers = () => {
        const popup = getPopupElement()
        if (!popup) {
            return
        }

        popup.style.zIndex = '99999999'

        const backdrops = document.querySelectorAll('.modal-backdrop')
        if (backdrops.length) {
            backdrops[backdrops.length - 1].style.zIndex = '99999998'
        }

        document.body.classList.add('newsletter-popup-open')
    }

    const initNewsletterPopup = () => {
        const popup = getPopupElement()

        if (!popup || !popup.querySelector('.newsletter-popup-content')) {
            return
        }

        const delaySeconds = parseInt(popup.getAttribute('data-delay'), 10)
        const newsletterDelayTime = Number.isFinite(delaySeconds) ? delaySeconds * 1000 : 5000
        let popupScheduled = false
        let popupShown = false
        let userDismissed = false

        const hideNewsletterModal = () => {
            userDismissed = true
            popupShown = false
            dontShowAgain(30 * 24 * 60 * 60 * 1000)

            if (window.bootstrap?.Modal) {
                const instance = window.bootstrap.Modal.getInstance(popup)

                if (instance) {
                    instance.hide()
                    return
                }
            }

            popup.classList.remove('show')
            popup.style.display = 'none'
            popup.setAttribute('aria-hidden', 'true')
            cleanupBackdrops()
        }

        const showNewsletterModal = () => {
            if (popupShown || userDismissed || hasDontShowCookie()) {
                return
            }

            if (window.bootstrap?.Modal) {
                const existing = window.bootstrap.Modal.getInstance(popup)
                if (existing) {
                    existing.dispose()
                }

                const instance = window.bootstrap.Modal.getOrCreateInstance(popup, {
                    backdrop: true,
                    keyboard: true,
                })

                popup.addEventListener('show.bs.modal', raisePopupLayers, { once: true })
                popup.addEventListener('shown.bs.modal', raisePopupLayers, { once: true })
                popup.addEventListener(
                    'hide.bs.modal',
                    () => {
                        const checkbox = popup.querySelector('input[name="dont_show_again"]')
                        if (checkbox?.checked) {
                            dontShowAgain(30 * 24 * 60 * 60 * 1000)
                        }
                    },
                    { once: true }
                )
                popup.addEventListener(
                    'hidden.bs.modal',
                    () => {
                        popupShown = false
                        cleanupBackdrops()
                    },
                    { once: true }
                )

                instance.show()
                popupShown = true
                return
            }

            popup.classList.add('show')
            popup.style.display = 'block'
            popup.style.zIndex = '99999999'
            popup.setAttribute('aria-hidden', 'false')
            document.body.classList.add('modal-open', 'newsletter-popup-open')

            if (!document.querySelector('.newsletter-popup-backdrop')) {
                const backdrop = document.createElement('div')
                backdrop.className = 'modal-backdrop fade show newsletter-popup-backdrop'
                backdrop.style.zIndex = '99999998'
                document.body.appendChild(backdrop)
            }

            popupShown = true
        }

        const waitForBootstrap = (callback, attempts = 30) => {
            if (window.bootstrap?.Modal || attempts <= 0) {
                callback()
                return
            }

            setTimeout(() => waitForBootstrap(callback, attempts - 1), 100)
        }

        const schedulePopup = () => {
            if (popupScheduled || hasDontShowCookie() || userDismissed) {
                return
            }

            popupScheduled = true

            const run = () => {
                waitForBootstrap(() => {
                    setTimeout(showNewsletterModal, newsletterDelayTime)
                })
            }

            if (document.readyState === 'complete') {
                run()
            } else {
                window.addEventListener('load', run, { once: true })
            }
        }

        popup.addEventListener('show.bs.modal', raisePopupLayers)
        popup.addEventListener('shown.bs.modal', raisePopupLayers)
        popup.addEventListener('hide.bs.modal', () => {
            const checkbox = popup.querySelector('input[name="dont_show_again"]')
            if (checkbox?.checked) {
                dontShowAgain(30 * 24 * 60 * 60 * 1000)
            }
        })
        popup.addEventListener('hidden.bs.modal', () => {
            popupShown = false
            cleanupBackdrops()
        })

        document.addEventListener('click', (event) => {
            const target = event.target

            if (
                target.closest('#newsletter-popup .js-newsletter-popup-close') ||
                target.closest('#newsletter-popup [data-bs-dismiss="modal"]')
            ) {
                event.preventDefault()
                event.stopPropagation()
                hideNewsletterModal()
            }
        })

        document.addEventListener('newsletter.subscribed', () => dontShowAgain(30 * 24 * 60 * 60 * 1000))

        schedulePopup()

        window.__showNewsletterPopup = showNewsletterModal
        window.__hideNewsletterPopup = hideNewsletterModal
    }

    const initNewsletterForm = () => {
        if (typeof jQuery === 'undefined') {
            return
        }

        const $ = jQuery

        const showError = (message) => {
            $('.newsletter-error-message').html(message).show()
        }

        const showSuccess = (message) => {
            $('.newsletter-success-message').html(message).show()
        }

        const handleValidationError = (errors) => {
            let message = ''
            $.each(errors, (index, item) => {
                if (message !== '') {
                    message += '<br />'
                }
                message += item
            })
            showError(message)
        }

        const handleError = (data) => {
            if (typeof data.errors !== 'undefined' && data.errors.length) {
                handleValidationError(data.errors)
            } else if (typeof data.responseJSON !== 'undefined') {
                if (typeof data.responseJSON.errors !== 'undefined' && data.status === 422) {
                    handleValidationError(data.responseJSON.errors)
                } else if (typeof data.responseJSON.message !== 'undefined') {
                    showError(data.responseJSON.message)
                } else {
                    $.each(data.responseJSON, (index, el) => {
                        $.each(el, (key, item) => showError(item))
                    })
                }
            } else {
                showError(data.statusText)
            }
        }

        $(document).on('submit', 'form.bb-newsletter-popup-form', (e) => {
            e.preventDefault()

            const $form = $(e.currentTarget)
            const $button = $form.find('button[type=submit]')

            $('.newsletter-success-message').html('').hide()
            $('.newsletter-error-message').html('').hide()

            $.ajax({
                type: 'POST',
                cache: false,
                url: $form.prop('action'),
                data: new FormData($form[0]),
                contentType: false,
                processData: false,
                beforeSend: () => $button.prop('disabled', true).addClass('btn-loading'),
                success: ({ error, message }) => {
                    if (error) {
                        showError(message)
                        return
                    }

                    $form.find('input[name="email"]').val('')
                    showSuccess(message)
                    document.dispatchEvent(new CustomEvent('newsletter.subscribed'))

                    setTimeout(() => {
                        if (typeof window.__hideNewsletterPopup === 'function') {
                            window.__hideNewsletterPopup()
                        }
                    }, 5000)
                },
                error: (error) => handleError(error),
                complete: () => {
                    if (typeof refreshRecaptcha !== 'undefined') {
                        refreshRecaptcha()
                    }

                    $button.prop('disabled', false).removeClass('btn-loading')
                },
            })
        })
    }

    onReady(() => {
        initNewsletterPopup()

        if (typeof jQuery !== 'undefined') {
            initNewsletterForm()
        } else {
            window.addEventListener('load', initNewsletterForm, { once: true })
        }
    })
})()
