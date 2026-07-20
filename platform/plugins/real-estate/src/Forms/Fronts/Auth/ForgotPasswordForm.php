<?php

namespace Botble\RealEstate\Forms\Fronts\Auth;

use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\RealEstate\Http\Requests\Fronts\Auth\ForgotPasswordRequest;

class ForgotPasswordForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('public.account.password.email'))
            ->setValidatorClass(ForgotPasswordRequest::class)
            ->icon('ti ti-lock-question')
            ->heading(trans('plugins/real-estate::account.forgot_password_heading'))
            ->description(trans('plugins/real-estate::account.forgot_password_description'))
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/real-estate::account.email'))
                    ->placeholder(trans('plugins/real-estate::account.email_address'))
                    ->icon('ti ti-mail')
                    ->toArray()
            )
            ->add('redirect', HtmlField::class, [
                'html' => '<input type="hidden" name="redirect" value="' . url()->previous() . '">',
            ])
            ->submitButton(trans('plugins/real-estate::account.send_password_reset_link'))
            ->add('back_to_login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center"><a href="%s" class="text-decoration-underline">%s</a></div>',
                    route('public.account.login'),
                    trans('plugins/real-estate::account.back_to_login_page')
                ),
            ]);
    }
}
