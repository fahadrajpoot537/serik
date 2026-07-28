@php
    // Botble auto-injects this field when enable_captcha + form enable_recaptcha.
    // Serik auth/contact use Theme\homzen\Supports\RecaptchaHelper instead so we
    // never render a second Google api.js onload that breaks login.
@endphp
