<?php

namespace App\Services\GoHighLevel;

use Illuminate\Support\Facades\Log;

/**
 * Resolves a real GHL Contact ID when workflow webhooks send a name/email
 * (or other hint) instead of the Contact ID — GHL's custom-data picker often
 * has no Contact ID variable.
 */
class GoHighLevelContactResolver
{
    public function __construct(protected GoHighLevelHttpClient $http)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolve(string $hint, ?string $mlsNumber = null, array $payload = []): ?string
    {
        $hint = trim($hint);
        $mls = strtoupper(trim((string) $mlsNumber));

        if ($hint !== '' && $this->looksLikeGhlContactId($hint) && $this->contactExists($hint)) {
            return $hint;
        }

        if ($mls !== '') {
            $byMls = $this->findByMls($mls);
            if ($byMls) {
                Log::channel('ghl_sync')->info('GoHighLevel contact resolved by MLS', [
                    'contact_id' => $byMls,
                    'mls' => $mls,
                ]);

                return $byMls;
            }
        }

        $email = $this->extractEmail($payload, $hint);
        if ($email !== '') {
            $byEmail = $this->findByQuery($email);
            if ($byEmail) {
                Log::channel('ghl_sync')->info('GoHighLevel contact resolved by email', [
                    'contact_id' => $byEmail,
                ]);

                return $byEmail;
            }
        }

        $phone = $this->extractPhone($payload);
        if ($phone !== '') {
            $byPhone = $this->findByQuery($phone);
            if ($byPhone) {
                Log::channel('ghl_sync')->info('GoHighLevel contact resolved by phone', [
                    'contact_id' => $byPhone,
                ]);

                return $byPhone;
            }
        }

        $name = $this->extractFullName($payload, $hint);
        if ($name !== '') {
            $byName = $this->findByFullName($name);
            if ($byName) {
                Log::channel('ghl_sync')->info('GoHighLevel contact resolved by full name', [
                    'contact_id' => $byName,
                ]);

                return $byName;
            }
        }

        Log::channel('ghl_sync')->warning('GoHighLevel contact resolve failed', [
            'hint_is_id_shape' => $this->looksLikeGhlContactId($hint),
            'has_mls' => $mls !== '',
            'has_email' => $email !== '',
            'has_phone' => $phone !== '',
            'has_name' => $name !== '',
        ]);

        return null;
    }

