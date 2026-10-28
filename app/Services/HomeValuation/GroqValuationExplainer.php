<?php

namespace App\Services\HomeValuation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Explains a verified valuation payload. Never invents prices or comps.
 */
class GroqValuationExplainer
{
    /**
     * @param  array<string, mixed>  $valuation
     * @return array{text: ?string, status: string}
     */
    public function explain(array $valuation): array
    {
        $cfg = config('home_valuation.groq', []);
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));
        if ($apiKey === '') {
            return ['text' => $this->fallback($valuation), 'status' => 'skipped_no_key'];
        }

        $endpoint = (string) ($cfg['endpoint'] ?? 'https://api.groq.com/openai/v1/chat/completions');
        $model = (string) ($cfg['model'] ?? 'llama-3.3-70b-versatile');

        $safeFacts = $this->safeFacts($valuation);
        $system = <<<'PROMPT'
You explain residential market estimates for Ontario, Canada.
Rules:
- Use ONLY the JSON facts provided. Never invent sold prices, comps, dates, distances, or statistics.
- Do not change or replace the estimated value or range.
- If basis is asking_price_indication, state clearly that this is not a sold-comparable valuation.
- If status is insufficient_data, explain why more data is needed without inventing numbers.
- Keep the reply under 120 words, plain language, no markdown headings.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((float) ($cfg['timeout_seconds'] ?? 8))
                ->connectTimeout((float) ($cfg['connect_timeout_seconds'] ?? 3))
                ->post($endpoint, [
                    'model' => $model,
                    'temperature' => (float) ($cfg['temperature'] ?? 0.2),
                    'max_tokens' => (int) ($cfg['max_tokens'] ?? 420),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => json_encode($safeFacts, JSON_UNESCAPED_SLASHES)],
                    ],
                ]);

            if ($response->status() === 429) {
                return ['text' => $this->fallback($valuation), 'status' => 'rate_limited'];
            }

            if (! $response->successful()) {
                Log::warning('Groq valuation explainer HTTP error', [
                    'status' => $response->status(),
                ]);

                return ['text' => $this->fallback($valuation), 'status' => 'http_error'];
            }

            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($text === '' || mb_strlen($text) > 1200) {
                return ['text' => $this->fallback($valuation), 'status' => 'malformed'];
            }

            if ($this->appearsFabricated($text, $valuation)) {
                return ['text' => $this->fallback($valuation), 'status' => 'rejected_fabrication'];
            }

            return ['text' => $text, 'status' => 'ok'];
        } catch (Throwable $e) {
            Log::warning('Groq valuation explainer failed', [
                'message' => $e->getMessage(),
            ]);

            return ['text' => $this->fallback($valuation), 'status' => 'timeout_or_error'];
        }
    }

    /**
     * @param  array<string, mixed>  $valuation
     * @return array<string, mixed>
     */
    private function safeFacts(array $valuation): array
    {
        $comps = [];
        foreach (array_slice($valuation['comparables'] ?? [], 0, 8) as $comp) {
            if (! is_array($comp)) {
                continue;
            }
            $comps[] = [
                'address' => $comp['address'] ?? null,
                'sold_price' => $comp['sold_price'] ?? null,
                'asking_price' => $comp['asking_price'] ?? null,
                'sold_date' => $comp['sold_date'] ?? null,
                'distance_km' => $comp['distance_km'] ?? null,
                'beds' => $comp['beds'] ?? null,
                'sqft' => $comp['sqft'] ?? null,
                'type' => $comp['type'] ?? null,
            ];
        }

        return [
            'status' => $valuation['status'] ?? null,
            'basis' => $valuation['basis'] ?? null,
            'estimate' => $valuation['estimate'] ?? null,
            'low' => $valuation['low'] ?? null,
            'high' => $valuation['high'] ?? null,
            'confidence' => $valuation['confidence'] ?? null,
            'confidence_reason' => $valuation['confidence_reason'] ?? null,
            'comps_count' => $valuation['comps_count'] ?? 0,
            'search' => $valuation['search'] ?? null,
            'subject' => [
                'address' => data_get($valuation, 'subject.address'),
                'beds' => data_get($valuation, 'subject.beds'),
                'baths' => data_get($valuation, 'subject.baths'),
                'sqft' => data_get($valuation, 'subject.sqft'),
                'type' => data_get($valuation, 'subject.type'),
            ],
            'comparables' => $comps,
            'methodology' => $valuation['methodology'] ?? null,
            'message' => $valuation['message'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $valuation
     */
    private function fallback(array $valuation): string
    {
        $status = (string) ($valuation['status'] ?? '');
        if ($status === 'insufficient_data') {
            return (string) ($valuation['message'] ?? 'Not enough verified sold comparables were found to produce a reliable estimate.');
        }
        if ($status === 'ambiguous_address') {
            return 'Multiple possible addresses matched. Please choose a more specific address suggestion.';
        }

        $basis = (string) ($valuation['basis'] ?? '');
        $estimate = (int) ($valuation['estimate'] ?? 0);
        $low = (int) ($valuation['low'] ?? 0);
        $high = (int) ($valuation['high'] ?? 0);
        $comps = (int) ($valuation['comps_count'] ?? 0);
        $confidence = (string) ($valuation['confidence'] ?? 'low');
        $reason = (string) ($valuation['confidence_reason'] ?? '');

        if ($basis === 'asking_price_indication') {
            return sprintf(
                'Asking-price market indication of about $%s (range $%s–$%s) from %d nearby active listings. This is not a sold-comparable valuation. Confidence: %s. %s',
                number_format($estimate),
                number_format($low),
                number_format($high),
                $comps,
                $confidence,
                $reason
            );
        }

        return sprintf(
            'Estimated market value about $%s (range $%s–$%s) using %d verified sold comparables. Confidence: %s. %s',
            number_format($estimate),
            number_format($low),
            number_format($high),
            $comps,
            $confidence,
            $reason
        );
    }

    /**
     * @param  array<string, mixed>  $valuation
     */
    private function appearsFabricated(string $text, array $valuation): bool
    {
        if (! preg_match_all('/\$[\d,]+/', $text, $matches)) {
            return false;
        }

        $allowed = [];
        foreach (['estimate', 'low', 'high'] as $key) {
            $n = (int) ($valuation[$key] ?? 0);
            if ($n > 0) {
                $allowed[] = $n;
            }
        }
        foreach ($valuation['comparables'] ?? [] as $comp) {
            if (! is_array($comp)) {
                continue;
            }
            foreach (['sold_price', 'asking_price'] as $key) {
                $n = (int) ($comp[$key] ?? 0);
                if ($n > 0) {
                    $allowed[] = $n;
                }
            }
        }

        foreach ($matches[0] as $raw) {
            $n = (int) preg_replace('/[^\d]/', '', $raw);
            if ($n < 10000) {
                continue;
            }
            $ok = false;
            foreach ($allowed as $a) {
                if (abs($a - $n) <= max(1000, (int) round($a * 0.02))) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return true;
            }
        }

        return false;
    }
}
