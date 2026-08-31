<?php

namespace App\Support;

use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Contact\Forms\Fronts\ContactForm;
use Botble\Contact\Models\Contact;
use Botble\RealEstate\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Trusted agent-card contact-form context.
 *
 * The agent is resolved on the server. Client-submitted recipient IDs are ignored
 * unless they match a published, contactable account.
 */
final class AgentInquiryFormContext
{
    public const CONTEXT_KEY = 'agent_contact';

    public const REQUEST_CONTEXT_KEY = 'serik_form_context';

    public const REQUEST_AGENT_KEY = 'serik_agent_id';

    public static function contactUrl(Account $account): ?string
    {
        if (! self::isContactable($account)) {
            return null;
        }

        return url('/contact-us') . '?' . http_build_query([
            self::REQUEST_CONTEXT_KEY => self::CONTEXT_KEY,
            self::REQUEST_AGENT_KEY => $account->getKey(),
        ]);
    }

    public static function isContactable(?Account $account): bool
    {
        if (! $account instanceof Account) {
            return false;
        }

        if (! empty($account->blocked_at)) {
            return false;
        }

        if (Schema::hasColumn($account->getTable(), 'contact_enabled')) {
            $enabled = $account->getAttribute('contact_enabled');
            if ($enabled === false || $enabled === 0 || $enabled === '0') {
                return false;
            }
        }

        return true;
    }

    public static function resolveAgent(?Request $request = null): ?Account
    {
        $request ??= request();

        if (! class_exists(Account::class)) {
            return null;
        }

        $id = $request->input(self::REQUEST_AGENT_KEY) ?? $request->query(self::REQUEST_AGENT_KEY);
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        try {
            $account = Account::query()->find((int) $id);
        } catch (\Throwable) {
            return null;
        }

        return self::isContactable($account) ? $account : null;
    }

    public static function isActive(?Request $request = null): bool
    {
        $request ??= request();

        $context = $request->input(self::REQUEST_CONTEXT_KEY) ?? $request->query(self::REQUEST_CONTEXT_KEY);

        return is_string($context) && $context === self::CONTEXT_KEY && self::resolveAgent($request) !== null;
    }

    public static function subjectFor(Account $account): string
    {
        $name = trim((string) $account->name);

        return $name !== '' ? 'Inquiry for ' . $name : 'Agent Inquiry';
    }

    public static function sourceFor(Account $account): string
    {
        $name = trim((string) $account->name);

        return $name !== '' ? 'Serik.ca - Agent: ' . $name : 'Serik.ca - Agent Contact';
    }

    public static function applyTrustedRequestOverrides(Request $request): void
    {
        $account = self::resolveAgent($request);
        if ($account === null) {
            return;
        }

        $email = strtolower(trim((string) $request->input('email', '')));

        $request->merge([
            self::REQUEST_CONTEXT_KEY => self::CONTEXT_KEY,
            self::REQUEST_AGENT_KEY => $account->getKey(),
            'subject' => self::subjectFor($account),
            'email' => $email !== '' ? $email : $request->input('email'),
        ]);

        $request->request->remove('source');
        $request->request->remove('ghl_source');
        $request->request->remove('agent_email');
        $request->request->remove('recipient');
        $request->query->remove('source');
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function applyValidationRules(array $rules, ?Request $request = null): array
    {
        $request ??= request();

        if (! self::isActive($request)) {
            return $rules;
        }

        $account = self::resolveAgent($request);
        $id = $account?->getKey();

        $rules[self::REQUEST_CONTEXT_KEY] = ['required', 'string', Rule::in([self::CONTEXT_KEY])];
        $rules[self::REQUEST_AGENT_KEY] = ['required', 'integer', Rule::in([$id])];
        $rules['subject'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    public static function applyToContactForm(ContactForm $form): void
    {
        if (! self::isActive()) {
            return;
        }

        $account = self::resolveAgent();
        if ($account === null) {
            return;
        }

        $subject = self::subjectFor($account);
        $class = trim((string) $form->getFormOption('class') . ' serik-agent-inquiry-form');
        $form->setFormOption('class', $class);

        if ($form->has('filters_before_form')) {
            $form->addAfter(
                'filters_before_form',
                'agent_form_intro',
                \Botble\Base\Forms\Fields\HtmlField::class,
                [
                    'html' => '<div class="serik-agent-form-intro" role="status"><h2 class="serik-service-form-title">'
                        . e($subject)
                        . '</h2></div>',
                ]
            );
        }

        if ($form->has('subject')) {
            $form->modify(
                'subject',
                \Botble\Base\Forms\Fields\TextField::class,
                [
                    'label' => trans('plugins/contact::contact.form_subject'),
                    'value' => $subject,
                    'attr' => [
                        'readonly' => true,
                        'class' => 'form-control style-1',
                        'tabindex' => '-1',
                    ],
                ]
            );
        }

        if (! $form->has(self::REQUEST_CONTEXT_KEY)) {
            $form->add(
                self::REQUEST_CONTEXT_KEY,
                'hidden',
                TextFieldOption::make()->value(self::CONTEXT_KEY)
            );
        }

        if (! $form->has(self::REQUEST_AGENT_KEY)) {
            $form->add(
                self::REQUEST_AGENT_KEY,
                'hidden',
                TextFieldOption::make()->value((string) $account->getKey())
            );
        }
    }

    public static function applyToContact(Contact $contact, Request $request): void
    {
        $account = self::resolveAgent($request);
        if ($account === null) {
            return;
        }

        $contact->subject = self::subjectFor($account);
        $fields = is_array($contact->custom_fields) ? $contact->custom_fields : [];
        $fields['Agent'] = $account->name;
        $contact->custom_fields = $fields;
        $contact->save();
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildGhlLead(
        string $name,
        string $email,
        string $phone,
        string $message,
        Account $account
    ): array {
        $source = self::sourceFor($account);
        $subject = self::subjectFor($account);

        $customFields = [];
        $sourceFieldId = trim((string) config('gohighlevel.contact_forms.lead_source_field_id', ''));
        $subjectFieldId = trim((string) config('gohighlevel.contact_forms.subject_field_id', ''));

        if ($sourceFieldId !== '') {
            $customFields[] = ['id' => $sourceFieldId, 'field_value' => $source];
        } else {
            Log::warning('GoHighLevel contact custom field is not configured', [
                'field_name' => 'Lead Source',
                'form_context' => self::CONTEXT_KEY,
            ]);
        }

        if ($subjectFieldId !== '') {
            $customFields[] = ['id' => $subjectFieldId, 'field_value' => $subject];
        }

        return [
            'name' => $name,
            'email' => strtolower(trim($email)),
            'phone' => trim($phone),
            'subject' => $subject,
            'message' => $message,
            'source' => $source,
            'tags' => ['Website Lead', 'Contact Us Form', 'Serik Realty', $source],
            'submitted_page' => url()->previous() ?: url('/'),
            'submitted_at' => now()->toIso8601String(),
            'merge_existing_tags' => true,
            'omit_empty' => true,
            'custom_fields' => $customFields,
            'idempotency_key' => 'ghl:agent-inquiry:' . md5(strtolower(trim($email)) . '|' . $account->getKey()),
        ];
    }
}
