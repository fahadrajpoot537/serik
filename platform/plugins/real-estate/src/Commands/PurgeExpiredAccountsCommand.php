<?php

namespace Botble\RealEstate\Commands;

use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:accounts:purge-expired', 'Delete real-estate accounts whose registration expired after 90 days')]
class PurgeExpiredAccountsCommand extends Command
{
    public function handle(): int
    {
        $count = AccountRegistrationExpiry::purgeExpiredAccounts();

        $this->components->info(sprintf('Deleted %d expired account(s).', $count));

        return self::SUCCESS;
    }
}
