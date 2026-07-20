<div class="agents-grid">

    <div class="agents-grid-wrap">

        <div class="fr-grid-thumb">
            @if ($account->url)
                <a href="{{ $account->url }}">
                    <img src="{{ $account->avatar_url ?: RvMedia::getImageUrl($account->avatar->url, 'medium') }}" class="img-fluid mx-auto" alt="{{ $account->name }}">
                </a>
            @else
                <span>
                    <img src="{{ $account->avatar_url ?: RvMedia::getImageUrl($account->avatar->url, 'medium') }}" class="img-fluid mx-auto" alt="{{ $account->name }}">
                </span>
            @endif
        </div>

        <div class="fr-grid-detail">
            <div class="fr-grid-detail-flex">
                <h5 class="fr-can-name">
                    @if ($account->url)
                        <a href="{{ $account->url }}">{{ $account->name }} {!! $account->badge !!}</a>
                    @else
                        {{ $account->name }} {!! $account->badge !!}
                    @endif
                </h5>
            </div>
            @if ($account->email && ! setting('real_estate_hide_agency_email', 0))
                <div class="fr-grid-detail-flex-right">
                    <div class="agent-email"><a href="mailto:{{ $account->email }}" title="{{ $account->name }}"><i class="fa fa-envelope"></i></a></div>
                </div>
            @endif
        </div>

    </div>

    <div class="fr-grid-info">
        <ul>
            @if ($account->phone && ! setting('real_estate_hide_agency_phone', 0))
                <li><strong class="d-inline-block me-1">{{ trans('plugins/real-estate::agent.phone') }}:</strong>&nbsp;<span dir="ltr">{{ $account->phone }}</span></li>
            @endif

            @if ($account->email && ! setting('real_estate_hide_agency_email', 0))
                <li><strong class="d-inline-block me-1">{{ trans('plugins/real-estate::agent.email') }}:</strong>&nbsp;<span dir="ltr">{{ $account->email }}</span></li>
            @endif
        </ul>
    </div>

    <div class="fr-grid-footer">
        <div class="fr-grid-footer-flex">
            <span class="fr-position"><i class="fa fa-home"></i>&nbsp;{{ trans_choice('plugins/real-estate::agent.properties_count', $account->properties_count, ['count' => $account->properties_count]) }}</span>
        </div>
        @if ($account->url)
            <div class="fr-grid-footer-flex-right">
                <a href="{{ $account->url }}" class="prt-view" tabindex="0">{{ trans('plugins/real-estate::agent.view') }}</a>
            </div>
        @endif
    </div>

</div>
