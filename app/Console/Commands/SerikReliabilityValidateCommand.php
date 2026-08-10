<?php

namespace App\Console\Commands;

use App\Support\SerikReliabilityService;
use Illuminate\Console\Command;

class SerikReliabilityValidateCommand extends Command
{
    protected $signature = 'serik:reliability:validate
        {--recover : Apply safe recoveries (stuck GHL, search checkpoints, archive cache)}
        {--json : JSON output}';

    protected $description = 'Detect corrupted/orphaned/incomplete sync state; optional safe recovery';

    public function handle(SerikReliabilityService $reliability): int
    {
        $report = $reliability->validate((bool) $this->option('recover'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return $report['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Serik reliability validation');
        if ($report['issues'] === []) {
            $this->line('No integrity issues detected.');
        } else {
            $this->table(
                ['Severity', 'Code', 'Message'],
                collect($report['issues'])->map(fn ($i) => [
                    $i['severity'] ?? '',
                    $i['code'] ?? '',
                    $i['message'] ?? '',
                ])->all()
            );
        }

        if ($this->option('recover')) {
            $this->newLine();
            $this->info('Recoveries applied:');
            $this->line(json_encode($report['recovered'], JSON_PRETTY_PRINT));
        }

        return $report['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
