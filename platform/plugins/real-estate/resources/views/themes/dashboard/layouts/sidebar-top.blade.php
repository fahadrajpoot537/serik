<div class="ps-block--user-wellcome">
    <div class="ps-block__left">
        <img
            src="{{ auth('account')->user()->avatar_url }}"
            alt="{{ auth('account')->user()->name }}"
            class="avatar avatar-lg"
        />
    </div>
    <div class="ps-block__right">
        <p>{{ trans('plugins/real-estate::dashboard.hello') }}, {{ auth('account')->user()->name }}</p>
        <small>{{ trans('plugins/real-estate::dashboard.joined_on', ['date' => auth('account')->user()->created_at->translatedFormat('M d, Y')]) }}</small>
    </div>
    <div class="ps-block__action">
        <a
            href="{{ route('public.account.logout') }}"
            title="{{ trans('plugins/real-estate::dashboard.header_logout_link') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="d-inline-flex align-items-center gap-1 text-decoration-none"
            style="color: #666; font-size: 14px; font-weight: 600; white-space: nowrap;"
        >
            <x-core::icon name="ti ti-logout" />
            <span>{{ trans('plugins/real-estate::dashboard.header_logout_link') }}</span>
        </a>
    </div>
</div>

@if (RealEstateHelper::isEnabledCreditsSystem())
    <div class="ps-block--earning-count">
        {!! apply_filters('real_estate_account_dashboard_sidebar_top_account_credits_before', null) !!}

        <small>{{ trans('plugins/real-estate::dashboard.credits') }}</small>
        <h3 class="my-2">{{ number_format(auth('account')->user()->credits) }}</h3>
        <a href="{{ route('public.account.packages') }}" target="_blank">
            {{ trans('plugins/real-estate::dashboard.buy_credits') }}
        </a>

        {!! apply_filters('real_estate_account_dashboard_sidebar_top_account_credits_after', null) !!}
    </div>
@endif
