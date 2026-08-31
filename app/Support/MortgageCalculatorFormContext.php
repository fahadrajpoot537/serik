<?php

namespace App\Support;

use Botble\Contact\Forms\Fronts\ContactForm;
use Botble\Contact\Models\Contact;
use Botble\Contact\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Trusted Mortgage Calculator contact-form context.
 *
 * Subject, source, and form type are server-defined. Client-submitted
 * copies of those values are ignored unless they match this whitelist.
 */
final class MortgageCalculatorFormContext
{
    public const KEY = 'mortgage_calculator';

    public const SUBJECT = 'Mortgage Pre-Qualification Inquiry';

    public const SOURCE = 'Serik.ca - Mortgage Calculator';

    public const INQUIRY_LABEL = 'Mortgage Inquiry Type';

    public const INQUIRY_NEW = 'New Mortgage';

    public const INQUIRY_REFINANCE = 'Refinance';

    public const REQUEST_CONTEXT_KEY = 'serik_form_context';

    public const REQUEST_INQUIRY_KEY = 'mortgage_inquiry_type';

    /**
     * @var list<string>
     */
    public const INQUIRY_TYPES = [
        self::INQUIRY_NEW,
        self::INQUIRY_REFINANCE,
    ];

    /**
     * Labels that belong to the generic Contact Us qualification question
     * and must not appear on the mortgage form.
     *
     * @var list<string>
     */
    public const FORBIDDEN_INQUIRY_LABELS = [
        'Buyer',
        'Seller',
        'Tenant',
        'Landlord',
        'Are you a Landlord or Tenant?',
    ];

    public static function contactUrl(): string
    {
        return url('/contact-us') . '?' . http_build_query([
            self::REQUEST_CONTEXT_KEY => self::KEY,
        ]);
    }

    public static function submittedPageUrl(): string
    {
        return url('/mortgage-calculator');
    }

    public static function isActive(?Request $request = null): bool
    {
        return self::isWhitelistedContext($request ?? request());
    }

    public static function isWhitelistedContext(?Request $request = null): bool
    {
        $request ??= request();

        $candidates = [
            $request->input(self::REQUEST_CONTEXT_KEY),
            $request->query(self::REQUEST_CONTEXT_KEY),
            $request->input('form'),
            $request->query('form'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value === self::KEY) {
                return true;
            }
        }

        return false;
    }

    public static function validatedInquiryType(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return in_array($value, self::INQUIRY_TYPES, true) ? $value : null;
    }

