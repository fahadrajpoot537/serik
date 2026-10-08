<style>
.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

/* Top bar: tools span full width; left contact/announcement column removed */
.top-header .top-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-width: 0;
    width: 100%;
    flex-wrap: nowrap;
}
.top-header .top-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
    width: 100%;
    flex: 1 1 auto;
    flex-wrap: nowrap;
}
.top-header .serik-hp-topbar__tools {
    display: flex;
    align-items: center;
    justify-content: space-evenly;
    gap: 0.75rem;
    min-width: 0;
    flex: 1 1 auto;
    width: 100%;
    flex-wrap: nowrap;
}
.top-header a,
.top-header .serik-hp-topbar__link {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.top-header .serik-hp-topbar__account,
.top-header .serik-hp-topbar__auth { flex-shrink: 0; margin-left: auto; }
</style>

<div class="top-header serik-hp-topbar">
    <div class="top-header-inner serik-hp-topbar__inner">
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
                        <ul class="d-flex align-items-center serik-hp-topbar__auth-list">
                            <li>
                                <a href="#modalLogin" class="tf-btn style-border serik-hp-topbar__auth-btn serik-hp-topbar__auth-btn--login js-auth-open-login">{{ __('Login') }}</a>
                            </li>
                            @if (RealEstateHelper::isRegisterEnabled())
                                <li>
                                    <a href="#modalRegister" class="tf-btn primary serik-hp-topbar__auth-btn serik-hp-topbar__auth-btn--join js-auth-open-register">Join Us</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endauth
            @endif
        </div>
    </div>
</div>
