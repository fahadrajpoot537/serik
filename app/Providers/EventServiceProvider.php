<?php

namespace App\Providers;

use App\Events\ConsultSubmitted;
use App\Listeners\PushConsultLeadToGoHighLevel;
use App\Listeners\PushContactLeadToGoHighLevel;
use App\Listeners\PushRegisteredAccountToGoHighLevel;
use Botble\Contact\Events\SentContactEvent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SentContactEvent::class => [
            PushContactLeadToGoHighLevel::class,
        ],
        ConsultSubmitted::class => [
            PushConsultLeadToGoHighLevel::class,
        ],
        Registered::class => [
            PushRegisteredAccountToGoHighLevel::class,
        ],
    ];
}
