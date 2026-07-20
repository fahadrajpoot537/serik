<?php

namespace Botble\RealEstate\Forms\Fronts;

use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Supports\Language;
use Botble\RealEstate\Forms\AccountForm;
use Botble\RealEstate\Forms\Fronts\Auth\Concerns\HasSubmitButton;
use Botble\RealEstate\Http\Requests\SettingRequest;
use Illuminate\Support\Facades\App;

class ProfileForm extends AccountForm
{
    use HasSubmitButton;

    public function setup(): void
    {
        parent::setup();

        $languages = Language::getAvailableLocales();

        $this
            ->setValidatorClass(SettingRequest::class)
            ->contentOnly()
            ->modify('description', 'textarea', [
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->modify('email', 'text', [
                'required' => false,
                'attr' => [
                    'disabled' => true,
                ],
            ], true)
            ->remove([
                'is_change_password',
                'password',
                'password_confirmation',
                'avatar_image',
                'is_featured',
                'is_public_profile',
                'is_blocked',
                'blocked_reason',
            ])
            ->addAfter('dob', 'gender', 'select', [
                'label' => trans('plugins/real-estate::dashboard.gender'),
                'choices' => [
                    'male' => trans('plugins/real-estate::dashboard.gender_male'),
                    'female' => trans('plugins/real-estate::dashboard.gender_female'),
                    'other' => trans('plugins/real-estate::dashboard.gender_other'),
                ],
            ])
            ->when(count($languages) > 1, function (FormAbstract $form) use ($languages): void {
                $languages = collect($languages)
                    ->pluck('name', 'locale')
                    ->map(fn ($item, $key) => $item . ' - ' . $key)
                    ->all();

                $form
                    ->add(
                        'locale',
                        SelectField::class,
                        SelectFieldOption::make()
                            ->label(trans('plugins/real-estate::dashboard.language'))
                            ->choices($languages)
                            ->selected($form->getModel()->getMetaData('locale', true) ?: App::getLocale())
                            ->metadata()
                    );
            })
            ->submitButton(trans('plugins/real-estate::dashboard.save'), isWrapped: false, attributes: [
                'class' => 'btn btn-primary',
            ]);
    }
}
