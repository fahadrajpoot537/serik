<?php

namespace Theme\homzen\Supports;

use Illuminate\Support\Facades\Http;

class RecaptchaHelper
{
    /** Google test keys — work on localhost and always pass verification */
    private const LOCAL_SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';

    private const LOCAL_SECRET_KEY = '6LeIxAcTAAAAAGG-vFI1TnRWxM8L79iOEv7kI';

    public static function usesLocalTestKeys(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        $host = self::requestHost();

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');
    }

    /**
     * Actual browser host (HTTP_HOST), not APP_URL-forced host from getHost().
     */
    private static function requestHost(): string
    {
        $host = (string) (request()->server('HTTP_HOST') ?: request()->header('Host') ?: request()->getHost());
        $host = strtolower($host);

        return explode(':', $host, 2)[0];
    }

    public static function siteKey(): string
    {
        if (self::usesLocalTestKeys()) {
            return self::LOCAL_SITE_KEY;
        }

        $key = config('services.recaptcha.site_key');

        if (! empty($key)) {
            return (string) $key;
        }

        if (function_exists('setting')) {
            $dbKey = setting('captcha_site_key');

            if (! empty($dbKey)) {
                return (string) $dbKey;
            }
        }

        return '';
    }

    public static function secretKey(): string
    {
        if (self::usesLocalTestKeys()) {
            return self::LOCAL_SECRET_KEY;
        }

        $secret = config('services.recaptcha.secret_key');

        if (! empty($secret)) {
            return (string) $secret;
        }

        if (function_exists('setting')) {
            $dbSecret = setting('captcha_secret');

            if (! empty($dbSecret)) {
                return (string) $dbSecret;
            }
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function verify(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        if (! self::isConfigured()) {
            return false;
        }

        if (self::usesLocalTestKeys()) {
            return true;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => self::secretKey(),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            return (bool) $response->json('success');
        } catch (\Throwable) {
            return false;
        }
    }
}
