<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Botble\Base\Facades\EmailHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queue PIN / registration email on HIGH so auth HTTP requests return quickly.
 */
class SendAccountPinEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public int $timeout = 60;

    public function __construct(
        public string $email,
        public string $accountName,
        public string $pin,
        public string $context = 'auth',
    ) {
        $this->onQueue(SerikQueue::high());
    }

    public function handle(): void
    {
        try {
            $sent = EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $this->accountName,
                    'account_email' => $this->email,
                    'account_password' => $this->pin,
                ])
                ->sendUsingTemplate('account-registered', $this->email);

            if (! $sent) {
                Log::warning('[SendAccountPinEmailJob] template disabled or not sent', [
                    'email' => $this->email,
                    'context' => $this->context,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('[SendAccountPinEmailJob] failed', [
                'email' => $this->email,
                'context' => $this->context,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
