<?php

namespace Botble\RealEstate\Forms\Fronts;

use Botble\Base\Forms\FieldOptions\ButtonFieldOption;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\InputFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\RealEstate\Enums\ConsultCustomFieldTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\SendConsultRequest;
use Botble\RealEstate\Models\ConsultCustomField;
use Botble\Theme\FormFront;
use Illuminate\Support\Collection;

class ConsultForm extends FormFront
{
    public function setup(): void
    {
        $customFields = ConsultCustomField::query()
            ->wherePublished()
            ->oldest('order')
            ->get();

        $mandatoryFields = RealEstateHelper::enabledMandatoryFieldsAtConsultForm();

        $this
            ->contentOnly()
            ->formClass('consult-form')
            ->setValidatorClass(SendConsultRequest::class)
            ->setUrl(route('public.send.consult'))
            ->add(
                'name',
                TextField::class,
                TextFieldOption::make()
                    ->required()
                    ->label(trans('plugins/real-estate::consult.form_name'))
                    ->placeholder(trans('plugins/real-estate::consult.name_placeholder')),
            )
            ->when(! RealEstateHelper::isHiddenFieldAtConsultForm('phone'), function (ConsultForm $form) use (
                $mandatoryFields
            ): void {
                $form->add(
                    'phone',
                    TextField::class,
                    TextFieldOption::make()
                        ->required(in_array('phone', $mandatoryFields))
                        ->label(trans('plugins/real-estate::consult.form_phone'))
                        ->placeholder(trans('plugins/real-estate::consult.phone_placeholder')),
                );
            })
            ->when(! RealEstateHelper::isHiddenFieldAtConsultForm('email'), function (ConsultForm $form) use (
                $mandatoryFields
            ): void {
                $form->add(
                    'email',
                    TextField::class,
                    TextFieldOption::make()
                        ->required(in_array('email', $mandatoryFields))
                        ->label(trans('plugins/real-estate::consult.form_email'))
                        ->placeholder(trans('plugins/real-estate::consult.email_placeholder')),
                );
            })
            ->when($customFields, function (ConsultForm $form, Collection $customFields): void {
                foreach ($customFields as $customField) {
                    /**
                     * @var ConsultCustomField $customField
                     */
                    $options = $customField->options()->select('id', 'label', 'value')->get()->mapWithKeys(function ($option) {
                        return [$option->value => $option->label];
                    })->all();

                    $fieldOptions = match ($customField->type->getValue()) {
                        ConsultCustomFieldTypeEnum::NUMBER => NumberFieldOption::make()
                            ->when($customField->placeholder, function (InputFieldOption $options, string $placeholder): void {
                                $options->placeholder($placeholder);
                            }),
                        ConsultCustomFieldTypeEnum::DROPDOWN => SelectFieldOption::make()
                            ->when($customField->placeholder, function (SelectFieldOption $fieldOptions, string $placeholder) use ($options): void {
                                $fieldOptions->choices(['' => $placeholder, ...$options]);
                            }, function (SelectFieldOption $fieldOptions) use ($options): void {
                                $fieldOptions->choices($options);
                            }),
                        ConsultCustomFieldTypeEnum::CHECKBOX => CheckboxFieldOption::make(),
                        ConsultCustomFieldTypeEnum::RADIO => RadioFieldOption::make()
                            ->choices($options),
                        default => TextFieldOption::make()
                            ->when($customField->placeholder, function (InputFieldOption $options, string $placeholder): void {
                                $options->placeholder($placeholder);
                            }),
                    };

                    $field = match ($customField->type->getValue()) {
                        ConsultCustomFieldTypeEnum::NUMBER => NumberField::class,
                        ConsultCustomFieldTypeEnum::TEXTAREA => TextareaField::class,
                        ConsultCustomFieldTypeEnum::DROPDOWN => SelectField::class,
                        ConsultCustomFieldTypeEnum::CHECKBOX => OnOffCheckboxField::class,
                        ConsultCustomFieldTypeEnum::RADIO => RadioField::class,
                        ConsultCustomFieldTypeEnum::DATE => 'date',
                        ConsultCustomFieldTypeEnum::DATETIME => 'datetime-local',
                        ConsultCustomFieldTypeEnum::TIME => 'time',
                        default => TextField::class,
                    };

                    $form->add(
                        "consult_custom_fields[$customField->id]",
                        $field,
                        $fieldOptions
                            ->label($customField->name)
                            ->required($customField->required)
                    );
                }
            })
            ->add(
                'content',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->required()
                    ->label(trans('plugins/real-estate::consult.form_message'))
                    ->placeholder(trans('plugins/real-estate::consult.message_placeholder')),
            )
            ->add(
                'form_extra_fields_render',
                HtmlField::class,
                HtmlFieldOption::make()->content(apply_filters('form_extra_fields_render', null, static::class))
            )
            ->add(
                'submit',
                'submit',
                ButtonFieldOption::make()
                    ->label(trans('plugins/real-estate::consult.send_consult'))
            );
    }

    public function renderForm(array $options = [], bool $showStart = true, bool $showFields = true, bool $showEnd = true): string
    {
        if (! RealEstateHelper::isEnabledConsultForm()) {
            return '';
        }

        return parent::renderForm($options, $showStart, $showFields, $showEnd);
    }
}
