<?php

namespace Botble\RealEstate\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\RealEstate\Http\Requests\Fronts\Auth\LoginRequest;
use Botble\RealEstate\Models\Account;

class LoginForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
           ->setUrl(route('public.account.login.post'))
            ->setValidatorClass(LoginRequest::class)
            ->setFormOption('attr', [
                'id' => 'login-form'
            ])
            ->icon('ti ti-lock')
            ->heading(trans('plugins/real-estate::account.login_heading'))
            ->description(trans('plugins/real-estate::account.register_description'))
            ->when(
                theme_option('login_background'),
                fn (AuthForm $form, string $background) => $form->banner($background)
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/real-estate::account.email'))
                    ->placeholder(trans('plugins/real-estate::account.email_placeholder'))
                    ->icon('ti ti-mail')
                    ->toArray()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password'))
                    ->placeholder(trans('plugins/real-estate::account.form.password'))
                    ->icon('ti ti-lock')
                    ->toArray()
            )
            ->add('openRow', HtmlField::class, [
                'html' => '<div class="row g-0 mb-3">',
            ])
            ->add(
                'remember',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/real-estate::account.remember_me'))
                    ->wrapperAttributes(['class' => 'col-6'])
                    ->toArray()
            )
            ->add(
                'forgot_password',
                HtmlField::class,
                [
                    'html' => Html::link(route('public.account.password.request'), trans('plugins/real-estate::account.forgot_password_question'), attributes: ['class' => 'text-decoration-underline']),
                    'wrapper' => [
                        'class' => 'col-6 text-end',
                    ],
                ]
            )
            ->add('closeRow', HtmlField::class, [
                'html' => '</div>',
            ])
            ->submitButton(trans('plugins/real-estate::account.login'), 'ti ti-arrow-narrow-right')
           ->when(RealEstateHelper::isRegisterEnabled(), function (LoginForm $form): void {
                $form->add('register', HtmlField::class, [
                    'html' => sprintf(
                        '<div class="mt-3 text-center">%s <a href="#" data-bs-toggle="modal" data-bs-target="#modalRegister" class="text-decoration-underline">%s</a></div>',
                        trans('plugins/real-estate::account.dont_have_account'),
                        trans('plugins/real-estate::account.register_now')
                    ),
                ]);
            })
            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Account::class),
            ]);
    }
}
