<?php

namespace App\Services\GoHighLevel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Shared LeadConnector HTTP client with retries for transient failures.
 * Does not alter GoHighLevelLeadService behaviour.
 */
class GoHighLevelHttpClient
{
    public function enabled(): bool
    {
        return (bool) config('services.gohighlevel.enabled')
            && filled(config('services.gohighlevel.api_token'))
            && filled(config('services.gohighlevel.location_id'));
    }

    public function locationId(): string
    {
        return (string) config('services.gohighlevel.location_id');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, query: $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->request('post', $path, body: $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function put(string $path, array $body = []): array
    {
        return $this->request('put', $path, body: $body);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path, array $query = [], array $body = []): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('GoHighLevel is disabled or missing credentials.');
        }

        if (! GoHighLevelCircuitBreaker::allow()) {
            GoHighLevelMetrics::incrDay('circuit_rejects');
            throw new RuntimeException('GoHighLevel circuit open — temporary pause after repeated failures.');
        }

        $url = rtrim((string) config('services.gohighlevel.base_url'), '/') . '/' . ltrim($path, '/');
        $timeout = (int) config('gohighlevel.mls_sync.http_timeout', 25);
        $retries = max(1, (int) config('gohighlevel.mls_sync.http_retries', 3));
        $sleepMs = max(100, (int) config('gohighlevel.mls_sync.http_retry_sleep_ms', 750));
        $correlationId = GoHighLevelMetrics::correlationId(
            is_string(request()?->attributes->get('ghl_correlation_id') ?? null)
                ? (string) request()->attributes->get('ghl_correlation_id')
                : null
        );

        $attempt = 0;
        $lastError = null;
        $t0 = microtime(true);

        while ($attempt < $retries) {
            $attempt++;

            try {
                $pending = Http::withToken((string) config('services.gohighlevel.api_token'))
                    ->withHeaders([
                        'Version' => (string) config('services.gohighlevel.api_version', '2021-07-28'),
                        'Accept' => 'application/json',
                        'X-Serik-Correlation-Id' => $correlationId,
                    ])
                    ->timeout($timeout)
                    ->acceptJson();

                /** @var Response $response */
                $response = match (strtolower($method)) {
                    'get' => $pending->get($url, $query),
                    'post' => $pending->post($url, $body),
                    'put' => $pending->put($url, $body),
                    default => throw new RuntimeException('Unsupported HTTP method: ' . $method),
                };

                if ($this->shouldRetryStatus($response->status())) {
                    $lastError = 'HTTP ' . $response->status();
                    GoHighLevelMetrics::incrDay('http_retries');
                    Log::channel('ghl_sync')->warning('GoHighLevel transient HTTP failure', [
                        'correlation_id' => $correlationId,
                        'method' => $method,
                        'path' => $path,
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'retry_after' => $response->header('Retry-After'),
                        'body' => $this->safeBody($response->body()),
                    ]);
                    $this->sleepBeforeRetry($sleepMs, $attempt, $response->header('Retry-After'));
                    continue;
                }

                if ($response->status() === 401) {
                    GoHighLevelCircuitBreaker::recordFailure();
                    Log::channel('ghl_sync')->error('GoHighLevel token unauthorized / expired', [
                        'correlation_id' => $correlationId,
                        'path' => $path,
                        'body' => $this->safeBody($response->body()),
                    ]);
                    throw new RuntimeException('GoHighLevel API token unauthorized or expired.');
                }

                if (! $response->successful()) {
                    // 4xx (except 408/429) are not retried
                    if ($response->status() >= 400 && $response->status() < 500) {
                        GoHighLevelCircuitBreaker::recordFailure();
                    }
                    throw new RuntimeException(
                        'GoHighLevel API error HTTP ' . $response->status() . ': ' . $this->safeBody($response->body())
                    );
                }

                GoHighLevelCircuitBreaker::recordSuccess();
                GoHighLevelMetrics::observeLatency('api_latency', (microtime(true) - $t0) * 1000);

                $json = $response->json();

                return is_array($json) ? $json : [];
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
                GoHighLevelMetrics::incrDay('http_retries');
                GoHighLevelCircuitBreaker::recordFailure();
                Log::channel('ghl_sync')->warning('GoHighLevel network failure', [
                    'correlation_id' => $correlationId,
                    'path' => $path,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                $this->sleepBeforeRetry($sleepMs, $attempt, null);
            } catch (RequestException $e) {
                $status = $e->response?->status();
                if ($status !== null && $this->shouldRetryStatus($status)) {
                    $lastError = $e->getMessage();
                    GoHighLevelMetrics::incrDay('http_retries');
                    $this->sleepBeforeRetry($sleepMs, $attempt, $e->response?->header('Retry-After'));
                    continue;
                }
                GoHighLevelCircuitBreaker::recordFailure();
                throw $e;
            } catch (Throwable $e) {
                if ($e instanceof RuntimeException && (
                    str_contains($e->getMessage(), 'token unauthorized')
                    || str_contains($e->getMessage(), 'circuit open')
                )) {
                    throw $e;
                }
                if ($attempt >= $retries) {
                    GoHighLevelCircuitBreaker::recordFailure();
                    throw $e;
                }
                $lastError = $e->getMessage();
                GoHighLevelMetrics::incrDay('http_retries');
                $this->sleepBeforeRetry($sleepMs, $attempt, null);
            }
        }

        GoHighLevelCircuitBreaker::recordFailure();
        throw new RuntimeException('GoHighLevel request failed after retries: ' . ($lastError ?? 'unknown'));
    }

    protected function shouldRetryStatus(int $status): bool
    {
        return $status === 429 || $status === 408 || $status >= 500;
    }

    protected function sleepBeforeRetry(int $baseSleepMs, int $attempt, ?string $retryAfter): void
    {
        $ms = $baseSleepMs * $attempt;

        if ($retryAfter !== null && $retryAfter !== '') {
            if (is_numeric($retryAfter)) {
                $ms = max($ms, (int) ((float) $retryAfter * 1000));
            } else {
                $until = strtotime($retryAfter);
                if ($until !== false) {
                    $ms = max($ms, max(0, $until - time()) * 1000);
                }
            }
        }

        // Cap + light jitter to avoid thundering herd on 429.
        $ms = min(30_000, $ms + random_int(0, 250));
        usleep($ms * 1000);
    }

    protected function safeBody(?string $body): string
    {
        $body = (string) $body;
        $body = preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i', 'Bearer ***', $body) ?? $body;
        $body = preg_replace('/("?(?:token|apiKey|api_key|authorization)"?\s*:\s*")([^"]+)(")/i', '$1***$3', $body) ?? $body;

        return mb_substr($body, 0, 500);
    }
}
