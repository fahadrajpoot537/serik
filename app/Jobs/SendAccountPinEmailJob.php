<?php

namespace App\Jobs;

use App\Support\AccountPinMailer;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue PIN email on HIGH when a worker is available (registration path).
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
        AccountPinMailer::send(
            $this->email,
            $this->accountName,
            $this->pin,
            $this->context
        );
    }
}
