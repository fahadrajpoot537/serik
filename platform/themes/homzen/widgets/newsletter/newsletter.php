<?php

use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Newsletter\Forms\Fronts\NewsletterForm;
use Botble\Widget\AbstractWidget;
use Botble\Widget\Forms\WidgetForm;
use Illuminate\Support\Collection;

class NewsletterWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Newsletter form'),
            'description' => __('Display Newsletter form on sidebar'),
            'title' => null,
            'subtitle' => null,
            // Captcha + CSRF must stay fresh — never cache this widget HTML.
            'enable_caching' => 'no',
        ]);
    }

    protected function data(): array|Collection
    {
        $form = NewsletterForm::create()
            ->formClass('subscribe-form serik-newsletter-form', true)
            ->modify('wrapper_before', HtmlField::class, HtmlFieldOption::make()->content('<div class="serik-newsletter-form__row">'))
            ->addBefore(
                'email',
                'icon',
                HtmlField::class,
                HtmlFieldOption::make()->content('<span class="serik-newsletter-form__icon icon-mail" aria-hidden="true"></span>')
            )
            ->modify('email', 'email', [
                'attr' => [
                    'class' => 'serik-newsletter-form__input',
                    'autocomplete' => 'email',
                    'aria-label' => __('Email address'),
                ],
            ])
            ->modify('submit', 'submit', [
                'attr' => [
                    'class' => 'serik-newsletter-form__submit',
                    'title' => __('Subscribe'),
                    'aria-label' => __('Subscribe'),
                ],
                'label' => '<i class="icon icon-send" aria-hidden="true"></i>',
            ])
            ->modify(
                'wrapper_after',
                HtmlField::class,
                HtmlFieldOption::make()->content('</div>')
            )
            ->setFormEndKey('messages');

        $siteKey = \Theme\homzen\Supports\RecaptchaHelper::siteKey();
        if ($siteKey !== '' && ! $form->has('serik_newsletter_recaptcha')) {
            $form->addBefore(
                'messages',
                'serik_newsletter_recaptcha',
                HtmlField::class,
                HtmlFieldOption::make()->content(
                    '<div class="serik-newsletter-recaptcha mb-2">'
                    . '<div id="newsletterRecaptcha" class="js-serik-newsletter-recaptcha"></div>'
                    . '</div>'
                )
            );
        }

        return compact('form');
    }

    protected function settingForm(): WidgetForm|string|null
    {
        $form = WidgetForm::createFromArray($this->getConfig())
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
                    ->toArray(),
            )
            ->add(
                'subtitle',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(__('Subtitle'))
                    ->toArray(),
            );

        return $form;
    }

    protected function requiredPlugins(): array
    {
        return ['newsletter'];
    }
}
