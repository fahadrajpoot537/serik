<?php

namespace App\Support;

use Botble\RealEstate\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Structured public agent profile helpers. Never invents specialties,
 * titles, languages, or contact details.
 */
final class AgentProfile
{
    /**
     * @var list<string>
     */
    public const STRUCTURED_FIELDS = [
        'professional_title',
        'short_bio',
        'specialties',
        'service_areas',
        'languages',
        'contact_enabled',
        'display_order',
    ];

    /**
     * Fields an agent must never self-assign.
     *
     * @var list<string>
     */
    public const PRIVILEGED_FIELDS = [
        'is_featured',
        'is_public_profile',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_note',
        'credits',
        'approved_at',
        'blocked_at',
        'blocked_reason',
        'display_order',
        'password',
        'email',
    ];

    public static function hasColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('re_accounts', $column);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function listValidationRules(bool $admin = false): array
    {
        $rules = [
            'professional_title' => ['nullable', 'string', 'max:160'],
            'short_bio' => ['nullable', 'string', 'max:400'],
            'specialties' => ['nullable'],
            'service_areas' => ['nullable'],
            'languages' => ['nullable'],
            'contact_enabled' => ['nullable', 'boolean'],
        ];

        if ($admin) {
            $rules['display_order'] = ['nullable', 'integer', 'min:0', 'max:65535'];
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    public static function sanitizeList(mixed $value, int $maxItems = 8, int $maxLength = 80): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            $item = trim(strip_tags((string) $item));
            if ($item === '' || mb_strlen($item) > $maxLength) {
                continue;
            }
            $out[] = $item;
            if (count($out) >= $maxItems) {
                break;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function listFrom(Account $account, string $field): array
    {
        if (! self::hasColumn($field)) {
            return [];
        }

        return self::sanitizeList($account->getAttribute($field));
    }

    public static function listToInput(mixed $account, string $field): string
    {
        if (! $account instanceof Account) {
            return '';
        }

        return implode(', ', self::listFrom($account, $field));
    }

    public static function title(Account $account): string
    {
        if (self::hasColumn('professional_title')) {
            $title = trim(strip_tags((string) $account->getAttribute('professional_title')));
            if ($title !== '') {
                return $title;
            }
        }

        return trim(strip_tags((string) $account->company));
    }

    public static function shortBio(Account $account): string
    {
        if (self::hasColumn('short_bio')) {
            $bio = trim(strip_tags((string) $account->getAttribute('short_bio')));
            if ($bio !== '') {
                return $bio;
            }
        }

        return trim(strip_tags((string) $account->description));
    }

    public static function cardBio(Account $account, int $limit = 140): string
    {
        $bio = self::shortBio($account);
        if ($bio === '') {
            return '';
        }

        return Str::limit($bio, $limit, '…');
    }

    public static function isPublished(Account $account): bool
    {
        if (! empty($account->blocked_at)) {
            return false;
        }

        return (bool) $account->is_public_profile;
    }

    /**
     * @return array{
     *   name: bool,
     *   title: bool,
     *   bio: bool,
     *   specialties: bool,
     *   service_areas: bool,
     *   languages: bool,
     *   image: bool,
     *   profile_cta: bool,
     *   contact_cta: bool,
     *   complete: bool
     * }
     */
    public static function completeness(Account $account): array
    {
        $name = trim((string) $account->name) !== '';
        $title = self::title($account) !== '';
        $bio = self::shortBio($account) !== '';
        $specialties = self::listFrom($account, 'specialties') !== [];
        $areas = self::listFrom($account, 'service_areas') !== [];
        $languages = self::listFrom($account, 'languages') !== [];
        $image = filled($account->avatar_id);
        $profile = false;
        try {
            $url = $account->url ?? null;
            $profile = is_string($url) && $url !== '';
        } catch (\Throwable) {
            $profile = filled($account->username);
        }
        $contact = AgentInquiryFormContext::isContactable($account) && AgentInquiryFormContext::contactUrl($account) !== null;

        $required = $name && $title && $bio && $specialties && $areas && $languages && $image && $profile && $contact;

        return [
            'name' => $name,
            'title' => $title,
            'bio' => $bio,
            'specialties' => $specialties,
            'service_areas' => $areas,
            'languages' => $languages,
            'image' => $image,
            'profile_cta' => $profile,
            'contact_cta' => $contact,
            'complete' => $required,
        ];
    }

    public static function agentCanEditOwn(Account $target): bool
    {
        $actor = Auth::guard('account')->user();

        if (! $actor instanceof Account) {
            return false;
        }

        return (int) $actor->getKey() === (int) $target->getKey();
    }

    public static function adminCanEdit(): bool
    {
        $user = Auth::guard('web')->user() ?? Auth::user();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission('accounts.edit')) {
            return true;
        }

        if (method_exists($user, 'isSuperUser') && $user->isSuperUser()) {
            return true;
        }

        return false;
    }

    public static function canEdit(Account $target): bool
    {
        return self::adminCanEdit() || self::agentCanEditOwn($target);
    }

    public static function invalidatePublicCaches(): void
    {
        HomepageFragmentCache::bump('shortcode:agents');
        HomepageResponseCache::bump();
    }
}
