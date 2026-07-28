@if (\Botble\Captcha\Facades\Captcha::reCaptchaEnabled())
    <div class="contact-form-group mb-3">
        {!! \Botble\Captcha\Facades\Captcha::display() !!}
    </div>
@endif
