{!! SeoHelper::render() !!}

{{-- Google SERP: crawlable square favicons ≥48×48 (CMS WhatsApp photo broke SERP icons) --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

@if (Theme::has('headerMeta'))
    {!! Theme::get('headerMeta') !!}
@endif

{!! apply_filters('theme_front_meta', null) !!}

{!! Theme::typography()->renderCssVariables() !!}

{!! Theme::asset()->container('before_header')->styles() !!}
{!! Theme::asset()->styles() !!}
{!! Theme::asset()->container('after_header')->styles() !!}
{!! Theme::asset()->container('header')->scripts() !!}

{!! apply_filters(THEME_FRONT_HEADER, null) !!}

{!! SeoHelper::meta()->getAnalytics()->render() !!}

<script>
    window.siteUrl = "{{ rescue(fn() => BaseHelper::getHomepageUrl()) }}"
</script>
