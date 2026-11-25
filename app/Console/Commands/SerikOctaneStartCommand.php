<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Octane\Commands\Command;
use Laravel\Octane\Commands\Concerns\InstallsRoadRunnerDependencies;
use Laravel\Octane\Commands\Concerns\InteractsWithEnvironmentVariables;
use Laravel\Octane\Commands\Concerns\InteractsWithServers;
use Laravel\Octane\RoadRunner\ServerProcessInspector;
use Laravel\Octane\RoadRunner\ServerStateFile;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Windows-safe Octane RoadRunner starter.
 *
 * Laravel's `octane:start` implements Symfony's SignalableCommandInterface, which
 * requires the pcntl extension (unavailable on Windows). This command uses the same
 * RoadRunner boot path without signal subscription so NSSM / local Windows can run it.
 */
class SerikOctaneStartCommand extends Command
{
    use InstallsRoadRunnerDependencies;
    use InteractsWithEnvironmentVariables;
    use InteractsWithServers;

    protected $signature = 'serik:octane-start
                    {--host= : The IP address the server should bind to}
                    {--port= : The port the server should be available on}
                    {--rpc-host= : The RPC IP address the server should bind to}
                    {--rpc-port= : The RPC port the server should be available on}
                    {--workers=auto : The number of workers that should be available to handle requests}
                    {--max-requests=500 : The number of requests to process before reloading the server}
                    {--rr-config= : The path to the RoadRunner .rr.yaml file}
                    {--watch : Automatically reload the server when the application is modified}
                    {--poll : Use file system polling while watching in order to watch files over a network}
                    {--log-level= : Log messages at or above the specified log level}';

    protected $description = 'Start Laravel Octane (RoadRunner) without pcntl — preferred on Windows';

    /**
     * InteractsWithServers defines SIG* subscriptions; returning none avoids
     * Symfony Console requiring the pcntl extension on Windows.
     */
    public function getSubscribedSignals(): array
    {
        return [];
    }

    public function handle(ServerProcessInspector $inspector, ServerStateFile $serverStateFile): int
    {
        // The parent process only watches RoadRunner. PHP's 300s CLI limit was killing it.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        if (! $this->isRoadRunnerInstalled()) {
            $this->components->error('RoadRunner not installed. Run composer require spiral/roadrunner-http spiral/roadrunner-cli and ensure rr.exe exists.');

            return 1;
        }

        $roadRunnerBinary = $this->ensureRoadRunnerBinaryIsInstalled();

        $this->ensurePortIsAvailable();

        if ($inspector->serverIsRunning()) {
            $this->components->error('RoadRunner server is already running.');

            return 1;
        }

        $this->ensureRoadRunnerBinaryMeetsRequirements($roadRunnerBinary);

        $this->writeServerStateFile($serverStateFile);

        $this->forgetEnvironmentVariables();

        $server = tap(new Process(array_filter([
            $roadRunnerBinary,
            '-c', $this->configPath(),
            '-o', 'version=3',
            '-o', 'http.address='.$this->getHost().':'.$this->getPort(),
            '-o', 'server.command='.$this->roadRunnerServerCommand(),
            '-o', 'http.pool.num_workers='.$this->workerCount(),
            '-o', 'http.pool.max_jobs='.$this->option('max-requests'),
            '-o', 'rpc.listen=tcp://'.$this->rpcHost().':'.$this->rpcPort(),
            '-o', 'http.pool.supervisor.exec_ttl='.$this->maxExecutionTime(),
            '-o', 'http.pool.supervisor.max_worker_memory='.$this->maxWorkerMemory(),
            '-o', 'http.static.dir='.public_path(),
            '-o', 'http.middleware='.config('octane.roadrunner.http_middleware', 'static'),
            '-o', 'logs.mode=production',
            '-o', 'logs.level='.($this->option('log-level') ?: (app()->environment('local') ? 'debug' : 'warn')),
            '-o', 'logs.output=stdout',
            '-o', 'logs.encoding=json',
            'serve',
        ]), base_path(), [
            'APP_ENV' => app()->environment(),
            'APP_BASE_PATH' => base_path(),
            'LARAVEL_OCTANE' => '1',
        ]))->start();

        $serverStateFile->writeProcessId($server->getPid());