    /**
     * Overwrite trusted fields on the incoming request. Tampered subject/source
     * values are ignored (replaced), never accepted.
     */
    public static function applyTrustedRequestOverrides(Request $request): void
    {
        if (! self::isWhitelistedContext($request)) {
            return;
        }

        $email = strtolower(trim((string) $request->input('email', '')));

        $request->merge([
            self::REQUEST_CONTEXT_KEY => self::KEY,
            'subject' => self::SUBJECT,
            'email' => $email !== '' ? $email : $request->input('email'),
        ]);

        $request->request->remove('source');
        $request->request->remove('ghl_source');
        $request->query->remove('source');

        $type = self::validatedInquiryType($request->input(self::REQUEST_INQUIRY_KEY));
        if ($type !== null) {
            $request->merge([self::REQUEST_INQUIRY_KEY => $type]);
        }
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function applyValidationRules(array $rules, ?Request $request = null): array
    {
        $request ??= request();

        if (! self::isWhitelistedContext($request)) {
            return $rules;
        }

        unset($rules['contact_custom_fields']);

        foreach (self::genericQualificationFieldIds() as $id) {
            unset($rules['contact_custom_fields.' . $id]);
        }

        $rules[self::REQUEST_CONTEXT_KEY] = ['required', 'string', Rule::in([self::KEY])];
        $rules[self::REQUEST_INQUIRY_KEY] = ['required', 'string', Rule::in(self::INQUIRY_TYPES)];
        $rules['subject'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    public static function applyToContactForm(ContactForm $form): void
    {
        if (! self::isActive()) {
            return;
        }

        $class = trim((string) $form->getFormOption('class') . ' serik-mortgage-calculator-form');
        $form->setFormOption('class', $class);

        if ($form->has('filters_before_form')) {
            $form->addAfter(
                'filters_before_form',
                'mortgage_form_intro',
                \Botble\Base\Forms\Fields\HtmlField::class,
                [
                    'html' => self::introHtml(),
                ]
            );
        }

        if ($form->has('subject')) {
            $form->modify(
                'subject',
                \Botble\Base\Forms\Fields\TextField::class,
                [
                    'label' => trans('plugins/contact::contact.form_subject'),
                    'value' => self::SUBJECT,
                    'attr' => [
                        'readonly' => true,
                        'class' => 'form-control style-1',
                        'tabindex' => '-1',
                    ],
                ]
            );
        }

        foreach (self::genericQualificationFieldNames() as $name) {
            if ($form->has($name)) {
                $form->remove($name);
            }
        }

        $after = $form->has('close_subject_wrapper_column_wrapper')
            ? 'close_subject_wrapper_column_wrapper'
            : 'subject';

        if (! $form->has('mortgage_inquiry_open')) {
            $form->addAfter(
                $after,
                'mortgage_inquiry_open',
                \Botble\Base\Forms\Fields\HtmlField::class,
                [
                    'html' => '<div class="contact-column-12 col-md-12 contact-field-mortgage_inquiry_type serik-mortgage-inquiry-type">',
                ]
            );
        }

        if (! $form->has(self::REQUEST_INQUIRY_KEY)) {
            $form->addAfter(
                'mortgage_inquiry_open',
                self::REQUEST_INQUIRY_KEY,
                \Botble\Base\Forms\Fields\RadioField::class,
                \Botble\Base\Forms\FieldOptions\RadioFieldOption::make()
                    ->label(self::INQUIRY_LABEL)
                    ->choices([
                        self::INQUIRY_NEW => self::INQUIRY_NEW,
                        self::INQUIRY_REFINANCE => self::INQUIRY_REFINANCE,
                    ])
                    ->required()
                    ->wrapperAttributes(['class' => 'contact-form-group'])
            );
        }

        if (! $form->has('mortgage_inquiry_close')) {
            $form->addAfter(
                self::REQUEST_INQUIRY_KEY,
                'mortgage_inquiry_close',
                \Botble\Base\Forms\Fields\HtmlField::class,
                [
                    'html' => '</div>',
                ]
            );
        }

        if (! $form->has(self::REQUEST_CONTEXT_KEY)) {
            $form->add(
                self::REQUEST_CONTEXT_KEY,
                'hidden',
                \Botble\Base\Forms\FieldOptions\TextFieldOption::make()
                    ->value(self::KEY)
            );
        }
    }

    public static function applyToContact(Contact $contact, Request $request): void
    {
        if (! self::isWhitelistedContext($request)) {
            return;
        }

        $type = self::validatedInquiryType($request->input(self::REQUEST_INQUIRY_KEY));
        if ($type === null) {
            $type = self::inquiryTypeFromCustomFields($contact->custom_fields);
        }
        if ($type === null) {
            return;
        }

        $contact->subject = self::SUBJECT;
        $contact->custom_fields = self::overlayCustomFields(
            is_array($contact->custom_fields) ? $contact->custom_fields : [],
            $type
        );
        $contact->save();
    }

    /**
     * @param  array<string, mixed>|null  $fields
     * @return array<string, mixed>
     */
    public static function overlayCustomFields(?array $fields, string $inquiryType): array
    {
        $fields = is_array($fields) ? $fields : [];

        foreach ($fields as $name => $value) {
            if (self::looksLikeGenericQualificationText((string) $name)
                || self::looksLikeGenericQualificationText((string) $value)) {
                unset($fields[$name]);
            }
        }

        $fields[self::INQUIRY_LABEL] = $inquiryType;

        return $fields;
    }

    /**
     * @param  array<string, mixed>|null  $fields
     */
    public static function inquiryTypeFromCustomFields(?array $fields): ?string
    {
        if (! is_array($fields)) {
            return null;
        }

        return self::validatedInquiryType($fields[self::INQUIRY_LABEL] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildGhlLead(
        string $name,
        string $email,
        string $phone,
        string $message,
        string $inquiryType
    ): array {
        $customFields = self::configuredGhlCustomFields($inquiryType);

        return [
            'name' => $name,
            'email' => strtolower(trim($email)),
            'phone' => trim($phone),
            'subject' => self::SUBJECT,
            'message' => $message,
            'source' => self::SOURCE,
            'tags' => ['Website Lead', 'Contact Us Form', 'Serik Realty', self::SOURCE],
            'inquiry_type' => $inquiryType,
            'submitted_page' => self::submittedPageUrl(),
            'submitted_at' => now()->toIso8601String(),
            'merge_existing_tags' => true,
            'omit_empty' => true,
            'custom_fields' => $customFields,
            'idempotency_key' => self::idempotencyKey($email, $inquiryType),
        ];
    }

    public static function idempotencyKey(string $email, string $inquiryType): string
    {
        return 'ghl:mortgage-calculator:' . md5(strtolower(trim($email)) . '|' . $inquiryType);
    }

    /**
     * @return list<array{id: string, field_value: string}>
     */
    public static function configuredGhlCustomFields(string $inquiryType): array
    {
        $map = [
            'inquiry_type' => [
                'config_key' => 'gohighlevel.contact_forms.inquiry_type_field_id',
                'env_key' => 'GOHIGHLEVEL_CONTACT_INQUIRY_TYPE_FIELD_ID',
                'name' => self::INQUIRY_LABEL,
                'value' => $inquiryType,
            ],
            'lead_source' => [
                'config_key' => 'gohighlevel.contact_forms.lead_source_field_id',
                'env_key' => 'GOHIGHLEVEL_CONTACT_LEAD_SOURCE_FIELD_ID',
                'name' => 'Lead Source',
                'value' => self::SOURCE,
            ],
            'subject' => [
                'config_key' => 'gohighlevel.contact_forms.subject_field_id',
                'env_key' => 'GOHIGHLEVEL_CONTACT_SUBJECT_FIELD_ID',
                'name' => 'Subject',
                'value' => self::SUBJECT,
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
                    'form_context' => self::KEY,
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

    /**
     * @return list<int|string>
     */
    public static function genericQualificationFieldIds(): array
    {
        try {
            if (! class_exists(CustomField::class)) {
                return [];
            }

            return CustomField::query()
                ->wherePublished()
                ->with('options')
                ->get()
                ->filter(fn (CustomField $field) => self::isGenericQualificationField($field))
                ->pluck('id')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    public static function genericQualificationFieldNames(): array
    {
        $names = [];

        foreach (self::genericQualificationFieldIds() as $id) {
            $names[] = 'contact_custom_fields[' . $id . ']';
            $names[] = 'open_custom_field_' . $id . '_wrapper_column_wrapper';
            $names[] = 'close_custom_field_' . $id . '_wrapper_column_wrapper';
            $names[] = 'open_custom_field_' . $id . '_column_wrapper';
            $names[] = 'close_custom_field_' . $id . '_column_wrapper';
        }

        return $names;
    }

    public static function isGenericQualificationField(CustomField $field): bool
    {
        $parts = [
            (string) $field->name,
            (string) $field->placeholder,
        ];

        try {
            foreach ($field->options as $option) {
                $parts[] = (string) ($option->label ?? '');
                $parts[] = (string) ($option->value ?? '');
            }
        } catch (\Throwable) {
            // Options may be unavailable during tests.
        }

        return self::looksLikeGenericQualificationText(implode(' ', $parts));
    }

    public static function looksLikeGenericQualificationText(string $text): bool
    {
        $haystack = mb_strtolower($text);

        foreach (self::FORBIDDEN_INQUIRY_LABELS as $label) {
            if (str_contains($haystack, mb_strtolower($label))) {
                return true;
            }
        }

        return false;
    }

    private static function introHtml(): string
    {
        $title = e(self::SUBJECT);
        $new = e(self::INQUIRY_NEW);
        $refi = e(self::INQUIRY_REFINANCE);

        return <<<HTML
<div class="serik-mortgage-form-intro" role="status">
    <h2 class="serik-mortgage-form-title">{$title}</h2>
    <p class="serik-mortgage-form-copy">Request a mortgage pre-qualification. Select {$new} or {$refi}.</p>
</div>
<style>
.serik-mortgage-form-intro{margin:0 0 1.25rem}
.serik-mortgage-form-title{margin:0 0 .35rem;font-size:1.5rem;line-height:1.3}
.serik-mortgage-form-copy{margin:0;color:#4b5563}
.serik-mortgage-inquiry-type .radio,
.serik-mortgage-inquiry-type label{display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;align-items:center}
.serik-mortgage-calculator-form.serik-mortgage-busy .tf-btn,
.serik-mortgage-calculator-form.serik-mortgage-busy .contact-button{pointer-events:none}
</style>
<script>
(function(){
  document.addEventListener('submit', function(e){
    var f = e.target;
    if (!(f instanceof HTMLFormElement) || !f.classList.contains('serik-mortgage-calculator-form')) return;
    if (f.classList.contains('serik-mortgage-busy')) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return;
    }
    f.classList.add('serik-mortgage-busy');
    var b = f.querySelector('button[type="submit"],input[type="submit"]');
    if (b) { b.classList.add('button-loading','btn-loading'); }
    setTimeout(function(){
      f.classList.remove('serik-mortgage-busy');
      if (b) { b.classList.remove('button-loading','btn-loading'); }
    }, 8000);
  }, true);
})();
</script>
HTML;
    }
}
