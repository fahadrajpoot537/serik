<?php

namespace App\Console\Commands;

use App\Support\SerikWindowsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Lightweight Redis/Memurai diagnostic for Windows production.
 */
class SerikRedisStatusCommand extends Command
{
    protected $signature = 'serik:redis:status
        {--json : Machine-readable JSON output}';

    protected $description = 'Report Redis/Memurai connectivity, latency, memory, persistence, and Windows service state';

    public function handle(): int
    {
        $host = (string) config('database.redis.default.host', '127.0.0.1');
        $port = (int) config('database.redis.default.port', 6379);
        $client = (string) config('database.redis.client', 'predis');
        $cacheStore = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');
        $queueConnection = (string) config('queue.default');

        $started = microtime(true);
        $status = 'DOWN';
        $ping = null;
        $error = null;
        $ms = null;
        $info = [];

        try {
            $conn = Redis::connection('default');
            $ping = $conn->ping();
            $ms = round((microtime(true) - $started) * 1000, 2);
            $status = 'UP';
            $raw = $conn->command('INFO');
            $info = is_string($raw)
                ? $this->parseInfoString($raw)
                : (is_array($raw) ? $this->flattenInfo($raw) : []);

            // Redis 3.x INFO often omits maxmemory*; pull from CONFIG when missing.
            if (empty($info['maxmemory']) && empty($info['maxmemory_human'])) {
                try {
                    $mm = $conn->command('config', ['get', 'maxmemory']);
                    $bytes = $this->configGetValue($mm, 'maxmemory');
                    if ($bytes !== null && ctype_digit((string) $bytes)) {
                        $info['maxmemory'] = (string) $bytes;
                        $info['maxmemory_human'] = $this->formatBytes((int) $bytes);
                    }
                    $mp = $conn->command('config', ['get', 'maxmemory-policy']);
                    $policy = $this->configGetValue($mp, 'maxmemory-policy');
                    if ($policy !== null) {
                        $info['maxmemory_policy'] = (string) $policy;
                    }
                } catch (Throwable) {
                    //
                }
            } elseif (! empty($info['maxmemory']) && empty($info['maxmemory_human']) && ctype_digit((string) $info['maxmemory'])) {
                $info['maxmemory_human'] = $this->formatBytes((int) $info['maxmemory']);
            }
        } catch (Throwable $e) {
            $ms = round((microtime(true) - $started) * 1000, 2);
            $error = $e->getMessage();
        }

        $serviceName = $this->detectRedisServiceName();
        $serviceState = $serviceName
            ? SerikWindowsService::state($serviceName)
            : (SerikWindowsService::isWindows() ? 'NOT_INSTALLED' : 'N/A');

        $pid = $serviceName ? $this->serviceProcessId($serviceName) : null;

        $payload = [
            'redis_host' => $host,
            'redis_port' => $port,
            'redis_client' => $client,
            'redis_status' => $status,
            'connection_ms' => $ms,
            'ping' => is_object($ping) ? (string) $ping : $ping,
            'error' => $error,
            'redis_version' => $info['redis_version'] ?? null,
            'uptime_seconds' => isset($info['uptime_in_seconds']) ? (int) $info['uptime_in_seconds'] : null,
            'process_id' => isset($info['process_id']) ? (int) $info['process_id'] : $pid,
            'connected_clients' => isset($info['connected_clients']) ? (int) $info['connected_clients'] : null,
            'blocked_clients' => isset($info['blocked_clients']) ? (int) $info['blocked_clients'] : null,
            'instantaneous_ops_per_sec' => isset($info['instantaneous_ops_per_sec']) ? (int) $info['instantaneous_ops_per_sec'] : null,
            'used_memory' => $info['used_memory_human'] ?? null,
            'used_memory_peak' => $info['used_memory_peak_human'] ?? null,
            'maxmemory' => $info['maxmemory_human'] ?? ($info['maxmemory'] ?? null),
            'maxmemory_policy' => $info['maxmemory_policy'] ?? null,
            'evicted_keys' => isset($info['evicted_keys']) ? (int) $info['evicted_keys'] : null,
            'rejected_connections' => isset($info['rejected_connections']) ? (int) $info['rejected_connections'] : null,
            'keyspace_hits' => isset($info['keyspace_hits']) ? (int) $info['keyspace_hits'] : null,
            'keyspace_misses' => isset($info['keyspace_misses']) ? (int) $info['keyspace_misses'] : null,
            'rdb_last_bgsave_status' => $info['rdb_last_bgsave_status'] ?? null,
            'aof_enabled' => isset($info['aof_enabled']) ? (bool) (int) $info['aof_enabled'] : null,
            'cache_store' => $cacheStore,
            'session_driver' => $sessionDriver,
            'queue_connection' => $queueConnection,
            'windows_service' => $serviceName,
            'windows_service_state' => $serviceState,
            'tcp_6379_open' => $this->tcpOpen($host, $port),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $status === 'UP' ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Key', 'Value'], collect($payload)->map(fn ($v, $k) => [
            $k,
            is_bool($v) ? ($v ? 'true' : 'false') : (string) ($v ?? ''),
        ])->values()->all());

        if ($status !== 'UP') {
            $this->warn('Redis is DOWN. As Administrator run: scripts\\windows\\configure-serik-redis-service.cmd');
        }

        return $status === 'UP' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, string>
     */
    protected function flattenInfo(array $raw): array
    {
        $flat = [];
        foreach ($raw as $section => $values) {
            if (is_array($values)) {
                foreach ($values as $k => $v) {
                    $flat[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
                }
            } else {
                $flat[(string) $section] = is_scalar($values) ? (string) $values : json_encode($values);
            }
        }

        return $flat;
    }

    /**
     * @return array<string, string>
     */
    protected function parseInfoString(string $raw): array
    {
        $flat = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $flat[$k] = $v;
        }

        return $flat;
    }

    /**
     * @param  mixed  $result
     */
    protected function configGetValue(mixed $result, string $key): mixed
    {
        if (! is_array($result)) {
            return null;
        }
        if (array_key_exists($key, $result)) {
            return $result[$key];
        }
        if (isset($result[0], $result[1]) && (string) $result[0] === $key) {
            return $result[1];
        }

        return $result[1] ?? null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0B';
        }
        $units = ['B', 'K', 'M', 'G'];
        $i = (int) floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));

        return round($bytes / (1024 ** $i), 2) . $units[$i];
    }

    protected function detectRedisServiceName(): ?string
    {
        if (! SerikWindowsService::isWindows()) {
            return null;
        }

        foreach (['Memurai', 'memurai', 'Redis', 'redis'] as $name) {
            $state = SerikWindowsService::state($name);
            if ($state !== 'NOT_INSTALLED' && $state !== 'UNKNOWN') {
                return $name;
            }
        }

        return null;
    }

    protected function serviceProcessId(string $serviceName): ?int
    {
        $output = [];
        $code = 1;
        @exec(
            'powershell -NoProfile -Command "(Get-CimInstance Win32_Service -Filter \"Name=\'' .
            addslashes($serviceName) .
            '\'\").ProcessId"',
            $output,
            $code
        );
        if ($code === 0 && isset($output[0]) && ctype_digit(trim($output[0]))) {
            return (int) trim($output[0]);
        }

        return null;
    }

    protected function tcpOpen(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
        if (is_resource($fp)) {
            fclose($fp);

            return true;
        }

        return false;
    }
}
