<?php

namespace Botble\RealEstate\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PhoneNumberField;

use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\TextField;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\RealEstate\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\RealEstate\Models\Account;

class RegisterForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('public.account.register.post'))
            ->setValidatorClass(RegisterRequest::class)
            ->icon('ti ti-user-plus')
            ->heading(trans('plugins/real-estate::account.register_heading'))
            ->description(trans('plugins/real-estate::account.register_description'))

            ->when(
                theme_option('register_background'),
                fn (AuthForm $form, string $background) => $form->banner($background)
            )

            /*
            |--------------------------------------------------------------------------
            | STEP 1
            |--------------------------------------------------------------------------
            */
            
            
            /*
|--------------------------------------------------------------------------
| STEP 1 - EMAIL
|--------------------------------------------------------------------------
*/

->add('step_1_start', HtmlField::class, [
    'html' => '<div class="form-step" data-step="1">',
])

->add(
    'email',
    EmailField::class,
    EmailFieldOption::make()
        ->label(trans('plugins/real-estate::account.email'))
        ->placeholder(trans('plugins/real-estate::account.email_placeholder'))
        ->icon('ti ti-mail')
        ->required()
)
 ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password'))
                    ->placeholder(trans('plugins/real-estate::account.form.password'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password_confirmation'))
                    ->placeholder(trans('plugins/real-estate::account.form.password_confirmation'))
                    ->icon('ti ti-lock')
                    ->required()
            )

->add('step_1_next', HtmlField::class, [
    'html' => '<button type="button" class="btn btn-primary next-step disabled">Next</button></div>',
])
   
   
   

/*
|--------------------------------------------------------------------------
| STEP 2 - USER INFO
|--------------------------------------------------------------------------
*/

->add('step_2_start', HtmlField::class, [
    'html' => '<div class="form-step d-none" data-step="2">',
])

->add(
    'first_name',
    TextField::class,
    TextFieldOption::make()
        ->label('Name')
        ->placeholder('Enter Your Name')
        ->icon('ti ti-user')
        ->addAttribute('id', 'first_name')
        ->required()
)

->add(
    'phone',
    PhoneNumberField::class,
    TextFieldOption::make()
        ->label(trans('plugins/real-estate::account.phone'))
        ->required()
        ->placeholder(trans('plugins/real-estate::account.phone_placeholder'))
        ->addAttribute('autocomplete', 'tel')
        ->addAttribute('type', 'tel')
        ->addAttribute('id', 'register-phone')
        ->toArray()
)


 ->when(!setting('real_estate_hide_username_in_registration_page', false), function (): void {
                $this->add(
                    'username',
                    TextField::class,
                    TextFieldOption::make()
                        ->placeholder(trans('plugins/real-estate::account.username'))
                        ->addAttribute('id', 'username')
                        ->addAttribute('style', 'display:none')
                );
            })


->add('step_2_next', HtmlField::class, [
    'html' => '
        <button type="button" class="btn btn-secondary prev-step">Previous</button>
        <button type="button" class="btn btn-primary next-step disabled">Next</button>
    </div>',
])         
            

/*
|--------------------------------------------------------------------------
| STEP 3 - TERMS
|--------------------------------------------------------------------------
*/

->add('step_3_start', HtmlField::class, [
    'html' => '<div class="form-step d-none" data-step="3">',
])


->add('terms_content', HtmlField::class, [
                'html' => '
                <div>

                <textarea style="width:100%; height:350px; margin-bottom:20px;" readonly>
Terms of Use
MLS®, VOW, CREA, TRREB, and PropTX Compliance

CREA Compliance
All listings on this Site are sourced from the Canadian Real Estate Association (CREA) MLS® database.

Listings are provided exclusively for personal, non-commercial use by individuals with a bona fide interest in buying, selling, or leasing real estate.

Users are prohibited from copying, redistributing, retransmitting, or otherwise using MLS® data outside the scope of evaluating a specific property.

TRREB Compliance
The Toronto Regional Real Estate Board (TRREB) maintains proprietary rights and copyrights over all MLS® data in the GTA.

You may not scrape, mine, redistribute, or sublicense listing information.

TRREB and its authorized representatives may monitor VOW displays to ensure compliance with MLS® rules and policies.

VOW (Virtual Office Website) Use
Serik Realty operates as an affiliated VOW partner providing registered users secure access to MLS® listings.

Personal information (name, email, phone) may be collected and shared with CREA, TRREB, or PropTX for auditing, verification, or legal purposes.

Any agreement creating financial obligations or representation by Serik Realty must be established separately and cannot be accepted merely by using the Site.

PropTX Compliance
Some MLS® listings may be accessed through PropTX which owns its MLS® database and system.

Users must not scrape, mine, redistribute, or manipulate PropTX listing information.

PropTX and authorized representatives may audit the platform to verify compliance.
                </textarea>

              
                </div>
                ',
            ])

->add(
                'agree_terms_and_policy',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->when(
                        $privacyPolicyUrl = theme_option('term_and_privacy_policy_url'),
                        function (CheckboxFieldOption $fieldOption, string $url): void {
                            $fieldOption->label(
                                trans('plugins/real-estate::account.agree_to_link', [
                                    'link' => Html::link(
                                        $url,
                                        trans('plugins/real-estate::account.terms_privacy_policy'),
                                        attributes: [
                                            'class' => 'text-decoration-underline',
                                            'target' => '_blank'
                                        ]
                                    )
                                ])
                            );
                        }
                    )
                    ->when(! $privacyPolicyUrl, function (CheckboxFieldOption $fieldOption): void {
                        $fieldOption->label(trans('plugins/real-estate::account.agree_to_terms'));
                    })
            )

            ->submitButton(trans('plugins/real-estate::account.register'), 'ti ti-arrow-narrow-right')

->add('step_3_prev', HtmlField::class, [
    'html' => '<button type="button" class="btn btn-secondary prev-step" style="margin-top:15px;">Previous</button></div>',
])          
          

           

            
         


            

            


            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Account::class),
            ])

           ->add('login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center">%s <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalLogin" class="text-decoration-underline">%s</a></div>',
                    trans('plugins/real-estate::account.already_have_account'),
                    trans('plugins/real-estate::account.login')
                ),
            ]);
            
            
            
            
    }
}