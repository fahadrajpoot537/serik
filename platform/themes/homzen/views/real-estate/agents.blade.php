@php
    Theme::set('pageTitle', __('Agents'));

    if (theme_option('breadcrumb_background_image_agents')) {
        Theme::set('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image_agents'));
    }
@endphp

<h1 class="d-none">{{ __('Agents') }}</h1>

<section class="flat-section flat-agents">
    <div class="container">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4">
            @foreach($accounts as $account)
                <div class="box col mb-4">
                    <div class="box-agent hover-img wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
                        <div class="box-img img-style mb-2">
                            {{ RvMedia::image($account->avatar_url, $account->name) }}
                            {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}
                        </div>
                        <div class="content">
                            <div class="info">
                                <a href="{{ $account->url }}">
                                    <h6 class="link">{{ $account->name }} {!! $account->badge !!}</h6>
                                </a>
                                {!! Theme::partial('shortcodes.agents.partials.info', compact('account')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $accounts->links(Theme::getThemeNamespace('partials.pagination')) }}
    </div>
</section>
