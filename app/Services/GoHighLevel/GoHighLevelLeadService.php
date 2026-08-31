<?php

namespace App\Services\GoHighLevel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
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
     *   skip_note?: bool|null,
     *   inquiry_type?: string|null,
     *   submitted_page?: string|null,
     *   submitted_at?: string|null,
     *   custom_fields?: list<array{id: string, field_value: mixed}>|null,
     *   merge_existing_tags?: bool|null,
     *   omit_empty?: bool|null,
     *   idempotency_key?: string|null,
     *   fail_hard?: bool|null
     * }  $lead
     * @return array<string, mixed>|null
     */
    public function upsertLead(array $lead): ?array
    {
        $failHard = ! empty($lead['fail_hard']);

        if (! $this->enabled()) {
            if ($failHard) {
                throw new \App\Exceptions\AppointmentWorkflowException(
                    \App\Exceptions\AppointmentWorkflowException::CRM_AUTH,
                    'ghl_contact',
                    false,
                    'CRM is not configured.'
                );
            }

            return null;
        }

        $email = trim((string) ($lead['email'] ?? ''));
        $phone = trim((string) ($lead['phone'] ?? ''));
        $name = trim((string) ($lead['name'] ?? ''));
        $firstName = trim((string) ($lead['first_name'] ?? ''));
        $lastName = trim((string) ($lead['last_name'] ?? ''));

        if ($email === '' && $phone === '') {
            Log::info('GoHighLevel skip: lead has no email or phone');

            if ($failHard) {
                throw new \App\Exceptions\AppointmentWorkflowException(
                    \App\Exceptions\AppointmentWorkflowException::CRM_VALIDATION,
                    'ghl_contact',
                    false,
                    'CRM requires an email or phone.'
                );
            }

            return null;
        }

        $idempotencyKey = trim((string) ($lead['idempotency_key'] ?? ''));
        if ($idempotencyKey !== '' && ! Cache::add($idempotencyKey, 1, 60)) {
            Log::info('GoHighLevel skip: duplicate mortgage lead within idempotency window', [
                'source' => $lead['source'] ?? null,
            ]);

            return null;
        }

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName($name);
        }

        if ($name === '') {
            $name = trim($firstName . ' ' . $lastName);
        }

        $tags = $this->normalizeTags((array) ($lead['tags'] ?? []));

        $existing = null;
        if (! empty($lead['merge_existing_tags']) && $email !== '') {
            $existing = $this->findContactByEmail($email);
            if (is_array($existing)) {
                $tags = $this->normalizeTags(array_merge(
                    $this->extractTags($existing),
                    $tags
                ));
            }
        }

        $omitEmpty = ! empty($lead['omit_empty']);
        $payload = array_filter([
            'locationId' => config('services.gohighlevel.location_id'),
            'firstName' => $firstName !== '' ? $firstName : null,
            'lastName' => $lastName !== '' ? $lastName : null,
            'name' => $name !== '' ? $name : null,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'source' => (string) ($lead['source'] ?? 'serik.ca'),
            'tags' => $tags !== [] ? $tags : null,
            'website' => ($lead['property_url'] ?? null) ?: ($omitEmpty ? null : config('app.url')),
        ], static fn ($v) => $v !== null && $v !== '');

        $customFields = $this->formatCustomFields($lead['custom_fields'] ?? null);
        if ($customFields !== []) {
            $payload['customFields'] = $customFields;
        }

        try {
            $response = Http::withToken((string) config('services.gohighlevel.api_token'))
                ->withHeaders([
                    'Version' => (string) config('services.gohighlevel.api_version', '2021-07-28'),
                    'Accept' => 'application/json',
                ])
                ->timeout(15)
                ->post(rtrim((string) config('services.gohighlevel.base_url'), '/') . '/contacts/upsert', $payload);
        } catch (ConnectionException $e) {
            if ($failHard) {
                throw new \App\Exceptions\AppointmentWorkflowException(
                    \App\Exceptions\AppointmentWorkflowException::CRM_TIMEOUT,
                    'ghl_contact',
                    true,
                    'CRM request timed out.'
                );
            }

            throw $e;
        }

        if (! $response->successful()) {
            Log::warning('GoHighLevel upsert failed', [
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 200),
            ]);

            if ($failHard) {
                throw \App\Exceptions\AppointmentWorkflowException::fromGhlHttp($response->status());
            }

            return null;
        }

        $data = $response->json();
        $contactId = data_get($data, 'contact.id');

        if ($failHard && (! is_string($contactId) || $contactId === '')) {
            throw new \App\Exceptions\AppointmentWorkflowException(
                \App\Exceptions\AppointmentWorkflowException::CRM_VALIDATION,
                'ghl_contact',
                true,
                'CRM did not return a contact id.'
            );
        }

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
        if (! empty($lead['inquiry_type'])) {
            $lines[] = 'Mortgage Inquiry Type: ' . $lead['inquiry_type'];
        }
        if (! empty($lead['property_name'])) {
            $lines[] = 'Property: ' . $lead['property_name'];
        }
        if (! empty($lead['property_url'])) {
            $lines[] = 'URL: ' . $lead['property_url'];
        }
        if (! empty($lead['submitted_page'])) {
            $lines[] = 'Submitted page: ' . $lead['submitted_page'];
        }
        if (! empty($lead['submitted_at'])) {
            $lines[] = 'Submitted date: ' . $lead['submitted_at'];
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

    /**
     * @param  list<mixed>  $tags
     * @return list<string>
     */
    protected function normalizeTags(array $tags): array
    {
        $out = [];

        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $value = trim($tag);
            } elseif (is_array($tag)) {
                $value = trim((string) ($tag['name'] ?? $tag['tag'] ?? ''));
            } else {
                $value = '';
            }

            if ($value !== '') {
                $out[] = $value;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, mixed>  $contact
     * @return list<string>
     */
    protected function extractTags(array $contact): array
    {
        $tags = $contact['tags'] ?? data_get($contact, 'contact.tags', []);

        return $this->normalizeTags(is_array($tags) ? $tags : []);
    }

    /**
     * @param  mixed  $customFields
     * @return list<array{id: string, field_value: mixed}>
     */
    protected function formatCustomFields(mixed $customFields): array
    {
        if (! is_array($customFields) || $customFields === []) {
            return [];
        }

        $out = [];

        foreach ($customFields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $id = trim((string) ($field['id'] ?? ''));
            if ($id === '' || ! array_key_exists('field_value', $field)) {
                continue;
            }

            $out[] = [
                'id' => $id,
                'field_value' => $field['field_value'],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findContactByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        try {
            $response = Http::withToken((string) config('services.gohighlevel.api_token'))
                ->withHeaders([
                    'Version' => (string) config('services.gohighlevel.api_version', '2021-07-28'),
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get(rtrim((string) config('services.gohighlevel.base_url'), '/') . '/contacts/', [
                    'locationId' => config('services.gohighlevel.location_id'),
                    'query' => $email,
                    'limit' => 10,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $contacts = data_get($response->json(), 'contacts', []);
            if (! is_array($contacts)) {
                return null;
            }

            foreach ($contacts as $contact) {
                if (! is_array($contact)) {
                    continue;
                }
                if (strcasecmp((string) ($contact['email'] ?? ''), $email) === 0) {
                    return $contact;
                }
            }
        } catch (Throwable $e) {
            Log::info('GoHighLevel contact lookup skipped: ' . $e->getMessage());
        }

        return null;
    }
}
