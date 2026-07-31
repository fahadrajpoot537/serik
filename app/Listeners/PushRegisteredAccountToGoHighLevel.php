<?php

namespace App\Listeners;

use App\Services\GoHighLevel\GoHighLevelLeadService;
use Botble\RealEstate\Models\Account;
use Illuminate\Auth\Events\Registered;

class PushRegisteredAccountToGoHighLevel
{
    public function __construct(protected GoHighLevelLeadService $ghl)
    {
    }

    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Website account registration only — ignore admin ACL / other guards.
        if (! $user instanceof Account) {
            return;
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $fullName = trim((string) ($user->name ?? ''));
        if ($fullName === '') {
            $fullName = trim($firstName . ' ' . $lastName);
        }

        $this->ghl->pushAfterResponse([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'source' => 'Website Registration — serik.ca',
            'tags' => ['Website Registration'],
            'message' => 'New account registration on serik.ca',
        ]);
    }
}
