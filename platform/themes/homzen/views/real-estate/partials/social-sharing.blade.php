@php
    $shareUrl = $model->url ?? null;
    if (! is_string($shareUrl) || $shareUrl === '') {
        $shareUrl = url()->current();
    }
    $shareTitle = trim((string) ($model->name ?? ''));
    $socialSharing = \Botble\Theme\Supports\ThemeSupport::getSocialSharingButtons(
        $shareUrl,
        $shareTitle !== '' ? $shareTitle : 'Property'
    );
@endphp
@if($socialSharing)
    <div class="property-share-social">
        <span>{{ __('Share:') }}</span>
        <ul class="list-social d-flex align-items-center">
            @foreach($socialSharing as $social)
                <li>
                    <a title="{{ $social['name'] }}" href="{{ $social['url'] }}" class="box-icon w-40 social" target="_blank">
                        {!! $social['icon'] !!}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
