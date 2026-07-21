@php
    use App\Support\SerikMediaUrl;

    $title = theme_option('newsletter_popup_title');
    $image = theme_option('newsletter_popup_image');
    $imageUrl = $image
        ? SerikMediaUrl::resolvePublic([
            $image,
            'newsletter-1.webp',
            'newsletter.webp',
            'general/newsletter-image.jpg',
        ])
        : null;
    $placeholderUrl = SerikMediaUrl::placeholder();
@endphp

<style>
    .newsletter-popup.modal {
        z-index: 99999999 !important;
    }

    .newsletter-popup.modal.show {
        display: block !important;
    }

    body.newsletter-popup-open .modal-backdrop.show {
        z-index: 99999998 !important;
        opacity: 0.35 !important;
    }

    .newsletter-popup .modal-dialog {
        background: transparent !important;
        border-radius: 0;
        overflow: visible;
        margin: 0.75rem auto;
        max-width: calc(100% - 1.5rem);
        box-shadow: none;
    }

    .newsletter-popup .modal-dialog.modal-lg {
        max-width: 800px;
    }

    .newsletter-popup .modal-content {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
        position: relative;
    }

    .newsletter-popup .modal-content > .btn-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 20;
        pointer-events: auto;
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        opacity: 1;
        padding: 0.6rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .newsletter-popup .newsletter-popup-bg {
        background-color: #f7f7f7;
        padding: 0;
    }

    .newsletter-popup .newsletter-popup-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .newsletter-popup .newsletter-popup-bg--mobile {
        height: 140px;
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .newsletter-popup .newsletter-popup-bg {
            width: 100%;
            height: 12rem;
        }
    }

    .newsletter-popup .newsletter-popup-content {
        width: 100%;
        padding: 1.25rem !important;
        position: relative;
    }

    @media (min-width: 768px) {
        .newsletter-popup .newsletter-popup-content {
            padding: 2rem 2.25rem !important;
        }
    }

    .newsletter-popup .popup-content {
        display: flex;
        flex-direction: column;
        text-align: center;
        font-family: Arial, sans-serif;
        zoom: 0.8;
    }

    .newsletter-popup .popup-content h2 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .newsletter-popup .popup-content h2 .text-success,
    .newsletter-popup .popup-content h2 span {
        color: #013677;
    }

    .newsletter-popup .popup-content p {
        font-size: 18px !important;
        color: #555;
        margin-bottom: 20px;
    }

    .newsletter-popup .popup-alert {
        background: #fff3cd;
        color: #856404;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 18px !important;
        margin-bottom: 20px;
        display: inline-block;
    }

    .newsletter-popup .popup-btn {
        width: 100%;
        padding: 12px;
        font-size: 18px !important;
        font-weight: 600;
        border-radius: 8px;
    }

    .newsletter-popup .popup-checklist {
        text-align: left;
        margin-top: 20px;
        font-size: 14px !important;
    }

    .newsletter-popup .popup-checklist .form-check-label {
        margin-left: 5px;
        color: #000;
    }

    .newsletter-popup .popup-checklist .form-check {
        margin-bottom: 8px;
    }

    .newsletter-popup .popup-footer {
        margin-top: 15px;
        font-size: 12px;
        color: #999;
    }

    .newsletter-popup .dont-show .form-check-label {
        color: #000 !important;
        font-weight: 500;
    }

    .newsletter-popup .dont-show {
        margin-top: 14px;
        text-align: center;
    }

    @media (max-width: 767px) {
        .newsletter-popup .popup-content {
            zoom: 1;
        }

        .newsletter-popup .popup-content h2 {
            font-size: 22px;
            margin-bottom: 12px;
        }

        .newsletter-popup .popup-content p,
        .newsletter-popup .popup-alert {
            font-size: 15px !important;
            margin-bottom: 14px;
        }

        .newsletter-popup .popup-btn {
            font-size: 16px !important;
            padding: 11px;
        }

        .newsletter-popup .newsletter-popup-content {
            padding: 1rem !important;
        }
    }
</style>

<div @class(['modal-dialog modal-dialog-centered', 'modal-lg' => $image])>
    <div @class([
        'modal-content border-0',
        'd-flex flex-md-col flex-lg-row' => $image,
    ])>
        @if ($imageUrl)
            <div class="newsletter-popup-bg newsletter-popup-bg--mobile d-md-none">
                <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="eager" decoding="async" onerror="this.onerror=null;this.src='{{ $placeholderUrl }}'">
            </div>

            <div class="d-none d-md-block col-6 newsletter-popup-bg">
                <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="eager" decoding="async" onerror="this.onerror=null;this.src='{{ $placeholderUrl }}'">
            </div>
        @endif

        <button
            type="button"
            class="btn-close js-newsletter-popup-close"
            data-bs-dismiss="modal"
            aria-label="Close"
        ></button>

        <div class="newsletter-popup-content">
            <div class="popup-overlay">
                <div class="popup-content">
                    <h2>Get up to <span style="color: #013677;">1.5% Cash Back</span> &amp; Save on Closing Costs!</h2>
                    <p>Serik Realty helps you save thousands on your home purchase with cash back and reduced closing costs — no hidden fees.</p>

                    <div class="popup-alert">
                        ⏰ Limited-time offer for Ontario buyers
                    </div>

                    <a href="https://serik.ca/contact-us" class="whatsapp" target="_blank" rel="noopener">
                        <button type="button" class="btn btn-warning popup-btn" style="background:#013677;color:white;border:1px solid #013677;">
                            Book Your Free Consultation Today
                        </button>
                    </a>

                    <div class="popup-footer">No obligation. Takes less than 2 minutes.</div>

                    <div class="popup-checklist mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check1" checked disabled>
                            <label class="form-check-label" for="check1" style="opacity: 1 !important;">
                                Put money back in your pocket after closing
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check2" checked disabled>
                            <label class="form-check-label" for="check2" style="opacity: 1 !important;">
                                Expert guidance through every step of the buying process
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check3" checked disabled>
                            <label class="form-check-label" for="check3" style="opacity: 1 !important;">
                                Transparent pricing with no hidden fees or surprises
                            </label>
                        </div>
                    </div>

                    <div class="form-check mt-3 dont-show">
                        <input class="form-check-input" type="checkbox" name="dont_show_again" id="dontShow">
                        <label class="form-check-label" for="dontShow">
                            Don't show this again
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
