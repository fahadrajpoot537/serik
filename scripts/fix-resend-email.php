<?php

/**
 * One-shot Resend email fix — run via:
 *   php scripts/fix-resend-email.php you@email.com
 *   https://serik.ca/clear-serik-cache.php?key=serik2026clear&fix_resend=1&to=you@email.com
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = (string) env('RESEND_API_KEY', '');
if ($key === '') {
    echo "FAIL: RESEND_API_KEY missing in .env\n";
    exit(1);
}

$from = (string) env('MAIL_FROM_ADDRESS', 'onboarding@resend.dev');
$verified = array_filter(array_map('trim', explode(',', (string) env('RESEND_VERIFIED_DOMAINS', ''))));
if ($verified !== [] && ! str_ends_with(strtolower($from), '@resend.dev')) {
    $domainOk = false;
    foreach ($verified as $domain) {
        if (str_ends_with(strtolower($from), '@' . strtolower($domain))) {
            $domainOk = true;
            break;
        }
    }
    if (! $domainOk) {
        $from = 'onboarding@resend.dev';
        echo "WARN: From reset to onboarding@resend.dev (domain not in RESEND_VERIFIED_DOMAINS)\n";
    }
} elseif (! str_ends_with(strtolower($from), '@resend.dev') && $verified === [] && filter_var(env('RESEND_FORCE_SANDBOX_FROM', true), FILTER_VALIDATE_BOOL)) {
    $from = 'onboarding@resend.dev';
    echo "WARN: From reset to onboarding@resend.dev until serik.ca is verified in Resend.\n";
}

setting()->set([
    'email_driver' => 'resend',
    'email_resend_key' => $key,
    'email_from_address' => $from,
    'email_from_name' => env('MAIL_FROM_NAME', 'Serik Realty'),
    'plugins_real-estate_account-registered_status' => '1',
])->save();

echo 'DB email_driver=' . setting('email_driver') . "\n";
echo 'DB from=' . setting('email_from_address') . "\n";
echo 'DB key_prefix=' . substr((string) setting('email_resend_key'), 0, 10) . "\n";
echo 'template_enabled=' . (get_setting_email_status('plugins', 'real-estate', 'account-registered') ? 'yes' : 'no') . "\n\n";

$testTo = $argv[1] ?? env('MAIL_TEST_TO', setting('email_from_address'));
echo "Sending PIN template test to: {$testTo}\n";

try {
    app()->forgetInstance(\Illuminate\Mail\MailManager::class);
    app()->forgetInstance('mail.manager');

    $sent = \Botble\Base\Facades\EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
        ->setVariableValues([
            'account_name' => 'Mail Test',
            'account_email' => $testTo,
            'account_password' => '654321',
        ])
        ->sendUsingTemplate('account-registered', $testTo, [], true);

    echo $sent ? "SEND OK\n" : "SEND returned false\n";
    echo "\nCheck https://resend.com/emails for delivery/bounce status.\n";
    echo "Sandbox onboarding@resend.dev may only reach your Resend signup email.\n";
    echo "Production: verify serik.ca → RESEND_VERIFIED_DOMAINS=serik.ca → MAIL_FROM_ADDRESS=info@serik.ca\n";
    exit($sent ? 0 : 1);
} catch (Throwable $e) {
    echo 'SEND FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}
