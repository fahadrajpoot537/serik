<?php

namespace Botble\RealEstate\Providers;

use Botble\RealEstate\Commands\PurgeExpiredAccountsCommand;
use Botble\RealEstate\Commands\RenewPropertiesCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            RenewPropertiesCommand::class,
            PurgeExpiredAccountsCommand::class,
        ]);
    }
}
