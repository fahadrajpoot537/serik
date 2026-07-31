<?php

namespace App\Services\GoHighLevel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Push website leads / registrations to GoHighLevel (LeadConnector) Contacts API.
 */
class GoHighLevelLeadService
{
    public function enabled(): bool
    {
        return (bool) config('services.gohighlevel.enabled')
            && filled(config('services.gohighlevel.api_token'))
            && filled(config('services.gohighlevel.location_id'));
    }

    /**
     * Queue the push after the HTTP response so form submit stays fast
     * without requiring a queue worker.
     *
     * @param  array<string, mixed>  $lead
     */
    public function pushAfterResponse(array $lead): void
    {
        if (! $this->enabled()) {
            return;
        }

        dispatch(function () use ($lead): void {
            try {
                app(self::class)->upsertLead($lead);
            } catch (Throwable $e) {
                report($e);
                Log::warning('GoHighLevel lead push failed', [
                    'message' => $e->getMessage(),
                    'source' => $lead['source'] ?? null,
                    'email' => $lead['email'] ?? null,
                ]);
            }
        })->afterResponse();
    }

    /**
     * @param  array{
     *   name?: string|null,
     *   first_name?: string|null,
     *   last_name?: string|null,
     *   email?: string|null,
     *   phone?: string|null,
     *   message?: string|null,
     *   subject?: string|null,
     *   property_name?: string|null,
     *   property_url?: string|null,
     *   source?: string|null,
     *   tags?: list<string>|null,
     *   skip_note?: bool|null
     * }  $lead
     * @return array<string, mixed>|null
     */
    public function upsertLead(array $lead): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $email = trim((string) ($lead['email'] ?? ''));
        $phone = trim((string) ($lead['phone'] ?? ''));
        $name = trim((string) ($lead['name'] ?? ''));
        $firstName = trim((string) ($lead['first_name'] ?? ''));
        $lastName = trim((string) ($lead['last_name'] ?? ''));

        if ($email === '' && $phone === '') {
            Log::info('GoHighLevel skip: lead has no email or phone');

            return null;
        }

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName($name);
        }

        if ($name === '') {
            $name = trim($firstName . ' ' . $lastName);
        }

        $tags = array_values(array_unique(array_filter(array_map(
            static fn ($t) => trim((string) $t),
            (array) ($lead['tags'] ?? [])
        ))));

        $payload = array_filter([
            'locationId' => config('services.gohighlevel.location_id'),
            'firstName' => $firstName !== '' ? $firstName : null,
            'lastName' => $lastName !== '' ? $lastName : null,
            'name' => $name !== '' ? $name : null,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'source' => (string) ($lead['source'] ?? 'serik.ca'),
            'tags' => $tags !== [] ? $tags : null,
            'website' => ($lead['property_url'] ?? null) ?: config('app.url'),
        ], static fn ($v) => $v !== null && $v !== '');

        $response = Http::withToken((string) config('services.gohighlevel.api_token'))
            ->withHeaders([
                'Version' => (string) config('services.gohighlevel.api_version', '2021-07-28'),
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->post(rtrim((string) config('services.gohighlevel.base_url'), '/') . '/contacts/upsert', $payload);

        if (! $response->successful()) {
            Log::warning('GoHighLevel upsert failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();
        $contactId = data_get($data, 'contact.id');

        if (is_string($contactId) && $contactId !== '' && empty($lead['skip_note'])) {
            $this->addNote($contactId, $this->buildNoteBody($lead));
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function buildNoteBody(array $lead): string
    {
        $lines = [
            'New website lead from serik.ca',
            'Source: ' . (string) ($lead['source'] ?? 'serik.ca'),
        ];

        if (! empty($lead['subject'])) {
            $lines[] = 'Subject: ' . $lead['subject'];
        }
        if (! empty($lead['property_name'])) {
            $lines[] = 'Property: ' . $lead['property_name'];
        }
        if (! empty($lead['property_url'])) {
            $lines[] = 'URL: ' . $lead['property_url'];
        }
        if (! empty($lead['message'])) {
            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = (string) $lead['message'];
        }

        return implode("\n", $lines);
    }

    protected function addNote(string $contactId, string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            return;
        }

        try {
            $response = Http::withToken((string) config('services.gohighlevel.api_token'))
                ->withHeaders([
                    'Version' => (string) config('services.gohighlevel.api_version', '2021-07-28'),
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->post(
                    rtrim((string) config('services.gohighlevel.base_url'), '/') . '/contacts/' . $contactId . '/notes',
                    ['body' => $body]
                );

            if (! $response->successful()) {
                Log::info('GoHighLevel note skipped/failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::info('GoHighLevel note error: ' . $e->getMessage());
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
