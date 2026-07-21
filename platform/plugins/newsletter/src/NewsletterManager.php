<?php

namespace Botble\Newsletter;

use Botble\Base\Facades\AdminHelper;
use Botble\Media\Facades\RvMedia;
use Botble\Newsletter\Contracts\Factory;
use Botble\Newsletter\Drivers\MailChimp;
use Botble\Newsletter\Drivers\SendGrid;
use Botble\Theme\Events\RenderingThemeOptionSettings;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Facades\ThemeOption;
use Botble\Theme\ThemeOption\Fields\MediaImageField;
use Botble\Theme\ThemeOption\Fields\MultiCheckListField;
use Botble\Theme\ThemeOption\Fields\NumberField;
use Botble\Theme\ThemeOption\Fields\TextareaField;
use Botble\Theme\ThemeOption\Fields\TextField;
use Botble\Theme\ThemeOption\Fields\ToggleField;
use Botble\Theme\ThemeOption\ThemeOptionSection;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class NewsletterManager extends Manager implements Factory
{
    protected function createMailChimpDriver(): MailChimp
    {
        return new MailChimp(
            setting('newsletter_mailchimp_api_key'),
            setting('newsletter_mailchimp_list_id')
        );
    }

    protected function createSendGridDriver(): SendGrid
    {
        return new SendGrid(
            setting('newsletter_sendgrid_api_key'),
            setting('newsletter_sendgrid_list_id')
        );
    }

    public function getDefaultDriver(): string
    {
        throw new InvalidArgumentException('No email marketing provider was specified.');
    }

    public function registerNewsletterPopup(bool $keepHtmlDomOnClose = false): void
    {
        app('events')->listen(RenderingThemeOptionSettings::class, function (): void {
            ThemeOption::setSection(
                ThemeOptionSection::make('opt-text-subsection-newsletter-popup')
                    ->title(trans('plugins/newsletter::newsletter.newsletter_popup'))
                    ->icon('ti ti-mail-opened')
                    ->fields([
                        ToggleField::make()
                            ->name('newsletter_popup_enable')
                            ->label(trans('plugins/newsletter::newsletter.enable_newsletter_popup')),
                        MediaImageField::make()
                            ->name('newsletter_popup_image')
                            ->label(trans('plugins/newsletter::newsletter.popup_image')),
                        TextField::make()
                            ->name('newsletter_popup_title')
                            ->label(trans('plugins/newsletter::newsletter.popup_title')),
                        TextField::make()
                            ->name('newsletter_popup_subtitle')
                            ->label(trans('plugins/newsletter::newsletter.popup_subtitle')),
                        TextareaField::make()
                            ->name('newsletter_popup_description')
                            ->label(trans('plugins/newsletter::newsletter.popup_description')),
                        NumberField::make()
                            ->name('newsletter_popup_delay')
                            ->label(trans('plugins/newsletter::newsletter.popup_delay_seconds'))
                            ->defaultValue(5)
                            ->helperText(
                                trans('plugins/newsletter::newsletter.popup_delay_helper')
                            )
                            ->attributes([
                                'min' => 0,
                            ]),
                        MultiCheckListField::make()
                            ->name('newsletter_popup_display_pages')
                            ->label(trans('plugins/newsletter::newsletter.display_on_pages'))
                            ->inline()
                            ->defaultValue(['public.index'])
                            ->options(
                                apply_filters('newsletter_popup_display_pages', [
                                    'public.index' => trans('plugins/newsletter::newsletter.homepage'),
                                    'all' => trans('plugins/newsletter::newsletter.all_pages'),
                                ])
                            ),
                    ])
            );
        });

        app('events')->listen(RouteMatched::class, function () use ($keepHtmlDomOnClose): void {
            if (! $this->isNewsletterPopupEnabled($keepHtmlDomOnClose)) {
                return;
            }

            Theme::asset()
                ->container('footer')
                ->add(
                    'newsletter',
                    asset('vendor/core/plugins/newsletter/js/newsletter.js'),
                    ['jquery', 'bootstrap'],
                    version: '1.3.7'
                );

            Theme::asset()
                ->container('header')
                ->add(
                    'newsletter-popup',
                    asset('vendor/core/plugins/newsletter/css/newsletter.css'),
                    [],
                    version: '1.3.7'
                );

            add_filter('theme_front_meta', function (?string $html): string {
                $image = theme_option('newsletter_popup_image');

                if (! $image) {
                    return $html;
                }

                return $html . '<link rel="preload" as="image" href="' . e(\App\Support\SerikMediaUrl::resolvePublic([
                    $image,
                    'newsletter-1.webp',
                    'newsletter.webp',
                    'general/newsletter-image.jpg',
                ])) . '" />';
            });

            add_filter(THEME_FRONT_BODY, function (?string $html): string {
                return $html . view('plugins/newsletter::partials.newsletter-popup');
            });
        });
    }

    protected function isNewsletterPopupEnabled(bool $keepHtmlDomOnClose = false): bool
    {
        $isEnabled = is_plugin_active('newsletter')
            && theme_option('newsletter_popup_enable', false)
            && ($keepHtmlDomOnClose || ! isset($_COOKIE['newsletter_popup']))
            && ! AdminHelper::isInAdmin();

        if (! $isEnabled) {
            return false;
        }

        $displayPages = theme_option('newsletter_popup_display_pages');

        if ($displayPages) {
            $displayPages = json_decode($displayPages, true) ?: ['public.index'];
        } else {
            $displayPages = ['public.index'];
        }

        $routeName = Route::currentRouteName();
        $isHomepage = $routeName === 'public.index' || request()->is('/') || request()->path() === '';

        if (
            ! in_array('all', $displayPages)
            && ! in_array($routeName, $displayPages)
            && ! ($isHomepage && in_array('public.index', $displayPages))
        ) {
            return false;
        }

        $ignoredBots = [
            'googlebot', // Googlebot
            'bingbot', // Microsoft Bingbot
            'slurp', // Yahoo! Slurp
            'ia_archiver', // Alexa
            'Chrome-Lighthouse', // Google Lighthouse
        ];

        if (in_array(strtolower(request()->userAgent()), $ignoredBots)) {
            return false;
        }

        return true;
    }
}