    /**
     * GHL contact ids are opaque alphanumeric tokens (no spaces). Full names are not.
     */
    public function looksLikeGhlContactId(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, ' ') || str_contains($value, '@')) {
            return false;
        }

        // Typical LeadConnector ids: ~20 chars [A-Za-z0-9]; allow a safe band.
        return (bool) preg_match('/^[A-Za-z0-9_-]{12,64}$/', $value);
    }

    protected function contactExists(string $contactId): bool
    {
        try {
            $data = $this->http->get('/contacts/' . $contactId);
            $id = (string) (data_get($data, 'contact.id') ?? data_get($data, 'id') ?? '');

            return $id !== '' && strcasecmp($id, $contactId) === 0;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function findByMls(string $mls): ?string
    {
        $fieldId = trim((string) (
            config('gohighlevel.mls_sync.mls_field_id')
            ?: app(GoHighLevelMlsPendingService::class)->resolveMlsFieldId()
            ?: ''
        ));

        if ($fieldId === '') {
            return null;
        }

        // Best-effort: some GHL locations do not index custom fields for /contacts/search.
        // Full-name / email / phone resolution remains the primary path for workflow webhooks.
        try {
            $result = $this->http->post('/contacts/search', [
                'locationId' => $this->http->locationId(),
                'pageLimit' => 10,
                'filters' => [[
                    'field' => 'customFields.' . $fieldId,
                    'operator' => 'eq',
                    'value' => $mls,
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel MLS contact search failed', [
                'mls' => $mls,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $contacts = data_get($result, 'contacts', []);
        if (! is_array($contacts) || $contacts === []) {
            return null;
        }

        if (count($contacts) === 1) {
            return (string) ($contacts[0]['id'] ?? '') ?: null;
        }

        // Prefer the most recently updated when duplicates share the same MLS.
        usort($contacts, static function ($a, $b) {
            $ta = strtotime((string) ($a['dateUpdated'] ?? $a['updatedAt'] ?? $a['dateAdded'] ?? 0)) ?: 0;
            $tb = strtotime((string) ($b['dateUpdated'] ?? $b['updatedAt'] ?? $b['dateAdded'] ?? 0)) ?: 0;

            return $tb <=> $ta;
        });

        $id = (string) ($contacts[0]['id'] ?? '');

        return $id !== '' ? $id : null;
    }

    protected function findByQuery(string $query): ?string
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        try {
            $result = $this->http->get('/contacts/', [
                'locationId' => $this->http->locationId(),
                'query' => $query,
                'limit' => 10,
            ]);
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel contact query failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $contacts = data_get($result, 'contacts', []);
        if (! is_array($contacts) || $contacts === []) {
            return null;
        }

        // Exact email match when querying by email
        if (str_contains($query, '@')) {
            foreach ($contacts as $contact) {
                if (strcasecmp((string) ($contact['email'] ?? ''), $query) === 0) {
                    return (string) ($contact['id'] ?? '') ?: null;
                }
            }
        }

        $id = (string) ($contacts[0]['id'] ?? '');

        return $id !== '' ? $id : null;
    }

    protected function findByFullName(string $fullName): ?string
    {
        $fullName = preg_replace('/\s+/', ' ', trim($fullName)) ?? '';
        if ($fullName === '') {
            return null;
        }

        try {
            $result = $this->http->get('/contacts/', [
                'locationId' => $this->http->locationId(),
                'query' => $fullName,
                'limit' => 20,
            ]);
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel contact name search failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $contacts = data_get($result, 'contacts', []);
        if (! is_array($contacts)) {
            return null;
        }

        $needle = mb_strtolower($fullName);
        $exact = [];
        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }
            $candidate = mb_strtolower(trim(
                (string) ($contact['contactName'] ?? '')
                ?: trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? ''))
            ));
            if ($candidate === $needle) {
                $exact[] = $contact;
            }
        }

        $pool = $exact !== [] ? $exact : $contacts;
        if ($pool === []) {
            return null;
        }

        if (count($pool) > 1 && $exact === []) {
            // Ambiguous name match without exact equality — do not guess.
            Log::channel('ghl_sync')->warning('GoHighLevel contact name ambiguous', [
                'matches' => count($pool),
            ]);

            return null;
        }

        $id = (string) ($pool[0]['id'] ?? '');

        return $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractEmail(array $payload, string $hint): string
    {
        if (str_contains($hint, '@')) {
            return strtolower(trim($hint));
        }

        $raw = (string) (
            data_get($payload, 'email')
            ?? data_get($payload, 'Email')
            ?? data_get($payload, 'contact.email')
            ?? data_get($payload, 'customData.email')
            ?? data_get($payload, 'contact_email')
            ?? ''
        );

        $raw = strtolower(trim($raw));

        return str_contains($raw, '@') ? $raw : '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractPhone(array $payload): string
    {
        $raw = (string) (
            data_get($payload, 'phone')
            ?? data_get($payload, 'Phone')
            ?? data_get($payload, 'contact.phone')
            ?? data_get($payload, 'customData.phone')
            ?? data_get($payload, 'contact_phone')
            ?? ''
        );

        return trim($raw);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractFullName(array $payload, string $hint): string
    {
        if ($hint !== '' && ! $this->looksLikeGhlContactId($hint) && ! str_contains($hint, '@')) {
            return preg_replace('/\s+/', ' ', $hint) ?? $hint;
        }

        $raw = (string) (
            data_get($payload, 'full_name')
            ?? data_get($payload, 'fullName')
            ?? data_get($payload, 'contact_name')
            ?? data_get($payload, 'contactName')
            ?? data_get($payload, 'Contact Full Name')
            ?? data_get($payload, 'customData.full_name')
            ?? data_get($payload, 'contact.contactName')
            ?? trim((string) data_get($payload, 'contact.firstName') . ' ' . (string) data_get($payload, 'contact.lastName'))
            ?? ''
        );

        return preg_replace('/\s+/', ' ', trim($raw)) ?? '';
    }
}
