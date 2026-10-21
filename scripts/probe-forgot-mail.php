<?php

/**
 * Test forgot-PIN mail path (does NOT change any account password).
 *
 *   php scripts/probe-forgot-mail.php you@example.com
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$to = $argv[1] ?? null;
if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/probe-forgot-mail.php you@example.com\n");
    exit(1);
}

try {
    app(Illuminate\Mail\MailManager::class);
} catch (Throwable $e) {
}

$from = config('mail.from.address') ?: setting('email_from_address');
$key = config('services.resend.key') ?: setting('email_resend_key') ?: env('RESEND_API_KEY');

echo 'mailer=' . config('mail.default') . PHP_EOL;
echo 'from=' . $from . PHP_EOL;
echo 'resend_key=' . ($key ? ('yes len=' . strlen((string) $key)) : 'NO') . PHP_EOL;

$pin = (string) random_int(100000, 999999);
echo "test_pin={$pin} (not saved to DB)\n";

try {
    $ok = App\Support\AccountPinMailer::send($to, 'Mail Probe', $pin, 'forgot-password');
    echo 'send_ok=' . ($ok ? 'YES' : 'NO') . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR=' . $e->getMessage() . PHP_EOL;
    exit(2);
}

echo "Check inbox+spam for: Your new Serik Realty login PIN\n";
echo "Log lines: [AccountPinMailer]\n";
