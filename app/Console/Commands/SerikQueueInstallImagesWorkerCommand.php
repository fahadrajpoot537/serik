<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SerikQueueInstallImagesWorkerCommand extends Command
{
    protected $signature = 'serik:queue:install-images-worker
        {--dry-run : Show the installer path without executing}';

    protected $description = 'Install or update the SerikQueueImages Windows NSSM service (Administrator required)';

    public function handle(): int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->error('This command is for Windows production servers only.');

            return self::FAILURE;
        }

        $script = base_path('scripts/windows/install-serik-queue-images.cmd');

        if (! is_file($script)) {
            $this->error('Installer not found: ' . $script);

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Run as Administrator:');
            $this->line('  ' . $script);

            return self::SUCCESS;
        }

        $this->warn('Launching NSSM installer (requires Administrator UAC prompt)...');
        $this->line('  ' . $script);

        passthru('"' . $script . '"', $exitCode);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
