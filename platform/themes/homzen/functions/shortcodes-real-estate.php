<?php

use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\UiSelectorFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\UiSelectorField;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Models\Project;
use Botble\Shortcode\Compilers\Shortcode as ShortcodeCompiler;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Theme\Facades\Theme;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Theme\homzen\Actions\GetProjectsAction;
use Theme\homzen\Actions\GetPropertiesAction;
use Theme\homzen\Forms\ShortcodeForm;
use Theme\homzen\Supports\VisitorCityHelper;

app()->booted(function (): void {
    if (!is_plugin_active('real-estate')) {
        return;
    }

    Event::listen(RouteMatched::class, function (): void {
        Shortcode::register('properties', __('Properties'), __('Properties'), function (ShortcodeCompiler $shortcode): ?string {
            $tabs = collect();

            $categoryIds = Shortcode::fields()->getIds('category_ids', $shortcode) ?: [];

            \Illuminate\Support\Facades\Log::info('[shortcode:properties] render start', [
                'shortcode' => 'properties',
                'style' => $shortcode->style,
                'attributes' => [
                    'style' => $shortcode->style,
                    'title' => $shortcode->title,
                    'limit' => $shortcode->limit,
                    'type' => $shortcode->type,
                    'is_featured' => $shortcode->is_featured,
                    'category_ids' => $categoryIds,
                ],
                'renderer' => $shortcode->style == 5
                    ? \App\Actions\HomepageFeaturedPropertiesAction::class
                    : \Theme\homzen\Actions\GetPropertiesAction::class,
            ]);

            if ($shortcode->style == 5) {
                $limit = (int) $shortcode->limit ?: 8;
                try {
                    if (class_exists(\App\Actions\HomepageFeaturedPropertiesAction::class)) {
                        $result = app(\App\Actions\HomepageFeaturedPropertiesAction::class)->handle($limit);
                    } else {
                        $result = app(\Theme\homzen\Actions\GetHomepageFeaturedPropertiesAction::class)->handle($limit);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[shortcode:properties] style=5 uncaught', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $result = [
                        'propertiesForSale' => collect(),
                        'propertiesSold' => collect(),
                        'visitorCity' => null,
                    ];
                }

                $propertiesForSale = $result['propertiesForSale'] ?? collect();
                $propertiesSold = $result['propertiesSold'] ?? collect();
                $visitorCity = $result['visitorCity'] ?? null;
                $properties = $propertiesForSale;

                $view = 'shortcodes.properties.index';

                \Illuminate\Support\Facades\Log::info('[shortcode:properties] rendering view', [
                    'view' => $view,
                    'style' => 5,
                    'for_sale_count' => is_countable($propertiesForSale) ? count($propertiesForSale) : 0,
                    'sold_count' => is_countable($propertiesSold) ? count($propertiesSold) : 0,
                ]);

                try {
                    return Theme::partial($view, compact(
                        'shortcode',
                        'properties',
                        'propertiesForSale',
                        'propertiesSold',
                        'tabs',
                        'categoryIds',
                        'visitorCity'
                    ));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[shortcode:properties] style=5 view failed', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'class' => $e::class,
                    ]);

                    // Soft-fail: never blank the homepage section.
                    return '<section class="flat-section"><div class="container text-center py-4">'
                        . '<p class="mb-3">Featured properties are temporarily unavailable.</p>'
                        . '<a class="tf-btn primary size-1" href="' . e(url('/properties')) . '">Browse all properties</a>'
                        . '</div></section>';
                }
            }

            try {
                if ($shortcode->style == 2 && $categoryIds) {
                    $tabs = Category::query()
                        ->wherePublished()
                        ->whereIn('id', $categoryIds)
                        ->get()
                        ->mapWithKeys(fn($item) => [$item->id => $item->name]);

                    $properties = app(GetPropertiesAction::class)
                        ->handle(
                            limit: (int) $shortcode->limit ?: 4,
                            type: $shortcode->type,
                            featured: (bool) $shortcode->is_featured,
                            categoryIds: $categoryIds
                        );
                } else {
                    $properties = app(GetPropertiesAction::class)->handle(
                        limit: (int) $shortcode->limit ?: 4,
                        type: $shortcode->type ?: null,
                        featured: in_array((string) $shortcode->is_featured, ['1', 'true', 'yes'], true),
                        categoryIds: $categoryIds
                    );
                }

                \Illuminate\Support\Facades\Log::info('[shortcode:properties] rendering view', [
                    'view' => 'shortcodes.properties.index',
                    'style' => $shortcode->style,
                    'property_count' => $properties->count(),
                ]);

                return Theme::partial('shortcodes.properties.index', compact('shortcode', 'properties', 'tabs', 'categoryIds'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('[shortcode:properties] non-style5 failed', [
                    'style' => $shortcode->style,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'class' => $e::class,
                ]);

                return '<section class="flat-section"><div class="container text-center py-4">'
                    . '<p class="mb-3">Properties are temporarily unavailable.</p>'
                    . '<a class="tf-btn primary size-1" href="' . e(url('/properties')) . '">Browse all properties</a>'
                    . '</div></section>';
            }
        });

        Shortcode::setPreviewImage('properties', Theme::asset()->url('images/shortcodes/properties/style-2.png'));

        Shortcode::setAdminConfig('properties', function (array $attributes): ShortcodeForm {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->add(
                    'style',
                    UiSelectorField::class,
                    UiSelectorFieldOption::make()
                        ->label(__('Style'))
                        ->choices(
                            collect(range(1, 7))
                                ->mapWithKeys(fn($number) => [
                                    $number => [
                                        'label' => __('Style :number', ['number' => $number]),
                                        'image' => Theme::asset()->url("images/shortcodes/properties/style-$number.png"),
                                    ],
                                ])
                                ->all()
                        )
                        ->selected(Arr::get($attributes, 'style', 1))
                        ->defaultValue(1)
                )
                ->addSectionHeadingFields()
                ->add(
                    'category_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Categories'))
                        ->helperText(__('Select categories to display as tabs.'))
                        ->multiple()
                        ->searchable()
                        ->choices(
                            Category::query()
                                ->wherePublished()
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->selected(explode(',', Arr::get($attributes, 'category_ids', '')))
                )
                ->add(
                    'type',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->choices(['' => __('All'), ...PropertyTypeEnum::labels()]),
                )
                ->add(
                    'is_featured',
                    OnOffField::class,
                    OnOffFieldOption::make()
                        ->label(__('Only show featured properties'))
                )
                ->addLimitField()
                ->addSectionButtonAction()
                ->add(
                    'background_image',
                    MediaImageField::class,
                    MediaImageFieldOption::make()
                        ->label(__('Background Image'))
                        ->helperText(__('Select background image for this section.'))
                        ->collapsible('style', '6', Arr::get($attributes, 'style', '1'))
                )
                ->addBackgroundColorField();
        });

        Shortcode::register('projects', __('Projects'), __('Projects'), function (ShortcodeCompiler $shortcode): ?string {
            if (!RealEstateHelper::isEnabledProjects()) {
                return null;
            }

            $tabs = collect();

            $categoryIds = Shortcode::fields()->getIds('category_ids', $shortcode) ?: [];

            if ($shortcode->style == 2 && $categoryIds) {
                $tabs = Category::query()
                    ->wherePublished()
                    ->whereIn('id', $categoryIds)
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->id => $item->name]);

                $projects = app(GetProjectsAction::class)
                    ->handle(
                        limit: (int) $shortcode->limit ?: 4,
                        featured: (bool) $shortcode->is_featured,
                        categoryIds: $categoryIds
                    );
            } else {
                $projects = (new GetProjectsAction())->handle(
                    limit: (int) $shortcode->limit ?: 4,
                    featured: (bool) $shortcode->is_featured,
                    categoryIds: $categoryIds
                );
            }

            return Theme::partial('shortcodes.projects.index', compact('shortcode', 'projects', 'tabs', 'categoryIds'));
        });

        Shortcode::setPreviewImage('projects', Theme::asset()->url('images/shortcodes/properties/style-2.png'));

        Shortcode::setAdminConfig('projects', function (array $attributes): ShortcodeForm {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->add(
                    'style',
                    UiSelectorField::class,
                    UiSelectorFieldOption::make()
                        ->label(__('Style'))
                        ->choices(
                            collect(range(1, 7))
                                ->mapWithKeys(fn($number) => [
                                    $number => [
                                        'label' => __('Style :number', ['number' => $number]),
                                        'image' => Theme::asset()->url("images/shortcodes/properties/style-$number.png"),
                                    ],
                                ])
                                ->all()
                        )
                        ->selected(Arr::get($attributes, 'style', 1))
                        ->defaultValue(1)
                )
                ->addSectionHeadingFields()
                ->add(
                    'category_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Categories'))
                        ->helperText(__('Select categories to display as tabs.'))
                        ->multiple()
                        ->searchable()
                        ->choices(
                            Category::query()
                                ->wherePublished()
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->selected(explode(',', Arr::get($attributes, 'category_ids', '')))
                )
                ->add(
                    'is_featured',
                    OnOffField::class,
                    OnOffFieldOption::make()
                        ->label(__('Only show featured projects'))
                )
                ->addLimitField()
                ->addSectionButtonAction()
                ->add(
                    'background_image',
                    MediaImageField::class,
                    MediaImageFieldOption::make()
                        ->label(__('Background Image'))
                        ->helperText(__('Select background image for this section.'))
                        ->collapsible('style', '6', Arr::get($attributes, 'style', '1'))
                )
                ->addBackgroundColorField();
        });

        Shortcode::register('property-categories', __('Property Categories'), __('Property Categories'), function (ShortcodeCompiler $shortcode): ?string {
            if (!$categoryIds = Shortcode::fields()->getIds('category_ids', $shortcode)) {
                return null;
            }

            $categories = Category::query()
                ->wherePublished()
                ->whereIn('id', $categoryIds)
                ->with('slugable')
                ->withCount('properties')
                ->get();

            if ($categories->isEmpty()) {
                return null;
            }

            return Theme::partial('shortcodes.property-categories.index', compact('shortcode', 'categories'));
        });

        Shortcode::setPreviewImage('property-categories', Theme::asset()->url('images/shortcodes/property-categories/style-1.png'));

        Shortcode::setAdminConfig('property-categories', function (array $attributes): ShortcodeForm {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->add(
                    'style',
                    UiSelectorField::class,
                    UiSelectorFieldOption::make()
                        ->label(__('Style'))
                        ->choices([
                            1 => [
                                'label' => __('Style :number', ['number' => 1]),
                                'image' => Theme::asset()->url('images/shortcodes/property-categories/style-1.png'),
                            ],
                            2 => [
                                'label' => __('Style :number', ['number' => 2]),
                                'image' => Theme::asset()->url('images/shortcodes/property-categories/style-2.png'),
                            ],
                            3 => [
                                'label' => __('Grid'),
                                'image' => Theme::asset()->url('images/shortcodes/property-categories/style-3.png'),
                            ],
                        ])
                        ->selected(Arr::get($attributes, 'style', 1))
                        ->defaultValue(1)
                )
                ->addSectionHeadingFields()
                ->add(
                    'category_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Categories'))
                        ->multiple()
                        ->searchable()
                        ->choices(
                            Category::query()
                                ->wherePublished()
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->selected(explode(',', Arr::get($attributes, 'category_ids', '')))
                )
                ->addBackgroundColorField()
                ->addSectionButtonAction()
                ->addSliderFields();
        });

        Shortcode::register('pricing-plan', __('Pricing Plan'), __('Pricing Plan'), function (ShortcodeCompiler $shortcode): ?string {
            if (!$packageIds = Shortcode::fields()->getIds('package_ids', $shortcode)) {
                return null;
            }

            $packages = Package::query()
                ->wherePublished()
                ->whereIn('id', $packageIds)
                ->get();

            if ($packages->isEmpty()) {
                return null;
            }

            return Theme::partial('shortcodes.pricing-plan.index', compact('shortcode', 'packages'));
        });

        Shortcode::setPreviewImage('pricing-plan', Theme::asset()->url('images/shortcodes/pricing-plan.png'));

        Shortcode::setAdminConfig('pricing-plan', function (array $attributes): ShortcodeForm {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->addSectionHeadingFields()
                ->add(
                    'package_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Packages'))
                        ->multiple()
                        ->searchable()
                        ->choices(
                            Package::query()
                                ->wherePublished()
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->selected(explode(',', Arr::get($attributes, 'package_ids', '')))
                );
        });

        Shortcode::register('agents', __('Agents'), __('Agents'), function (ShortcodeCompiler $shortcode): ?string {
            if (!$accountIds = Shortcode::fields()->getIds('account_ids', $shortcode)) {
                return null;
            }

            $accounts = Account::query()
                ->whereIn('id', $accountIds)
                ->withCount('properties')
                ->orderByDesc('is_featured')
                ->oldest('first_name')
                ->get();

            if ($accounts->isEmpty()) {
                return null;
            }

            return Theme::partial('shortcodes.agents.index', compact('shortcode', 'accounts'));
        });

        Shortcode::setPreviewImage('agents', Theme::asset()->url('images/shortcodes/agents/style-1.png'));

        Shortcode::setAdminConfig('agents', function (array $attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->add(
                    'style',
                    UiSelectorField::class,
                    UiSelectorFieldOption::make()
                        ->choices(
                            collect(range(1, 2))
                                ->mapWithKeys(fn($number) => [
                                    $number => [
                                        'label' => __('Style :number', ['number' => $number]),
                                        'image' => Theme::asset()->url("images/shortcodes/agents/style-$number.png"),
                                    ],
                                ])
                                ->all()
                        )
                )
                ->addSectionHeadingFields()
                ->add(
                    'account_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->searchable()
                        ->multiple()
                        ->choices(Account::query()->pluck('username', 'id')->all())
                        ->selected(explode(',', Arr::get($attributes, 'account_ids', '')))
                        ->label(__('Choose agents'))
                )
                ->add(
                    'items_per_row',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Items per row'))
                        ->defaultValue(4)
                        ->collapsible('style', '1', Arr::get($attributes, 'style', '1'))
                )
                ->addBackgroundColorField();
        });

        Shortcode::register('featured-projects', __('Featured projects'), __('Featured projects'), function (ShortcodeCompiler $shortcode) {
            if (!RealEstateHelper::isEnabledProjects()) {
                return null;
            }

            $projects = collect();

            if (is_plugin_active('real-estate')) {
                $projects = Project::query()
                    ->where([
                        're_projects.is_featured' => true,
                    ] + RealEstateHelper::getProjectDisplayQueryConditions())
                    ->with(RealEstateHelper::getProjectRelationsQuery())
                    ->latest('re_projects.created_at')
                    ->limit((int) $shortcode->limit ?: (int) theme_option('number_of_featured_projects', 6))
                    ->get();
            }

            if ($projects->isEmpty()) {
                return null;
            }

            return Theme::partial('shortcodes.featured-projects.index', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'projects' => $projects,
                'shortcode' => $shortcode,
            ]);
        });

        if (RealEstateHelper::isEnabledProjects()) {
            Shortcode::setAdminConfig('featured-projects', function (array $attributes) {
                return ShortcodeForm::createFromArray($attributes)
                    ->lazyLoading()
                    ->addSectionHeadingFields()
                    ->addLimitField()
                    ->addBackgroundColorField(defaultValue: '#f7f7f7');
            });
        }

        Shortcode::register('search-box', __('Search Box'), __('Search box for properties and projects'), function (ShortcodeCompiler $shortcode): ?string {
            return Theme::partial('shortcodes.search-box.index', [
                'shortcode' => $shortcode,
                'type' => $shortcode->type ?: 'properties',
                'showAdvanced' => $shortcode->show_advanced ?: 'yes',
                'marginTop' => $shortcode->margin_top ?: 0,
                'marginBottom' => $shortcode->margin_bottom ?: 0,
            ]);
        });

        Shortcode::setAdminConfig('search-box', function (array $attributes): ShortcodeForm {
            return ShortcodeForm::createFromArray($attributes)
                ->lazyLoading()
                ->add(
                    'type',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Search Type'))
                        ->choices([
                            'properties' => __('Properties'),
                            'projects' => __('Projects'),
                        ])
                        ->defaultValue('properties')
                        ->selected(Arr::get($attributes, 'type', 'properties'))
                )
                ->add(
                    'show_advanced',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Show Advanced Search'))
                        ->choices([
                            'yes' => __('Yes'),
                            'no' => __('No'),
                        ])
                        ->defaultValue('yes')
                        ->selected(Arr::get($attributes, 'show_advanced', 'yes'))
                )
                ->add(
                    'margin_top',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Margin Top (px)'))
                        ->helperText(__('Space above the search box in pixels'))
                        ->defaultValue(0)
                        ->value(Arr::get($attributes, 'margin_top', 0))
                )
                ->add(
                    'margin_bottom',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Margin Bottom (px)'))
                        ->helperText(__('Space below the search box in pixels'))
                        ->defaultValue(0)
                        ->value(Arr::get($attributes, 'margin_bottom', 0))
                );
        });
    });
});
