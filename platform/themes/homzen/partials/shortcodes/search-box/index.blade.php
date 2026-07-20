@php
    use Botble\RealEstate\Facades\RealEstateHelper;
    use Botble\Theme\Facades\ThemeOption;

    $type = $type ?? 'properties';
    $showAdvanced = $showAdvanced ?? 'yes';
    $marginTop = $marginTop ?? 0;
    $marginBottom = $marginBottom ?? 0;

    $actionUrl = $type === 'projects'
        ? RealEstateHelper::getProjectsListPageUrl()
        : RealEstateHelper::getPropertiesListPageUrl();

    $searchType = $type === 'projects' ? 'projects' : 'properties';

    $style = 2;

    if ($showAdvanced === 'no') {
        $originalAdvancedOption = theme_option('real_estate_enable_advanced_search');
        ThemeOption::setOption('real_estate_enable_advanced_search', 'no');
    }

    $inlineStyle = '';
    if ($marginTop > 0) {
        $inlineStyle .= "margin-top: {$marginTop}px;";
    }
    if ($marginBottom > 0) {
        $inlineStyle .= "margin-bottom: {$marginBottom}px;";
    }
@endphp

<div class="search-box-shortcode" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
    <form action="{{ $actionUrl }}" method="get" class="search-box-form">
        @if($searchType === 'properties')
            @include(Theme::getThemeNamespace('views.real-estate.partials.filters.property-search-box'), ['style' => $style])
        @else
            @include(Theme::getThemeNamespace('views.real-estate.partials.filters.project-search-box'), ['style' => $style])
        @endif
    </form>
</div>

@php
    if (isset($originalAdvancedOption)) {
        ThemeOption::setOption('real_estate_enable_advanced_search', $originalAdvancedOption);
    }
@endphp
