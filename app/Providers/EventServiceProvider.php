<?php

namespace App\Providers;

use App\Events\ConsultSubmitted;
use App\Listeners\PushConsultLeadToGoHighLevel;
use App\Listeners\PushContactLeadToGoHighLevel;
use Botble\Contact\Events\SentContactEvent;
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
    ];
}
