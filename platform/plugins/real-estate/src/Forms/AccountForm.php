<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Facades\AdminHelper;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\DatePickerFieldOption;
use Botble\Base\Forms\FieldOptions\DescriptionFieldOption;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\InputFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\DatePickerField;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Location\Fields\Options\SelectLocationFieldOption;
use Botble\Location\Fields\SelectLocationField;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\AccountCreateRequest;
use Botble\RealEstate\Models\Account;
use Carbon\Carbon;

class AccountForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addStylesDirectly('vendor/core/plugins/real-estate/css/account-admin.css')
            ->addScriptsDirectly(['/vendor/core/plugins/real-estate/js/account-admin.js']);

        $this
            ->model(Account::class)
            ->setValidatorClass(AccountCreateRequest::class)
            ->template('plugins/real-estate::account.admin.form')
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.first_name'))
                    ->required()
                    ->placeholder(trans('plugins/real-estate::account.first_name'))
                    ->maxLength(120)
                    ->toArray()
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.last_name'))
                    ->required()
                    ->placeholder(trans('plugins/real-estate::account.last_name'))
                    ->maxLength(120)
                    ->toArray()
            )
            ->add(
                'username',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.username'))
                    ->required()
                    ->placeholder(trans('plugins/real-estate::account.username_placeholder'))
                    ->maxLength(120)
                    ->toArray()
            )
            ->add(
                'company',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.company'))
                    ->placeholder(trans('plugins/real-estate::account.company_placeholder'))
                    ->maxLength(255)
                    ->toArray()
            )
            ->add('description', TextareaField::class, DescriptionFieldOption::make()->toArray())
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.phone'))
                    ->placeholder(trans('plugins/real-estate::account.phone_placeholder'))
                    ->maxLength(20)
                    ->toArray()
            )
            ->add(
                'whatsapp',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.whatsapp'))
                    ->placeholder(trans('plugins/real-estate::account.whatsapp_placeholder'))
                    ->maxLength(25)
                    ->toArray()
            )
            ->add(
                'dob',
                DatePickerField::class,
                DatePickerFieldOption::make()
                    ->label(trans('plugins/real-estate::account.dob'))
                    ->defaultValue(BaseHelper::formatDate(Carbon::now()))
                    ->toArray()
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.email'))
                    ->required()
                    ->placeholder(trans('plugins/real-estate::account.email_placeholder'))
                    ->maxLength(60)
                    ->toArray()
            )
            ->when(is_plugin_active('location'), function (FormAbstract $form): void {
                // Default the location to Canada when the account has no saved country (admin can change it).
                $account = $form->getModel();
                if (empty($account->country_id)) {
                    $canadaId = \Botble\Location\Models\Country::query()
                        ->where('name', 'Canada')
                        ->value('id');

                    if ($canadaId) {
                        $account->country_id = $canadaId;
                    }
                }

                $form->add(
                    'location_data',
                    SelectLocationField::class,
                    SelectLocationFieldOption::make()->toArray()
                );
            })
            ->add(
                'is_change_password',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.change_password'))
                    ->defaultValue(false)
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '#change-password',
                    ])
                    ->toArray()
            )
            ->add(
                'openRow',
                HtmlField::class,
                HtmlFieldOption::make()
                    ->content('<div id="change-password" class="row"' . ($this->getModel()->id ? ' style="display: none"' : null) . '>')
                    ->toArray()
            )
            ->add(
                'password',
                PasswordField::class,
                InputFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password'))
                    ->required()
                    ->addAttribute('data-counter', 60)
                    ->wrapperAttributes([
                        'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6',
                    ])
                    ->toArray()
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                InputFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password_confirmation'))
                    ->required()
                    ->addAttribute('data-counter', 60)
                    ->wrapperAttributes([
                        'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6',
                    ])
                    ->toArray()
            )
            ->add(
                'closeRow',
                HtmlField::class,
                HtmlFieldOption::make()
                    ->content('</div>')
                    ->toArray()
            )
            ->add(
                'avatar_image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(trans('plugins/real-estate::dashboard.profile-picture'))
                    ->value($this->getModel()->avatar->url)
            )
            ->when(AdminHelper::isInAdmin(true), function (FormAbstract $form): void {
                $form
                    ->add(
                        'is_featured',
                        OnOffField::class,
                        OnOffFieldOption::make()
                        ->label(trans('core/base::forms.is_featured'))
                        ->defaultValue(false)
                        ->toArray()
                    )
                    ->add(
                        'is_blocked',
                        OnOffField::class,
                        OnOffFieldOption::make()
                            ->label(trans('plugins/real-estate::account.form.is_blocked'))
                            ->defaultValue($this->getModel()->is_blocked ?? false)
                            ->attributes([
                                'data-bb-toggle' => 'collapse',
                                'data-bb-target' => '#blocked-reason-section',
                            ])
                            ->toArray()
                    )
                    ->add(
                        'blocked_reason',
                        TextareaField::class,
                        DescriptionFieldOption::make()
                            ->label(trans('plugins/real-estate::account.form.blocked_reason'))
                            ->placeholder(trans('plugins/real-estate::account.form.blocked_reason_placeholder'))
                            ->addAttribute('rows', 3)
                            ->addAttribute('data-counter', 500)
                            ->toArray()
                    );
            })
            ->when(! RealEstateHelper::isDisabledPublicProfile(), function (FormAbstract $form): void {
                $form->add(
                    'is_public_profile',
                    OnOffField::class,
                    OnOffFieldOption::make()
                        ->label(trans('plugins/real-estate::account.form.is_public_profile'))
                        ->defaultValue(false)
                        ->toArray()
                );
            })
            ->when($this->getModel()->id && RealEstateHelper::isEnabledCreditsSystem(), function (FormAbstract $form): void {
                /**
                 * @var Account $account
                 */
                $account = $this->getModel();

                $form->addMetaBoxes([
                    'credits' => [
                        'attributes' => [
                            'id' => 'credit-histories',
                        ],
                        'title' => trans('plugins/real-estate::account.transactions'),
                        'subtitle' => trans('plugins/real-estate::account.credits_count', ['count' => number_format($this->getModel()->credits)]),
                        'header_actions' => view(
                            'plugins/real-estate::partials.forms.header-actions.button',
                            [
                                'className' => 'btn-trigger-add-credit',
                                'label' => trans('plugins/real-estate::account.manual_transaction'),
                            ]
                        )->render(),
                        'content' => view('plugins/real-estate::account.admin.credits', [
                            'account' => $account,
                            'transactions' => $account->transactions()->latest()->get(),
                        ])->render(),
                    ],
                ]);
            })
            ->setBreakFieldPoint('avatar_image');
    }
}
