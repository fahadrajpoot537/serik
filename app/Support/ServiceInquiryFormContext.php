<?php

namespace App\Support;

use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Contact\Forms\Fronts\ContactForm;
use Botble\Contact\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Trusted homepage service-card contact-form context.
 *
 * Subject and CRM source are server-defined from a whitelisted service key.
 * Client-submitted subject/source/service-type values are never trusted.
 */
final class ServiceInquiryFormContext
{
    public const CONTEXT_KEY = 'service_inquiry';

    public const REQUEST_CONTEXT_KEY = 'serik_form_context';

    public const REQUEST_SERVICE_KEY = 'serik_service_key';

    /**
     * @var array<string, array{subject: string, source: string, title_aliases: list<string>}>
     */
    public const SERVICES = [
        'commercial_leasing' => [
            'subject' => 'Commercial Leasing Inquiry',
            'source' => 'Serik.ca - Commercial Leasing',
            'title_aliases' => ['commercial leasing'],
        ],
        'pre_construction' => [
            'subject' => 'Pre-Construction Inquiry',
            'source' => 'Serik.ca - Pre-Construction',
            'title_aliases' => ['pre-construction', 'pre construction', 'preconstruction'],
        ],
        'custom_home_build' => [
            'subject' => 'Custom Home-Build Inquiry',
            'source' => 'Serik.ca - Custom Home-Build',
            'title_aliases' => ['custom home-build', 'custom home build', 'custom homebuild'],
        ],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::SERVICES);
    }

    public static function isValidKey(mixed $value): bool
    {
        return is_string($value) && isset(self::SERVICES[$value]);
    }

    public static function validatedKey(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return self::isValidKey($value) ? $value : null;
    }

    /**
     * @return array{subject: string, source: string, title_aliases: list<string>}|null
     */
    public static function definition(string $key): ?array
    {
        return self::SERVICES[$key] ?? null;
    }

    public static function subjectFor(string $key): ?string
    {
        return self::SERVICES[$key]['subject'] ?? null;
    }

    public static function sourceFor(string $key): ?string
    {
        return self::SERVICES[$key]['source'] ?? null;
    }

    public static function contactUrl(string $key): string
    {
        return url('/contact-us') . '?' . http_build_query([
            self::REQUEST_CONTEXT_KEY => self::CONTEXT_KEY,
            self::REQUEST_SERVICE_KEY => $key,
        ]);
    }

    /**
     * Match a CMS service tab to a trusted key without trusting client URLs.
     *
     * @param  array<string, mixed>  $service
     */
    public static function keyFromService(array $service): ?string
    {
        $explicit = $service['service_key'] ?? $service['key'] ?? null;
        if (self::isValidKey($explicit)) {
            return $explicit;
        }

        $title = mb_strtolower(trim(html_entity_decode(strip_tags((string) ($service['title'] ?? '')), ENT_QUOTES, 'UTF-8')));

        if ($title !== '') {
            foreach (self::SERVICES as $key => $meta) {
                foreach ($meta['title_aliases'] as $alias) {
                    if ($alias !== '' && str_contains($title, mb_strtolower($alias))) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $service
     * @return array<string, mixed>
     */
    public static function resolveCard(array $service): array
    {
        $key = self::keyFromService($service);
        if ($key === null) {
            return $service;
        }

        $service['service_key'] = $key;
        $service['inquiry_subject'] = self::subjectFor($key);
        $service['button_url'] = self::contactUrl($key);
        $service['opens_service_inquiry'] = true;

        return $service;
    }

    public static function isActive(?Request $request = null): bool
    {
        return self::activeKey($request) !== null;
    }

    public static function activeKey(?Request $request = null): ?string
    {
        $request ??= request();

        $context = $request->input(self::REQUEST_CONTEXT_KEY) ?? $request->query(self::REQUEST_CONTEXT_KEY);
        if (! is_string($context) || $context !== self::CONTEXT_KEY) {
            return null;
        }

        return self::validatedKey(
            $request->input(self::REQUEST_SERVICE_KEY) ?? $request->query(self::REQUEST_SERVICE_KEY)
        );
    }

    public static function applyTrustedRequestOverrides(Request $request): void
    {
        $key = self::activeKey($request);
        if ($key === null) {
            return;
        }

        $email = strtolower(trim((string) $request->input('email', '')));

        $request->merge([
            self::REQUEST_CONTEXT_KEY => self::CONTEXT_KEY,
            self::REQUEST_SERVICE_KEY => $key,
            'subject' => self::subjectFor($key),
            'email' => $email !== '' ? $email : $request->input('email'),
        ]);

        $request->request->remove('source');
        $request->request->remove('ghl_source');
        $request->request->remove('ghl_field_id');
        $request->request->remove('service_type');
        $request->query->remove('source');
        $request->query->remove('ghl_source');
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

        $rules[self::REQUEST_CONTEXT_KEY] = ['required', 'string', Rule::in([self::CONTEXT_KEY])];
        $rules[self::REQUEST_SERVICE_KEY] = ['required', 'string', Rule::in(self::keys())];
        $rules['subject'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    public static function applyToContactForm(ContactForm $form): void
    {
        $key = self::activeKey();
        if ($key === null) {
            return;
        }

        $subject = (string) self::subjectFor($key);

        $class = trim((string) $form->getFormOption('class') . ' serik-service-inquiry-form');
        $form->setFormOption('class', $class);

        if ($form->has('filters_before_form')) {
            $form->addAfter(
                'filters_before_form',
                'service_form_intro',
                \Botble\Base\Forms\Fields\HtmlField::class,
                [
                    'html' => self::introHtml($subject),
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

        if (! $form->has(self::REQUEST_SERVICE_KEY)) {
            $form->add(
                self::REQUEST_SERVICE_KEY,
                'hidden',
                TextFieldOption::make()->value($key)
            );
        }
    }

    public static function applyToContact(Contact $contact, Request $request): void
    {
        $key = self::activeKey($request);
        if ($key === null) {
            return;
        }

        $contact->subject = self::subjectFor($key);
        $fields = is_array($contact->custom_fields) ? $contact->custom_fields : [];
        $fields['Service'] = self::subjectFor($key);
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
        string $key,
        ?string $pageUrl = null
    ): array {
        $subject = (string) self::subjectFor($key);
        $source = (string) self::sourceFor($key);

        return [
            'name' => $name,
            'email' => strtolower(trim($email)),
            'phone' => trim($phone),
            'subject' => $subject,
            'message' => $message,
            'source' => $source,
            'tags' => ['Website Lead', 'Contact Us Form', 'Serik Realty', $source],
            'submitted_page' => $pageUrl ?: url()->previous() ?: url('/'),
            'submitted_at' => now()->toIso8601String(),
            'merge_existing_tags' => true,
            'omit_empty' => true,
            'custom_fields' => self::configuredGhlCustomFields($key),
            'idempotency_key' => self::idempotencyKey($email, $key),
        ];
    }

    public static function idempotencyKey(string $email, string $key): string
    {
        return 'ghl:service-inquiry:' . md5(strtolower(trim($email)) . '|' . $key);
    }

    /**
     * @return list<array{id: string, field_value: string}>
     */
    public static function configuredGhlCustomFields(string $key): array
    {
        $subject = (string) self::subjectFor($key);
        $source = (string) self::sourceFor($key);

        $map = [
            'lead_source' => [
                'config_key' => 'gohighlevel.contact_forms.lead_source_field_id',
                'env_key' => 'GOHIGHLEVEL_CONTACT_LEAD_SOURCE_FIELD_ID',
                'name' => 'Lead Source',
                'value' => $source,
            ],
            'subject' => [
                'config_key' => 'gohighlevel.contact_forms.subject_field_id',
                'env_key' => 'GOHIGHLEVEL_CONTACT_SUBJECT_FIELD_ID',
                'name' => 'Subject',
                'value' => $subject,
            ],
        ];

        $fields = [];

        foreach ($map as $item) {
            $id = trim((string) config($item['config_key'], ''));
            if ($id === '') {
                Log::warning('GoHighLevel contact custom field is not configured', [
                    'field_name' => $item['name'],
                    'config_key' => $item['config_key'],
                    'env_key' => $item['env_key'],
                    'form_context' => self::CONTEXT_KEY,
                    'service_key' => $key,
                ]);

                continue;
            }

            $fields[] = [
                'id' => $id,
                'field_value' => $item['value'],
            ];
        }

        return $fields;
    }

    private static function introHtml(string $subject): string
    {
        $title = e($subject);

        return <<<HTML
<div class="serik-service-form-intro" role="status">
    <h2 class="serik-service-form-title">{$title}</h2>
    <p class="serik-service-form-copy">Tell us about your inquiry. A Serik Realty advisor will follow up.</p>
</div>
<style>
.serik-service-form-intro{margin:0 0 1.25rem}
.serik-service-form-title{margin:0 0 .35rem;font-size:1.5rem;line-height:1.3}
.serik-service-form-copy{margin:0;color:#4b5563}
.serik-service-inquiry-form.serik-service-busy .tf-btn,
.serik-service-inquiry-form.serik-service-busy .contact-button{pointer-events:none}
</style>
<script>
(function(){
  document.addEventListener('submit', function(e){
    var f = e.target;
    if (!(f instanceof HTMLFormElement) || !f.classList.contains('serik-service-inquiry-form')) return;
    if (f.classList.contains('serik-service-busy')) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return;
    }
    f.classList.add('serik-service-busy');
    var b = f.querySelector('button[type="submit"],input[type="submit"]');
    if (b) { b.classList.add('button-loading','btn-loading'); }
    setTimeout(function(){
      f.classList.remove('serik-service-busy');
      if (b) { b.classList.remove('button-loading','btn-loading'); }
    }, 8000);
  }, true);
})();
</script>
HTML;
    }
}
