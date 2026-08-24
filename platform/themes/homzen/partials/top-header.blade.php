<style>
.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

/* Top bar responsive polish (UI only) */
.top-header .top-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-width: 0;
    flex-wrap: nowrap;
}
.top-header .top-header-left,
.top-header .top-header-right,
.top-header .serik-hp-topbar__tools {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    flex-wrap: nowrap;
}
.top-header .top-header-left { flex: 1 1 auto; overflow: hidden; }
.top-header .top-header-right { flex: 0 1 auto; justify-content: flex-end; }
.top-header a,
.top-header .serik-hp-topbar__link {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.top-header .serik-hp-topbar__account,
.top-header .serik-hp-topbar__auth { flex-shrink: 0; }
@media (max-width: 1199.98px) {
    .top-header .serik-hp-topbar__tools a:nth-child(n+4) { display: none; }
}
@media (max-width: 991.98px) {
    .top-header .serik-hp-topbar__tools a:nth-child(n+3) { display: none; }
    .top-header .top-header-left .top-header-item:nth-child(n+2) { display: none; }
}
</style>

@php
    $announcements = apply_filters('announcement_display_html', null);
    $canRenderAnnouncements = is_plugin_active('announcement') && $announcements && \ArchiElite\Announcement\Models\Announcement::query()->exists();
@endphp

<div class="top-header serik-hp-topbar">
    <div class="top-header-inner serik-hp-topbar__inner">
        <div class="top-header-left serik-hp-topbar__left">
            @if($canRenderAnnouncements)
                {!! $announcements !!}
            @else
                @if($hotline = theme_option('hotline'))
                    <div class="top-header-item serik-hp-topbar__item">
                        <x-core::icon name="ti ti-phone" style="width: 1rem; height: 1rem" />
                        <a href="tel:{{ $hotline }}">{{ $hotline }}</a>
                    </div>
                @endif
                @if($email = theme_option('email'))
                    <div class="top-header-item serik-hp-topbar__item">
                        <x-core::icon name="ti ti-mail" style="width: 1rem; height: 1rem" />
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </div>
                @endif
            @endif
        </div>

        <div class="top-header-right serik-hp-topbar__right">
            <nav class="serik-hp-topbar__tools" aria-label="{{ __('Quick tools') }}">
                <a href="http://pre-con.serik.ca/" class="my-wishlist-link serik-hp-topbar__link">{{ __('Pre-Construction') }}</a>
                <a href="{{ url('/map') }}" class="my-wishlist-link serik-hp-topbar__link">{{ __('Map Search') }}</a>
                <a href="{{ url('mortgage-calculator') }}" class="my-wishlist-link serik-hp-topbar__link">{{ __('Mortgage Calculator') }}</a>
                <a href="{{ url('cash-back-calculator') }}" class="my-wishlist-link serik-hp-topbar__link">{{ __('Cash Back Calculator') }}</a>
            </nav>

            @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                @auth('account')
                    <a href="{{ route('public.account.dashboard') }}" class="d-flex gap-2 align-items-center serik-hp-topbar__account">
                        {{ RvMedia::image(auth('account')->user()->avatar_url, auth('account')->user()->name, attributes: ['class' => 'rounded-circle serik-hp-topbar__avatar', 'style' => 'width: 22px;height:22px !important;']) }}
                        <span class="text-body-2 fw-semibold">{{ auth('account')->user()->name }}</span>
                    </a>
                @else
                    <div class="register serik-hp-topbar__auth">
                        <ul class="d-flex">
                            <li>
                                <a href="#modalLogin" class="js-auth-open-login">{{ __('Login') }}</a>
                            </li>
                            @if (RealEstateHelper::isRegisterEnabled())
                                <li class="serik-hp-topbar__sep" aria-hidden="true">/</li>
                                <li>
                                    <a href="#modalRegister" class="js-auth-open-register">{{ __('Register') }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endauth
            @endif
        </div>
    </div>
</div>