        return $this->runServer($server, $inspector, 'roadrunner');
    }

    protected function writeServerStateFile(ServerStateFile $serverStateFile): void
    {
        $serverStateFile->writeState([
            'appName' => config('app.name', 'Laravel'),
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'rpcPort' => $this->rpcPort(),
            'workers' => $this->workerCount(),
            'maxRequests' => $this->option('max-requests'),
            'octaneConfig' => config('octane'),
        ]);
    }

    protected function workerCount(): int
    {
        return $this->option('workers') == 'auto'
            ? 0
            : (int) $this->option('workers');
    }

    protected function workerCommand(): string
    {
        $command = config('octane.roadrunner.command', 'vendor/bin/roadrunner-worker');

        return $this->isAbsolutePath($command) ? $command : base_path($command);
    }

    /**
     * RoadRunner "server.command" is a comma-separated argv list.
     * On Windows we inject -d opcache.enable_cli=0 to avoid XAMPP ZTS crashes.
     */
    protected function roadRunnerServerCommand(): string
    {
        $php = (new PhpExecutableFinder)->find(false) ?: (new PhpExecutableFinder)->find();
        $worker = $this->workerCommand();
        $extra = trim((string) env('OCTANE_PHP_ARGS', \PHP_OS_FAMILY === 'Windows' ? '-d opcache.enable_cli=0' : ''));

        $parts = [$php];
        if ($extra !== '') {
            foreach (preg_split('/\s+/', $extra) ?: [] as $arg) {
                if ($arg !== '') {
                    $parts[] = $arg;
                }
            }
        }
        $parts[] = $worker;

        return implode(',', $parts);
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1;
    }

    protected function configPath(): string
    {
        $path = $this->option('rr-config');

        if (! $path) {
            touch(base_path('.rr.yaml'));

            return base_path('.rr.yaml');
        }

        if ($path && ! realpath($path)) {
            throw new InvalidArgumentException('Unable to locate specified configuration file.');
        }

        return realpath($path);
    }

    protected function maxExecutionTime(): string
    {
        return config('octane.max_execution_time', '30').'s';
    }

    /**
     * RoadRunner kills a worker that crosses this limit and the next request
     * pays a full Laravel boot. This app's workers exceed the old 100MB cap.
     */
    protected function maxWorkerMemory(): int
    {
        return max(128, (int) env('OCTANE_WORKER_MEMORY_MB', 512));
    }

    protected function rpcHost(): string
    {
        return $this->option('rpc-host') ?: $this->getHost();
    }

    protected function rpcPort(): int
    {
        return (int) ($this->option('rpc-port') ?: ((int) $this->getPort() - 1999));
    }

    protected function writeServerOutput($server): void
    {
        [$output, $errorOutput] = $this->getServerOutput($server);

        Str::of($output)
            ->explode("\n")
            ->filter()
            ->each(function ($output) {
                if (! is_array($debug = json_decode($output, true))) {
                    return $this->components->info($output);
                }

                if (is_array($stream = json_decode($debug['msg'] ?? '', true))) {
                    return $this->handleStream($stream);
                }

                if (($debug['logger'] ?? null) == 'server') {
                    return $this->raw($debug['msg']);
                }

                if (($debug['level'] ?? null) == 'info'
                    && isset($debug['remote_address'])
                    && isset($debug['msg'])
                    && $debug['msg'] == 'http log') {
                    [
                        'elapsed' => $elapsed,
                        'method' => $method,
                        'status' => $statusCode,
                        'URI' => $url,
                    ] = $debug;

                    return $this->requestInfo([
                        'method' => $method,
                        'url' => $url,
                        'statusCode' => $statusCode,
                        'duration' => $this->calculateElapsedTime($elapsed),
                    ]);
                }
            });

        Str::of($errorOutput)
            ->explode("\n")
            ->filter()
            ->each(function ($output) {
                if (! Str::contains($output, ['DEBUG', 'INFO', 'WARN'])) {
                    $this->components->error($output);
                }
            });
    }

    protected function calculateElapsedTime(string $elapsed): float
    {
        if (Str::endsWith($elapsed, 'ms')) {
            return (float) substr($elapsed, 0, -2);
        }

        if (Str::endsWith($elapsed, 'µs')) {
            return (float) mb_substr($elapsed, 0, -2) * 0.001;
        }

        if (filter_var($elapsed, FILTER_VALIDATE_INT) !== false) {
            return (float) $elapsed;
        }

        return (float) $elapsed * 1000;
    }

    protected function stopServer(): void
    {
        $this->callSilent('octane:stop', [
            '--server' => 'roadrunner',
        ]);
    }

    protected function writeServerRunningMessage(): void
    {
        $this->components->info('Server running…');

        $this->output->writeln([
            '',
            '  Local: <fg=white;options=bold>http://'.$this->getHost().':'.$this->getPort().' </>',
            '',
            '  <fg=yellow>Windows: stop with `php artisan octane:stop` or NSSM restart (Ctrl+C is unreliable without pcntl)</>',
            '',
        ]);
    }
}
