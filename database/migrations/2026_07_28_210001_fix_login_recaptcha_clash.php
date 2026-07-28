<?php

use Botble\Setting\Facades\Setting;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Revert global Botble captcha — it injected a second GreCAPTCHA api.js
        // (onloadCallback) via newsletter/footer forms and broke login verification.
        // Contact form uses RecaptchaHelper (same keys/scripts as login modal).
        Setting::set([
            'enable_captcha' => '0',
            'enable_recaptcha_botble_contact_forms_fronts_contact_form' => '0',
            'enable_recaptcha_botble_newsletter_forms_fronts_newsletter_form' => '0',
            'enable_recaptcha_botble_real_estate_forms_fronts_auth_login_form' => '0',
            'enable_recaptcha_botble_real_estate_forms_fronts_auth_register_form' => '0',
            'enable_recaptcha_botble_real_estate_forms_fronts_auth_forgot_password_form' => '0',
            'enable_recaptcha_botble_real_estate_forms_fronts_auth_reset_password_form' => '0',
            'enable_recaptcha_botble_a_c_l_forms_auth_login_form' => '0',
            'enable_recaptcha_botble_a_c_l_forms_auth_forgot_password_form' => '0',
            'enable_recaptcha_botble_a_c_l_forms_auth_reset_password_form' => '0',
        ])->save();
    }
};
