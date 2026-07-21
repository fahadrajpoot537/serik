
<style>
.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

/* Fullscreen overlay */
.full-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* Center box */
.overlay-content {
    background: #ffffff;
    width:100%;
    height:100vh;
    padding: 40px 60px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.overlay-content h1 {
    font-size: 40px;
    margin-bottom: 15px;
}

.overlay-content p {
    font-size: 18px;
    margin-bottom: 20px;
}

.overlay-content a {
    display: inline-block;
    padding: 10px 25px;
    background: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
}
</style>

@php
    $announcements = apply_filters('announcement_display_html', null);
    $canRenderAnnouncements = is_plugin_active('announcement') && $announcements && \ArchiElite\Announcement\Models\Announcement::query()->exists();
@endphp

<div class="top-header">
    <div class="top-header-left">
        @if($canRenderAnnouncements)
            {!! $announcements !!}
        @else
            @if($hotline = theme_option('hotline'))
                <div class="top-header-item">
                    <x-core::icon name="ti ti-phone" style="width: 1.25rem; height: 1.25rem" />
                    <a href="tel:{{ $hotline }}">{{ $hotline }}</a>
                </div>
            @endif
            @if($email = theme_option('email'))
                <div class="top-header-item">
                    <x-core::icon name="ti ti-mail" style="width: 1.25rem; height: 1.25rem" />
                    <a href="mailto:{{ $email }}">{{ $email }}</a>
                </div>
            @endif
        @endif
    </div>

    <div class="top-header-right">
        <a href="{{ url('/map') }}" class="my-wishlist-link">
                    {{ __('Map Search') }}
                   
                </a>
         <!--a href="featured-properties" class="my-wishlist-link">
                    {{ __('Featured Properties') }}
                   
                </a-->
                 <a href="{{ url('mortgage-calculator') }}" class="my-wishlist-link">
                    {{ __('Mortgage Calculator') }}
                   
                </a>
                
                <a href="{{ url('cash-back-calculator') }}" class="my-wishlist-link">
                    {{ __('Cash Back Calculator') }}
                   
                </a>
                <!--a href="categories" class="my-wishlist-link">
                    {{ __('Type of Properties') }}
                   
                </a>
      <  @if (is_plugin_active('real-estate'))
            @if (RealEstateHelper::isEnabledWishlist())
                <a href="{{ route('public.wishlist') }}" class="my-wishlist-link">
                    {{ __('My Wishlist') }}
                    (<span data-bb-toggle="wishlist-count" class="fw-medium">0</span>)
                </a>
            @endif

            {!! Theme::partial('currency-switcher') !!}
        @endif

        {!! Theme::partial('language-switcher') !!} -->

        @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
            @auth('account')
                <a href="{{ route('public.account.dashboard') }}" class="d-flex gap-2 align-items-center me-3" style="height:22px !important;">
                    {{ RvMedia::image(auth('account')->user()->avatar_url, auth('account')->user()->name, attributes: ['class' => 'rounded-circle', 'style' => 'width: 22px;height:22px !important;']) }}
                    <span class="text-body-2 fw-semibold">{{ auth('account')->user()->name }}</span>
                </a>
            @else
                <div class="register">
                    <ul class="d-flex">
                        <li>
                            <a href="#modalLogin" class="js-auth-open-login">
                                {{ __('Login') }}
                            </a>
                        </li>
                
                        @if (RealEstateHelper::isRegisterEnabled())
                            <li>/</li>
                            <li>
                                <a href="#modalRegister" class="js-auth-open-register">
                                    {{ __('Register') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endauth
        @endif
    </div>
</div>
