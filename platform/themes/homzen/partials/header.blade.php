<style>
   .smart-search {
    position: relative;
    max-width: 500px;
    min-width: 350px;
}

.search-box {
    background:#f3f6f8;
    border-radius:8px;
    padding:6px 10px;
    display:flex;
    align-items:center;
}

.search-box input {
    border:none;
    background:transparent;
    outline:none;
    padding: 4px 6px;
    width:100%;
    font-size:16px;
}

.clear-btn {
    cursor:pointer;
    opacity:.6;
}

.search-dropdown {
    position:absolute;
    top:60px;
    left:0;
    width:100%;
    background:#fff;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    max-height:450px;
    overflow-y:auto;
    display:none;
    z-index:999;
}

.dropdown-section {
    padding: 0 15px 15px 15px;
}

.search-dropdown .section-title {
    font-size: 12px;
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #666;
    margin: 10px 0 6px;
}

.location-item {
    padding:10px;
    border-radius:8px;
    cursor:pointer;
}

.location-item:hover {
    background:#f4f7f9;
}

.ac-cat-item-hidden {
    display: none !important;
}

.ac-cat-load-more {
    display: block;
    width: auto;
    margin: 0 0 4px 10px;
    padding: 0;
    border: none;
    background: none;
    color: #6b7280;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.4;
    cursor: pointer;
    text-align: left;
}

.ac-cat-load-more:hover {
    color: #0255a1;
    text-decoration: underline;
}

.listing-item {
    display:flex;
    gap:12px;
    padding:10px;
    border-radius:10px;
    cursor:pointer;
}

.listing-item:hover {
    background:#f4f7f9;
}

.listing-item img {
    width:70px;
    height:55px;
    border-radius:8px;
    object-fit:cover;
}

.price {
    color:#0255a1;
    font-weight:700;
}

    
    
    
    
   .show-more-btn{
    width: 100%;
    padding: 7px 10px;
    margin-bottom:10px;
    background: #e9e9e9;        /* light grey fill */
    border: 2px solid #0255a1;  /* teal border */
    color: #0255a1;              /* teal text */
    font-size: 18px;
    font-weight: 500;
    border-radius: 14px;         /* rounded corners */
    cursor: pointer;
    transition: 0.25s ease;
}

/* Hover Effect */
.show-more-btn:hover{
    background:#0255a1 !important;
    color:#fff !important;
}
 
    
    
    
   .dropdown-loader{
    display:flex;
    align-items:center;
    gap:10px;
    padding:14px;
    color:#777;
    font-size:14px;
}

.loader-spinner{
    width:18px;
    height:18px;
    border:3px solid #ddd;
    border-top:3px solid #1aa3a8;
    border-radius:50%;
    animation:spin .8s linear infinite;
}

@keyframes spin{
    100%{ transform:rotate(360deg); }
}
 
 
 .filter-group {
    
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding:10px;
}

.filter-btn {
    padding: 4px 3%;
    border:none;
    border-bottom: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s ease;
}

.filter-btn:hover {
    background: #f1f1f1;
}

.filter-btn.active {
    background: var(--primary-color, #db1d23);
    color: #fff;
    border-color: var(--primary-color, #db1d23);
}

    
 .property-item {
    position: relative;
    overflow: hidden;
}

.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

.property-login-overlay {
   position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 99;

    display: flex;
    justify-content: center;
    align-items: center;
}   
  /* Hide mobile icon on desktop */
.mobile-search-icon{
    display:none;
}

/* MOBILE STYLE */
@media (max-width:768px){

    /* Hide desktop search */
    .smart-search{
        display:none;
    }

    /* Show search icon */
    .mobile-search-icon{
        display:inline;
        margin-left: 180px;
        align-items:center;
        justify-content:center;
        font-size:22px;
        cursor:pointer;
    }
    
    .mobile-search-icon1{
        display:inline;
        margin: 0px 5px;
        align-items:center;
        justify-content:center;
        font-size:22px;
        cursor:pointer;
    }

    /* Full screen search panel */
    .mobile-search-panel{
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100vh;
        background:#fff;
        z-index:9999;
        padding:20px;
        display:none;
        overflow-y:auto;
    }

    .mobile-search-panel.active{
        display:block;
    }

    /* Show search inside panel */
    .mobile-search-panel .smart-search{
        display:block;
        width:100%;
    }

    .search-box input{
        width:100%;
    }

    .mobile-search-header{
        display:flex;
        justify-content:flex-end;
        margin-bottom:10px;
        font-size:22px;
        cursor:pointer;
    }

} 
/* Hide on desktop */
.mobile-search-header{
    display: none;
}

/* Show only on mobile */
@media (max-width:768px){
    .mobile-search-header{
        display:flex;
        justify-content:flex-end;
        margin-bottom:10px;
        font-size:22px;
        cursor:pointer;
    }
}

#navbarSupportedContent{
    background-color: transparent;
    height: 64px;
}

@media (max-width: 991px) {
   #navbarSupportedContent{
    background-color: #fff;    height: auto;
    }
}




@media (max-width: 991px){

    .navigation{
        display: flex;
        flex-direction: column;
        width: 100%;
        padding: 10px 15px;
    }

    .navigation li{
        width: 100%;
        border-bottom: 1px solid #eee;
    }

    .navigation li a{
        display: block;
        padding: 12px 10px;
        font-size: 15px;
        color: #333;
    }

}

@media (max-width: 991px){

    .navigation li ul{
        position: static !important;
        display: none;
        box-shadow: none;
        background: #f8f9fb;
        border-radius: 8px;
        margin: 5px 0;
        padding-left: 10px;
    }

    .navigation li.open > ul{
        display: block;
    }

}




/* ===== MOBILE HEADER ===== */
@media (max-width: 768px){

    #header{
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px !important;
        z-index: 9999;
       
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .logo img{
        max-height: 32px;
    }

    .header-account,
    .nav-outer{
        display: none; /* hide desktop stuff */
    }

    .mobile-nav-toggler,
    .mobile-search-icon,
    .mobile-search-icon1{
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    body{
        padding-top: 60px; /* prevent overlap */
    }
}

/* ===== MOBILE BOTTOM NAV ===== */
.mobile-bottom-nav{
    display: none;
}

@media (max-width: 768px){

    .mobile-bottom-nav{
        display: flex;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 70px;
        color:#fff !important;
        background-color:#111827;
        border-top: 1px solid rgba(255,255,255,0.08);
        z-index: 9999;
        justify-content: space-around;
        align-items: center;
    }

    .mobile-bottom-nav .nav-item{
        text-align: center;
        text-decoration: none;
        color:#fff;
        font-size: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .mobile-bottom-nav .nav-item span{
        font-size: 20px;
    }

    .mobile-bottom-nav .nav-item.active{
        color: #0255a1;
    }

    body{
        padding-bottom: 70px; /* prevent overlap */
    }
}




.mobile-dropdown .dropdown-item {
    border-bottom: 1px solid #eee;
}

.mobile-dropdown .dropdown-toggle {
    font-size: 16px;
    font-weight: 600;
    padding: 12px 10px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
}

.mobile-dropdown .dropdown-toggle::after {
    content: '+';
    font-size: 18px;
}

.mobile-dropdown .dropdown-item.active .dropdown-toggle::after {
    content: '-';
}

.mobile-dropdown .dropdown-menu {
    display: none;
    padding-left: 10px;
}

.mobile-dropdown .dropdown-menu a {
    background-color: #fff;
    display: block;
    padding: 6px 10px;
    font-size: 14px;
}


    
    .main-header.fixed-header {
        position: relative !important;
        top: auto;
        left: auto;
        right: auto;
        z-index: 1;
        width: 100%;
    }

    @media (min-width: 992px) {
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10001;
            width: 100%;
            background: var(--top-header-background-color, #f7f7f7);
        }

        .serik-site-header {
            position: fixed;
            top: var(--serik-top-header-height, 40px);
            left: 0;
            right: 0;
            z-index: 10000;
            width: 100%;
        }

        body.serik-sticky-header {
            padding-top: calc(var(--serik-top-header-height, 40px) + var(--serik-main-header-height, 60px));
        }
    }

    @media (max-width: 991px) {
        .serik-site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            width: 100%;
        }

        body.serik-sticky-header {
            padding-top: 60px;
        }
    }

    /* Navbar responsive polish (UI only — keep sticky/mobile menu behavior) */
    .main-header .inner-container,
    .main-header .serik-nav-bar {
        min-width: 0;
        width: 100% !important;
        gap: 0.5rem;
        flex-wrap: nowrap;
        display: flex !important;
        justify-content: flex-start !important;
        align-items: center !important;
    }
    .main-header .logo-box {
        min-width: 0;
        flex: 0 1 auto;
    }
    .main-header .logo {
        max-width: min(200px, 46vw);
    }
    .main-header .logo img {
        max-width: 100% !important;
        height: auto !important;
        object-fit: contain;
    }
    .main-header .serik-nav-right {
        display: flex !important;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-left: auto !important;
        flex: 0 0 auto;
    }
    .main-header .header-account {
        flex: 0 0 auto;
        min-width: 0;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .main-header .serik-portal-nav__actions {
        display: flex !important;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        flex-wrap: nowrap;
        margin-left: 0;
    }
    .main-header .serik-portal-nav__map,
    .main-header .serik-portal-nav__cta {
        white-space: nowrap;
        flex-shrink: 0;
    }
    .main-header .main-menu .navigation > li > a {
        white-space: nowrap;
    }
    .main-header .mobile-nav-toggler {
        flex-shrink: 0;
    }
    @media (max-width: 1199.98px) {
        .main-header .serik-portal-nav__map {
            display: none;
        }
    }
    @media (max-width: 767.98px) {
        .main-header .logo {
            max-width: min(160px, 52vw);
        }
        .smart-search {
            min-width: 0;
            max-width: 100%;
        }
    }

    
</style>

<header
    id="header"
    style="min-height: 64px;"
    @class(['main-header', 'serik-hp-nav', 'fixed-header' => theme_option('sticky_header_enabled', true), Theme::get('headerClass')])
>
<script>
(function () {
    const topBar = document.querySelector('.top-header');
    const mainHeader = document.getElementById('header');
    const setHeights = () => {
        const topH = window.innerWidth >= 992 && topBar ? topBar.offsetHeight : 0;
        const mainH = mainHeader ? mainHeader.offsetHeight : 60;
        document.documentElement.style.setProperty('--serik-top-header-height', topH + 'px');
        document.documentElement.style.setProperty('--serik-main-header-height', mainH + 'px');
    };
    document.documentElement.classList.add('serik-sticky-header-enabled');
    if (mainHeader?.classList.contains('fixed-header')) {
        document.body.classList.add('serik-sticky-header');
    }
    setHeights();
    window.addEventListener('resize', setHeights);
    window.addEventListener('load', setHeights);
})();
</script>
    <div class="header-lower">
        <div class="row">
            <div class="col-lg-12">
                <div class="inner-container d-flex align-items-center serik-nav-bar">
                    <div class="logo-box d-flex align-items-center gap-3">
                        <div class="logo">
                            <a href="{{ BaseHelper::getHomepageUrl() }}">
                                {{ Theme::getLogoImage(maxHeight: 52) }}
                            </a>
                        </div>
                        
                        
                        <div class="mobile-search-panel" id="mobileSearchPanel">

                            <div class="mobile-search-header">
                                <span  id="closeMobileSearch">✕</span>
                            </div>

                               <div class="smart-search">
                                <div class="search-box">
                                     <x-core::icon name="ti ti-search" />
                                    <input type="text" id="smartInput" placeholder="Search address, community, street or listing...">
                                    <span class="clear-btn" id="clearBtn">✕</span>
                                </div>
        
                                    <div class="search-dropdown" id="searchDropdown">
                                        
                                        
                                        <div class="filter-group">
        
                                            <button class="filter-btn" data-type="transaction" data-value="For Sale">
                                                For Sale
                                            </button>
                                        
                                            <button class="filter-btn" data-type="transaction" data-value="For Lease">
                                                For Lease
                                            </button>
                                        
                                            |
                                        
                                            <button class="filter-btn" data-type="status" data-value="New">
                                                Active
                                            </button>
                                        
                                            <button class="filter-btn" data-type="status" data-value="Sold">
                                                Sold
                                            </button>
                                        
                                        </div>
        
                                
                                        <!-- Locations -->
                                        <div class="dropdown-section">
                                            <div class="section-title">Locations</div>
                                            <div id="locationResults"></div>
                                        </div>
                                
                                        <!-- Listings -->
                                        <div class="dropdown-section">
                                            <div class="section-title">Listings</div>
                                            <div id="listingResults"></div>
                                        </div>
                                        <div id="dropdownLoader" class="dropdown-loader" style="display:none;">
                                            <div class="loader-spinner"></div>
                                            <span>Searching properties...</span>
                                        </div>
                                        <center><button id="loadMoreBtn" class="tf-btn primary" style="width:60%;    padding: 5px 10px;margin-bottom:5px;">Load More</button></center>
                                      
                                    </div>
                                </div>
                        </div>
                    </div>
                    <div class="nav-outer">
                        
                        <nav class="main-menu show navbar-expand-md">
                            <div class="navbar-collapse collapse clearfix" id="navbarSupportedContent" style="">
                                {!! \App\Support\HomepageFragmentCache::rememberMenu('main-menu', static fn () => Menu::renderMenuLocation('main-menu', [
                                    'options' => ['class' => 'navigation clearfix'],
                                    'view' => 'main-menu',
                                ])) !!}
                            </div>
                        </nav>
                        
                    </div>
                    <div class="serik-nav-right">
                        <div class="header-account">
                            @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                                <div class="flat-bt-top serik-portal-nav__actions">
                                    <a class="tf-btn primary serik-hp-nav__cta serik-portal-nav__cta" href="{{ url('/contact-us') }}">{{ __('Contact Us') }}</a>
                                    <a class="serik-portal-nav__map" href="tel:+16475789400">+1 (647) 578-9400</a>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mobile-nav-toggler mobile-button">
                           
                            <x-core::icon name="ti ti-menu-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
   
    

    <div class="close-btn">
        <x-core::icon name="ti ti-x" />
    </div>

    <div class="mobile-menu">
        <div class="menu-backdrop"></div>
        <nav class="menu-box">
            <div class="nav-logo">
                <a href="{{ BaseHelper::getHomepageUrl() }}">
                    {{ Theme::getLogoImage(maxHeight: 44) }}
                </a>
            </div>
            <div class="bottom-canvas">
               <!-- @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                    @auth('account')
                        <div class="mb-3">
                            <a href="{{ route('public.account.dashboard') }}" class="d-flex gap-2 align-items-center">
                                {{ RvMedia::image(auth('account')->user()->avatar_url, auth('account')->user()->name, attributes: ['width' => 40, 'class' => 'rounded-circle']) }}
                                <span class="text-body-2 fw-semibold">{{ auth('account')->user()->name }}</span>
                            </a>
                        </div>
                    @else
                        <div class="login-box flex align-items-center">
                            <a
                                @if (theme_option('use_modal_for_authentication', true))
                                    href="#modalLogin"
                                data-bs-toggle="modal"
                                @else
                                    href="{{ route('public.account.login') }}"
                                @endif
                            >{{ __('Login') }}</a>
                            @if (RealEstateHelper::isRegisterEnabled())
                                <span>/</span>
                                <a
                                    @if (theme_option('use_modal_for_authentication', true))
                                        href="#modalRegister"
                                    data-bs-toggle="modal"
                                    @else
                                        href="{{ route('public.account.register') }}"
                                    @endif
                                >{{ __('Register') }}</a>
                            @endif
                        </div>
                    @endauth
                @endif-->

                    <!--div class="menu-outer"></div-->
                    <div class="mobile-dropdown">

                        <!-- BUY MENU -->
                        <div class="dropdown-item">
                            <div class="dropdown-toggle">Buy</div>
                            <div class="dropdown-menu">
                                <a href="{{ url('/on/houses-for-sale-in-brampton/map') }}" class="main-city">Houses for Sale in Brampton</a>
                                  <a href="{{ url('/on/houses-for-sale-in-mississauga/map') }}" class="main-city" >Houses for Sale in Mississauga</a>
                                    <a href="{{ url('/on/houses-for-sale-in-toronto/map') }}" class="main-city">Houses for Sale in Toronto</a>
                                   <a href="{{ url('/on/houses-for-sale-in-vaughan/map') }}" class="main-city">Houses for Sale in Vaughan</a>
                                   <a href="{{ url('/on/houses-for-sale-in-oakville/map') }}" class="main-city">Houses for Sale in Oakville</a>
                                   <a href="{{ url('/on/houses-for-sale-in-milton/map') }}" class="main-city">Houses for Sale in Milton</a>
                                   <a href="{{ url('/on/houses-for-sale-in-hamilton/map') }}" class="main-city">Houses for Sale in Hamilton</a>
                                   <a href="{{ url('/on/houses-for-sale-in-kitchener/map') }}" class="main-city">Houses for Sale in Kitchener</a>
                                    <a href="{{ url('/on/houses-for-sale-in-ottawa/map') }}" class="main-city">Houses for Sale in Ottawa</a>
                            </div>
                        </div>
                    
                        <!-- SELL MENU -->
                        <div class="dropdown-item">
                            <div class="dropdown-toggle">Sell</div>
                            <div class="dropdown-menu">
                               
                            
                         
                            <a href="{{ url('/free-home-evaluation') }}" class="main-city"> Free Home Evaluation</a>
                                <a href="https://serik.ca/tips-for-home-selling" class="main-city"> Tips For Home Selling</a>
                                <a href="https://serik.ca/about-us#testimonials" class="main-city"> Customers' testimonials</a>
                            </div>
                        </div>
                        
                        
                         <div class="dropdown-item">
                            <div class="dropdown-toggle">Upsize</div>
                            <div class="dropdown-menu">
                                <a href="https://serik.ca/appointment-scheduler" class="my-wishlist-link main-city">
                                {{ __('Upsize with Serik Realty') }}
                               
                            </a>
                            
                         
                           
                            </div>
                        </div>
                    
                    </div>

                @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                    <div class="button-mobi-sell">
                    
                    
                             
                                <a style="font-size:14px; font-weight:600;padding: 5px 10px;" href="{{ get_blog_page_url() }}">{{ __('Blog') }}</a>
                                <br><br>
                        <a class="tf-btn primary" href="{{ url('/contact-us') }}">{{ __('Contact Us') }}</a>
                        
                    </div>
                @endif


                <div class="mobi-icon-box">
                    @if (is_plugin_active('real-estate'))
                        @if (RealEstateHelper::isEnabledWishlist())
                            <div class="box">
                                <a href="{{ route('public.wishlist') }}">
                                    {{ __('My Wishlist') }}
                                    (<span data-bb-toggle="wishlist-count" class="fw-medium">0</span>)
                                </a>
                            </div>
                        @endif

                        <!--div class="box">
                            {!! Theme::partial('currency-switcher') !!}
                        </div-->
                    @endif

                    @if ($languageSwitcher = Theme::partial('language-switcher'))
                        <div class="box">
                            {!! $languageSwitcher !!}
                        </div>
                    @endif

                    @if($hotline = theme_option('hotline'))
                        <div class="box d-flex align-items-center">
                            <x-core::icon name="ti ti-phone" style="width: 1.25rem; height: 1.25rem" />
                            <div><a href="tel:{{ $hotline }}" title="{{ __('Phone') }}">{{ $hotline }}</a></div>
                        </div>
                    @endif
                    @if($email = theme_option('email'))
                        <div class="box d-flex align-items-center">
                            <x-core::icon name="ti ti-mail" style="width: 1.25rem; height: 1.25rem" />
                            <div><a href="mailto:{{ $email }}" title="{{ __('Email') }}">{{ $email }}</a></div>
                        </div>
                    @endif
                </div>
            </div>
        </nav>
    </div>
</header>


<div class="mobile-bottom-nav">

    <a href="/" class="nav-item">
        <x-core::icon name="ti ti-home" />
        <small>Home</small>
    </a>

    <a href="/on/houses-for-sale-in-ontario/map" class="nav-item">
        <x-core::icon name="ti ti-map-pin-search" />
        <small>Map</small>
    </a>

    <a href="javascript:void(0)" id="openMobileSearchBottom" class="nav-item">
        <x-core::icon name="ti ti-search" />
        <small>Search</small>
    </a>

    <a href="{{ url('/mortgage-calculator') }}" class="nav-item">
        <x-core::icon name="ti ti-calculator" />
        <small>Mortgage</small>
    </a>

    {{-- AUTH SECTION --}}
    @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
        @auth('account')
            <a href="{{ route('public.account.dashboard') }}" class="nav-item">
                <x-core::icon name="ti ti-user" />
                <small>Account</small>
            </a>
        @else
            <a 
                @if (theme_option('use_modal_for_authentication', true))
                    href="#modalLogin" data-bs-toggle="modal"
                @else
                    href="{{ route('public.account.login') }}"
                @endif
                class="nav-item"
            >
                <x-core::icon name="ti ti-login" />
                <small>Login</small>
            </a>
        @endauth
    @endif

</div>
{!! Theme::partial('community-search-index') !!}
<script>




document.querySelectorAll('.mobile-dropdown .dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
        const parent = this.parentElement;

        // close others (optional)
        document.querySelectorAll('.mobile-dropdown .dropdown-item').forEach(item => {
            if (item !== parent) {
                item.classList.remove('active');
                const otherMenu = item.querySelector('.dropdown-menu');
                if (otherMenu) {
                    otherMenu.style.display = 'none';
                }
            }
        });

        // toggle current
        parent.classList.toggle('active');
        const menu = parent.querySelector('.dropdown-menu');

        if (menu) {
            menu.style.display = menu.style.display === 'contents' ? 'none' : 'contents';
        }
    });
});




const openSearchBottom = document.getElementById('openMobileSearchBottom');

if(openSearchBottom){
    openSearchBottom.addEventListener('click', function(){
        document.getElementById('mobileSearchPanel').classList.add('active');
    });
}

const currentPath = window.location.pathname;

document.querySelectorAll('.mobile-bottom-nav .nav-item').forEach(link => {
    if(link.getAttribute('href') === currentPath){
        link.classList.add('active');
    }
});

document.addEventListener("DOMContentLoaded", function () {
  const links = document.querySelectorAll(".navigation > li > a");
  
  links.forEach(link => {
    link.style.fontSize = "14px"; // change to your desired size
  });
});

const openSearch = document.getElementById('openMobileSearch');
const closeSearch = document.getElementById('closeMobileSearch');
const mobilePanel = document.getElementById('mobileSearchPanel');

const openSearchTop = document.getElementById('openMobileSearch');
//const openSearchBottom = document.getElementById('openMobileSearchBottom');

//const mobilePanel = document.getElementById('mobileSearchPanel');

if (openSearchTop) {
    openSearchTop.addEventListener('click', () => {
        mobilePanel.classList.add('active');
    });
}

if (openSearchBottom) {
    openSearchBottom.addEventListener('click', () => {
        mobilePanel.classList.add('active');
    });
}

if (closeSearch) {
    closeSearch.addEventListener('click', function(){
        if (mobilePanel) {
            mobilePanel.classList.remove('active');
        }
    });
}

const headerCityCoordinates = {
    Brampton: { lat: 43.6886, lng: -79.7561 },
    Mississauga: { lat: 43.5878, lng: -79.6565 },
    Toronto: { lat: 43.6575, lng: -79.3987 },
    Vaughan: { lat: 43.7866, lng: -79.5146 },
    Milton: { lat: 43.5180, lng: -79.8793 },
    Oakville: { lat: 43.4522, lng: -79.7209 },
    Hamilton: { lat: 43.2533, lng: -79.8755 },
    Ottawa: { lat: 45.4215, lng: -75.7001 },
    Kitchener: { lat: 43.4511, lng: -80.4904 },
    Waterloo: { lat: 43.4832, lng: -80.5254 },
    Cambridge: { lat: 43.3616, lng: -80.3110 },
    London: { lat: 42.9849, lng: -81.2453 },
    Markham: { lat: 43.8561, lng: -79.3370 },
    RichmondHill: { lat: 43.8828, lng: -79.4403 },
    Burlington: { lat: 43.3255, lng: -79.7990 },
    Oshawa: { lat: 43.8971, lng: -78.8658 },
    Barrie: { lat: 44.3894, lng: -79.6903 },
    Guelph: { lat: 43.5448, lng: -80.2482 },
    Whitby: { lat: 43.8975, lng: -78.9429 },
    Ajax: { lat: 43.8509, lng: -79.0204 },
    Pickering: { lat: 43.8354, lng: -79.0890 },
    Newmarket: { lat: 44.0592, lng: -79.4613 },
    Aurora: { lat: 44.0065, lng: -79.4504 },
    Bradford: { lat: 44.1148, lng: -79.5629 },
    Brantford: { lat: 43.1394, lng: -80.2644 },
    StCatharines: { lat: 43.1594, lng: -79.2469 },
    NiagaraFalls: { lat: 43.0889, lng: -79.0819 },
    Windsor: { lat: 42.3149, lng: -83.0364 },
    Peterborough: { lat: 44.3091, lng: -78.3197 },
};

const headerCityAliases = { brandford: 'bradford' };

function normalizeHeaderCity(city) {
    return String(city || '').trim().toLowerCase().replace(/\s+/g, '');
}

function formatHeaderCityLabel(cityKey) {
    return String(cityKey || '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

const headerCitySearchIndex = (() => {
    const index = new Map();
    Object.keys(headerCityCoordinates).forEach((key) => {
        const norm = normalizeHeaderCity(key);
        if (!index.has(norm)) {
            index.set(norm, {
                label: formatHeaderCityLabel(key),
                coords: headerCityCoordinates[key],
            });
        }
    });
    return index;
})();

function getHeaderMatchingCities(keyword) {
    const raw = String(keyword || '').trim().toLowerCase();
    if (!raw) return [];

    const aliasedRaw = headerCityAliases[raw] || raw;
    const tokens = aliasedRaw.split(/[\s,]+/).filter((t) => t.length >= 2 && !/^\d+[a-z]?$/i.test(t));
    const needles = Array.from(new Set([
        normalizeHeaderCity(aliasedRaw),
        ...tokens.map((t) => normalizeHeaderCity(headerCityAliases[t] || t)),
    ])).filter((n) => n.length >= 2);

    const matches = [];
    headerCitySearchIndex.forEach((city, norm) => {
        for (const needle of needles) {
            if (norm.startsWith(needle) || norm.includes(needle) || needle.startsWith(norm)) {
                matches.push({ label: city.label, coords: city.coords, score: norm.startsWith(needle) ? 0 : 1 });
                break;
            }
        }
    });

    return matches.sort((a, b) => a.score - b.score || a.label.localeCompare(b.label)).slice(0, 6);
}

function headerSlugify(text) {
    return String(text || '')
        .trim()
        .toLowerCase()
        .replace(/&/g, 'and')
        .replace(/\s+/g, '-')
        .replace(/--+/g, '-');
}

function buildHeaderCityMapUrl(cityName, lat, lng) {
    const citySlug = headerSlugify(cityName);
    const tx = selectedFilters.transaction === 'For Lease' ? 'lease' : 'sale';
    let url = `${SITE_BASE}/on/houses-for-${tx}-in-${citySlug}/map`;
    const nLat = Number(lat);
    const nLng = Number(lng);
    if (Number.isFinite(nLat) && Number.isFinite(nLng) && !(nLat === 0 && nLng === 0)) {
        url += `?lat=${nLat}&lng=${nLng}`;
    }
    return url;
}

function buildHeaderCitySuggestionsHtml(keyword) {
    return getHeaderMatchingCities(keyword).map((city) => `
        <div class="location-item city-item"
            role="button"
            tabindex="0"
            data-city="${city.label}"
            data-lat="${city.coords.lat}"
            data-lng="${city.coords.lng}">
            🌆 ${city.label}
        </div>
    `).join('');
}

const headerCommunitySuggestCache = new Map();
const HEADER_COMMUNITY_SUGGEST_CACHE_MAX = 80;
let headerCommunityAbort = null;

function filterCommunityRowsByKeyword(rows, keyword) {
    const needle = String(keyword || '').trim().toLowerCase();
    if (!needle) {
        return [];
    }

    return (rows || []).filter((row) => {
        const name = String(row?.name || '').toLowerCase();
        const city = String(row?.city || '').toLowerCase();
        return name.includes(needle) || city.includes(needle);
    });
}

function getPrefixCommunityCache(keyword, cache) {
    const trimmed = String(keyword || '').trim().toLowerCase();
    if (trimmed.length < 2) {
        return null;
    }

    let best = null;

    for (const [url, rows] of cache.entries()) {
        const match = url.match(/keyword=([^&]+)/);
        if (!match) {
            continue;
        }

        const cachedKeyword = decodeURIComponent(match[1]).toLowerCase();
        if (!trimmed.startsWith(cachedKeyword) || cachedKeyword.length < 2) {
            continue;
        }

        if (!best || cachedKeyword.length > best.keyword.length) {
            best = { keyword: cachedKeyword, rows };
        }
    }

    if (!best) {
        return null;
    }

    return filterCommunityRowsByKeyword(best.rows, trimmed);
}

function fetchHeaderCommunitySuggestions(keyword, signal) {
    const trimmed = String(keyword || '').trim();
    if (trimmed.length < 2) {
        return Promise.resolve([]);
    }

    const local = window.SerikCommunitySearch?.filter?.(trimmed, 8);
    if (local && local.length) {
        return Promise.resolve(local);
    }

    const url = `/api/v1/community-suggestions?keyword=${encodeURIComponent(trimmed)}&limit=8`;
    if (headerCommunitySuggestCache.has(url)) {
        return Promise.resolve(headerCommunitySuggestCache.get(url));
    }

    return fetch(url, {
        signal,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((res) => (res.ok ? res.json() : []))
        .then((data) => {
            const rows = Array.isArray(data) ? data : [];
            if (headerCommunitySuggestCache.size >= HEADER_COMMUNITY_SUGGEST_CACHE_MAX) {
                headerCommunitySuggestCache.delete(headerCommunitySuggestCache.keys().next().value);
            }
            headerCommunitySuggestCache.set(url, rows);
            return rows;
        })
        .catch(() => []);
}

function buildHeaderCommunitySuggestionsHtml(communities) {
    return (communities || []).map((community) => {
        const name = community.name || '';
        const city = community.city || '';
        const citySuffix = city ? ` <span style="color:#6b7280;font-size:12px;">${city}</span>` : '';
        const isPlace = community.source === 'place';
        const itemClass = isPlace ? 'location-item place-item' : 'location-item community-item';
        const icon = isPlace ? '📍' : '🏘️';

        return `
            <div class="${itemClass}"
                role="button"
                tabindex="0"
                data-community="${name.replace(/"/g, '&quot;')}"
                data-city="${city.replace(/"/g, '&quot;')}"
                data-lat="${community.lat ?? ''}"
                data-lng="${community.lng ?? ''}"
                data-source="${community.source || 'mls'}">
                ${icon} ${name}${citySuffix}
            </div>
        `;
    }).join('');
}

function buildHeaderLocationSuggestionsHtml(keyword, communities) {
    return buildHeaderCitySuggestionsHtml(keyword) + buildHeaderCommunitySuggestionsHtml(communities);
}

function buildHeaderCommunityMapUrl(community) {
    const isPlace = community.source === 'place';
    let nLat = Number(community.lat);
    let nLng = Number(community.lng);
    let hasCoords = Number.isFinite(nLat) && Number.isFinite(nLng) && !(nLat === 0 && nLng === 0);

    // If community has no pin coords, fall back to its city center so map never opens in Toronto.
    if (!hasCoords && community.city) {
        const cityKey = Object.keys(headerCityCoordinates).find((key) =>
            normalizeHeaderCity(key) === normalizeHeaderCity(community.city)
            || normalizeHeaderCity(formatHeaderCityLabel(key)) === normalizeHeaderCity(community.city)
        );
        const cityCoords = cityKey ? headerCityCoordinates[cityKey] : null;
        if (cityCoords) {
            nLat = cityCoords.lat;
            nLng = cityCoords.lng;
            hasCoords = true;
        }
    }

    if (isPlace) {
        let url = `${SITE_BASE}/map?place=${encodeURIComponent(community.name)}`;
        if (community.city) {
            url += `&city=${encodeURIComponent(community.city)}`;
        }
        if (hasCoords) {
            url += `&lat=${nLat}&lng=${nLng}`;
        }
        return url;
    }

    let url = `${SITE_BASE}/map?community=${encodeURIComponent(community.name)}`;
    if (community.city) {
        url += `&city=${encodeURIComponent(community.city)}`;
    }
    if (hasCoords) {
        url += `&lat=${nLat}&lng=${nLng}`;
    }
    return url;
}

const AC_SUGGEST_PREVIEW_LIMIT = 2;
let headerAcCatExpanded = { cities: false, communities: false, addresses: false };

function resetHeaderAcCatExpanded() {
    headerAcCatExpanded = { cities: false, communities: false, addresses: false };
}

function applyHeaderSuggestionCategoryLimits() {
    const container = document.getElementById('locationResults');
    if (!container) {
        return;
    }

    container.querySelectorAll('.ac-cat-load-more').forEach((el) => el.remove());

    [
        { key: 'cities', selector: '.city-item' },
        { key: 'communities', selector: '.community-item, .place-item' },
        { key: 'addresses', selector: '.address-item' },
    ].forEach(({ key, selector }) => {
        const items = Array.from(container.querySelectorAll(selector));
        const expanded = !!headerAcCatExpanded[key];

        items.forEach((item, index) => {
            if (!expanded && index >= AC_SUGGEST_PREVIEW_LIMIT) {
                item.classList.add('ac-cat-item-hidden');
            } else {
                item.classList.remove('ac-cat-item-hidden');
            }
        });

        if (!expanded && items.length > AC_SUGGEST_PREVIEW_LIMIT) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ac-cat-load-more';
            btn.dataset.acCat = key;
            btn.setAttribute('aria-label', 'Load more suggestions');
            btn.textContent = 'Load More';
            items[AC_SUGGEST_PREVIEW_LIMIT - 1].insertAdjacentElement('afterend', btn);
        }
    });
}

function renderHeaderSearchShell(keyword) {
    const cityHTML = buildHeaderCitySuggestionsHtml(keyword);
    const locationEl = document.getElementById('locationResults');
    const listingEl = document.getElementById('listingResults');
    if (locationEl) {
        locationEl.innerHTML = cityHTML;
        applyHeaderSuggestionCategoryLimits();
    }
    if (listingEl) {
        listingEl.innerHTML = '<div class="hs-search-pending" style="padding:10px 12px;color:#6b7280;">Searching...</div>';
    }
    loader.style.display = 'flex';
    dropdown.style.display = 'block';
}

const input = document.getElementById("smartInput");
const dropdown = document.getElementById("searchDropdown");
const loadMoreBtn = document.getElementById("loadMoreBtn");
const loader = document.getElementById("dropdownLoader");
const clearBtn = document.getElementById("clearBtn");
const SITE_BASE = @json(rtrim(url('/'), '/'));
const isLoggedIn = @json((is_plugin_active('real-estate') && auth('account')->check()) || auth()->check());
const SOLD_STATUSES = ['Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape', 'Leased Conditional'];
let skip = 0;
let currentKeyword = "";
if (loadMoreBtn) {
    loadMoreBtn.style.display = "block";
}
let typingTimer;
const typingDelay = 300;
let searchController = null;
let headerSearchRequestId = 0;
const headerSearchCache = new Map();
const HEADER_SEARCH_CACHE_MAX = 60;

function headerSmartSearchFetch(url, signal) {
    if (headerSearchCache.has(url)) {
        return Promise.resolve(headerSearchCache.get(url));
    }
    return fetch(url, { signal, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then((res) => {
            if (!res.ok) {
                throw new Error('Search request failed');
            }
            return res.json();
        })
        .then((data) => {
            if (headerSearchCache.size >= HEADER_SEARCH_CACHE_MAX) {
                headerSearchCache.delete(headerSearchCache.keys().next().value);
            }
            headerSearchCache.set(url, data);
            return data;
        });
}

function buildHeaderSearchUrl(keyword) {
    const trimmed = String(keyword || '').trim();
    let url = `/api/v1/smart-search?keyword=${encodeURIComponent(trimmed)}&skip=${skip}`;
    const skipTxFilter = isHeaderMlsKeyword(trimmed) || looksLikeHeaderMlsPrefix(trimmed) || /\d/.test(trimmed);
    if (!skipTxFilter && selectedFilters.transaction) {
        url += `&transaction=${encodeURIComponent(selectedFilters.transaction)}`;
    }
    if (!skipTxFilter && selectedFilters.status) {
        url += `&status=${encodeURIComponent(selectedFilters.status)}`;
    }
    return url;
}

function isSoldListing(item) {
    return SOLD_STATUSES.includes(item.MlsStatus);
}

function guestBlurClass(item) {
    return (!isLoggedIn && isSoldListing(item)) ? 'blurred-content' : '';
}

function isHeaderMlsKeyword(keyword) {
    return /^[a-z]{1,2}\d{5,}$/i.test(String(keyword || '').trim());
}

function looksLikeHeaderMlsPrefix(keyword) {
    return /^[a-z]{1,2}\d{2,}$/i.test(String(keyword || '').trim());
}

function showHeaderSearchPending() {
    loader.style.display = 'flex';
    dropdown.style.display = 'block';
}

function isHeaderLocationOnlyKeyword(keyword) {
    const trimmed = String(keyword || '').trim();
    if (!trimmed || trimmed.length < 2) {
        return false;
    }
    if (/\d/.test(trimmed)) {
        return false;
    }
    return !isHeaderMlsKeyword(trimmed) && !looksLikeHeaderMlsPrefix(trimmed);
}

function handleHeaderSearchInput(keyword) {
    clearTimeout(typingTimer);
    const trimmed = String(keyword || '').replace(/\s+/g, ' ').trim();

    if (trimmed.length < 2) {
        currentKeyword = '';
        if (searchController) {
            searchController.abort();
            searchController = null;
        }
        dropdown.style.display = 'none';
        loader.style.display = 'none';
        document.getElementById('locationResults').innerHTML = '';
        document.getElementById('listingResults').innerHTML = '';
        return;
    }

    if (trimmed === currentKeyword) {
        return;
    }

    currentKeyword = trimmed;
    skip = 0;
    if (searchController) {
        searchController.abort();
        searchController = null;
    }
    resetHeaderAcCatExpanded();
    renderHeaderSearchShell(trimmed);
    typingTimer = setTimeout(() => {
        if (currentKeyword === trimmed) {
            loadResults(trimmed, true);
        }
    }, typingDelay);
}

if (input) {
input.addEventListener('input', function () {
    handleHeaderSearchInput(this.value);
});

input.addEventListener('focus', function () {
    const trimmed = String(this.value || '').replace(/\s+/g, ' ').trim();
    if (trimmed.length >= 2) {
        if (trimmed === currentKeyword) {
            dropdown.style.display = 'block';
        } else {
            handleHeaderSearchInput(this.value);
        }
    }
});
}

if (dropdown) {
dropdown.addEventListener('click', function (e) {
    const loadMoreCat = e.target.closest('.ac-cat-load-more');
    if (loadMoreCat) {
        e.preventDefault();
        e.stopPropagation();
        const key = loadMoreCat.dataset.acCat;
        if (key) {
            headerAcCatExpanded[key] = true;
            applyHeaderSuggestionCategoryLimits();
        }
        return;
    }

    const communityItem = e.target.closest('.community-item, .place-item');
    if (communityItem) {
        e.preventDefault();
        e.stopPropagation();

        const communityName = communityItem.dataset.community || '';
        if (!communityName) {
            return;
        }

        input.value = communityItem.dataset.city
            ? `${communityName}, ${communityItem.dataset.city}`
            : communityName;
        dropdown.style.display = 'none';
        loader.style.display = 'none';
        window.location.href = buildHeaderCommunityMapUrl({
            name: communityName,
            city: communityItem.dataset.city || '',
            lat: communityItem.dataset.lat || '',
            lng: communityItem.dataset.lng || '',
            source: communityItem.dataset.source || 'mls',
        });
        return;
    }

    const item = e.target.closest('.city-item');
    if (!item) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const cityName = item.dataset.city || item.innerText.replace(/[^\w\s-]/g, '').trim();
    if (!cityName) {
        return;
    }

    input.value = cityName;
    dropdown.style.display = 'none';
    loader.style.display = 'none';
    window.location.href = buildHeaderCityMapUrl(cityName, item.dataset.lat, item.dataset.lng);
});

dropdown.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') {
        return;
    }

    const item = e.target.closest('.city-item, .community-item');
    if (!item) {
        return;
    }

    e.preventDefault();
    item.click();
});
}

function buildPropertyUrl(item) {
    const slug = String(item.URL || item.url || '').replace(/^\/+/, '');

    if (!slug) {
        return `${SITE_BASE}/properties`;
    }

    return `${SITE_BASE}/properties/${slug}`;
}




function getPrefixSearchCache(keyword, cache) {
    const trimmed = String(keyword || '').trim().toLowerCase();
    if (trimmed.length < 2) {
        return null;
    }

    let best = null;

    for (const [url, rows] of cache.entries()) {
        const match = url.match(/keyword=([^&]+)/);
        if (!match) {
            continue;
        }

        const cachedKeyword = decodeURIComponent(match[1]).toLowerCase();
        if (!trimmed.startsWith(cachedKeyword) || cachedKeyword.length < 2) {
            continue;
        }

        if (!best || cachedKeyword.length > best.keyword.length) {
            best = { keyword: cachedKeyword, rows };
        }
    }

    return best?.rows || null;
}

function loadResults(keyword, reset = false){
    const requestId = ++headerSearchRequestId;
    const cityHTML = buildHeaderCitySuggestionsHtml(keyword);

    if (reset) {
        const prefixListings = getPrefixSearchCache(keyword, headerSearchCache);
        const localCommunities = window.SerikCommunitySearch?.filter?.(keyword, 8);
        const prefixCommunities = (localCommunities && localCommunities.length)
            ? localCommunities
            : getPrefixCommunityCache(keyword, headerCommunitySuggestCache);
        const locationHTML = buildHeaderLocationSuggestionsHtml(keyword, prefixCommunities || []);
        document.getElementById('locationResults').innerHTML = locationHTML || cityHTML;
        applyHeaderSuggestionCategoryLimits();
        if (prefixListings && prefixListings.length) {
            renderHeaderSearchResults(keyword, true, locationHTML || cityHTML, prefixListings, isHeaderMlsKeyword(keyword));
        } else if (!document.getElementById('listingResults').innerHTML) {
            document.getElementById('listingResults').innerHTML =
                '<div class="hs-search-pending" style="padding:10px 12px;color:#6b7280;">Searching...</div>';
        }
    }

    if (searchController) {
        searchController.abort();
    }
    const requestController = new AbortController();
    searchController = requestController;
    const isAddressLike = /\d/.test(String(keyword || ''));
    const communityPromise = isAddressLike
        ? Promise.resolve([])
        : fetchHeaderCommunitySuggestions(keyword, requestController.signal);
    loadResults._activeKeyword = keyword;

    const isMlsKey = isHeaderMlsKeyword(keyword);
    const searchTimeoutMs = isMlsKey ? 45000 : 15000;
    const searchTimeoutId = setTimeout(() => requestController.abort(), searchTimeoutMs);
    const searchUrl = buildHeaderSearchUrl(keyword);

    if (headerSearchCache.has(searchUrl)) {
        communityPromise.then((communities) => {
            if (requestId !== headerSearchRequestId) {
                return;
            }
            const locationHTML = buildHeaderLocationSuggestionsHtml(keyword, communities);
            renderHeaderSearchResults(keyword, reset, locationHTML, headerSearchCache.get(searchUrl), isMlsKey);
        });
        clearTimeout(searchTimeoutId);
        loader.style.display = 'none';
        return;
    }

    Promise.all([
        headerSmartSearchFetch(searchUrl, requestController.signal),
        communityPromise,
    ])
    .then(([data, communities]) => {
        if (requestId !== headerSearchRequestId) {
            return;
        }
        const locationHTML = buildHeaderLocationSuggestionsHtml(keyword, communities);
        renderHeaderSearchResults(keyword, reset, locationHTML, data, isMlsKey);
    })
    .catch((err) => {
        if (requestId !== headerSearchRequestId || err.name === 'AbortError') {
            return;
        }
        console.error('Header search failed:', err);
        if (reset) {
            document.getElementById('locationResults').innerHTML = cityHTML;
            applyHeaderSuggestionCategoryLimits();
            document.getElementById('listingResults').innerHTML =
                '<div style="padding:12px;color:#666;">Search failed. Please try again.</div>';
        }
    })
    .finally(() => {
        if (requestId !== headerSearchRequestId) {
            return;
        }
        clearTimeout(searchTimeoutId);
        loader.style.display = 'none';
        dropdown.style.display = 'block';
    });
}

function renderHeaderSearchResults(keyword, reset, cityHTML, data, isMlsKey) {
    loader.style.display = 'none';
    dropdown.style.display = 'block';

    if (!Array.isArray(data)) {
        return;
    }

    if (data.length === 0 && reset) {
        document.getElementById('locationResults').innerHTML = cityHTML;
        applyHeaderSuggestionCategoryLimits();
        document.getElementById('listingResults').innerHTML = isMlsKey
            ? '<div style="padding:12px;color:#666;">MLS listing not found in TREB feed. Try again later or search by address.</div>'
            : (cityHTML ? '' : '<div style="padding:12px;color:#666;">No listings found. Try another address or filter.</div>');
        return;
    }

    if (data.length === 0) {
        return;
    }

    let addressHTML = '';
    let listingsHTML = '';

    data.forEach(item => {
        const displayAddress = item.UnparsedAddress || item.building_address || '';
        const blurClass = guestBlurClass(item);
        const unitBadge = item.grouped && item.unit_count > 1
            ? `<span style="background:var(--primary-color,#db1d23);color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:6px;">${item.unit_count} units</span>`
            : '';
        const statusLabel = (item.MlsStatus === 'New')
            ? (item.TransactionType === 'For Sale' ? 'For Sale' : (item.TransactionType === 'For Lease' ? 'For Lease' : 'For Sale'))
            : (SOLD_STATUSES.includes(item.MlsStatus)
                ? item.MlsStatus
                : (item.MlsStatus ?? ''));

        addressHTML += `
            <div class="location-item address-item">
                <a href="${buildPropertyUrl(item)}" style="width: 100%" class="${blurClass}">📍 ${displayAddress}${unitBadge}</a>
            </div>
        `;

        const loginOverlay = (!isLoggedIn && isSoldListing(item))
            ? `<div class="property-login-overlay" style="position:relative;inset:auto;background:rgba(0,0,0,0.55);margin:8px 0;padding:16px;border-radius:8px;">
                    <div class="property-login-overlay-content text-center">
                        <p class="property-login-overlay-caption" style="color:#fff;font-size:13px;line-height:1.5;margin-bottom:12px;">
                            Local real estate board's rules require you to validate login to see this property.
                            <a href="#modalLogin" data-bs-toggle="modal" style="color:#fff;font-weight:600;text-decoration:underline;">(Full Details Here)</a>
                        </p>
                        <a href="#modalLogin" data-bs-toggle="modal" class="btn btn-light fw-bold">Confirm Login</a>
                    </div>
               </div>`
            : '';

        listingsHTML += `
            ${loginOverlay}
            <a href="${buildPropertyUrl(item)}" style="width: 100%" class="${blurClass}">
                <div class="listing-item" style="width: 100%">
                    <img src="${item.MediaURL}"
                    data-key="${item.ListingKey}"
                            class="property-image"
                            loading="lazy"
                            alt="${[item.UnparsedAddress, item.PropertySubType, item.ListingKey].filter(Boolean).join(' - ') || 'Property listing'}"
                            style="width:100px;height:80px;object-fit:cover;border-radius:6px;"
                            onerror="this.onerror=null;this.src='{{ \App\Support\SerikMediaUrl::placeholder() }}'"
                        />
                    <div style="width: 100%">
                        <div class="price">
                            $${Number(item.ListPrice || 0).toLocaleString()}
                            <p style="float:right">${statusLabel}</p>
                        </div>
                        <div>${displayAddress}${unitBadge}</div>
                        <p style="float:left">${item.PropertySubType || ''}</p>
                        <small style="float:right">
                            🛏 ${item.BedroomsTotal ?? 0}
                            🛁 ${item.BathroomsTotalInteger ?? 0}
                            🚘 ${(item.ParkingTotal ?? 0) - (item.ParkingSpaces ?? 0)}
                        </small>
                    </div>
                </div>
            </a>
        `;

        if (item.grouped && Array.isArray(item.units) && item.units.length > 1) {
            item.units.slice(1, 6).forEach(unit => {
                const unitBlur = guestBlurClass(unit);
                listingsHTML += `
                    <a href="${buildPropertyUrl(unit)}" style="width:100%;padding-left:20px;display:block;" class="${unitBlur}">
                        <div class="listing-item" style="width:100%;opacity:0.9;font-size:13px;">
                            <div>↳ ${unit.UnparsedAddress || unit.address || ''}</div>
                            <small>$${Number(unit.ListPrice || 0).toLocaleString()} · ${unit.MlsStatus || ''}</small>
                        </div>
                    </a>
                `;
            });
            if (item.units.length > 6) {
                listingsHTML += `<div style="padding-left:20px;font-size:12px;color:#666;">+ ${item.units.length - 6} more units</div>`;
            }
        }
    });

    const finalSuggestions = cityHTML + addressHTML;

    if (reset) {
        document.getElementById('locationResults').innerHTML = finalSuggestions || cityHTML;
        applyHeaderSuggestionCategoryLimits();
        document.getElementById('listingResults').innerHTML = listingsHTML;
    } else {
        document.getElementById('listingResults').insertAdjacentHTML('beforeend', listingsHTML);
    }

    setTimeout(loadImages, 0);
}




function loadImages() {

    document.querySelectorAll(".property-image").forEach(img => {

        const listingKey = img.dataset.key;

        // Skip if already loaded successfully
        if (img.dataset.loaded === 'true') return;

        if (img.complete && img.naturalWidth > 0 && !img.src.includes('placeholder.png')) {
            img.dataset.loaded = 'true';
            return;
        }

        if (!listingKey) {
            return;
        }

        const origin = window.location.origin.replace(/\/$/, '');
        const proxyUrl = origin + '/storage/properties/treb/' + encodeURIComponent(String(listingKey).toUpperCase()) + '/cover.webp';

        if (!img.src.includes('/storage/properties/treb/') && img.src !== proxyUrl) {
            img.src = proxyUrl;
        }

        if (img.dataset.fetchBound !== '1') {
            img.dataset.fetchBound = '1';
            img.addEventListener('error', function onImgError() {
                if (img.dataset.loaded === 'true') {
                    return;
                }
                fetch(`/api/v1/property-image/${listingKey}`)
                    .then(res => res.json())
                    .then(data => {
                        const imgUrl = data.media || (Array.isArray(data.images) ? data.images[0] : '');
                        if (imgUrl) {
                            img.src = imgUrl;
                            img.style.opacity = '0';
                            img.onload = () => {
                                img.style.transition = 'opacity 0.3s ease';
                                img.style.opacity = '1';
                            };
                        } else if (!img.src.includes('placeholder.png')) {
                            img.onerror = null;
                            img.src = '{{ \App\Support\SerikMediaUrl::placeholder() }}';
                        }
                        img.dataset.loaded = 'true';
                    })
                    .catch(() => {
                        img.dataset.loaded = 'true';
                    });
            }, { once: true });
        }

        if (img.complete && img.naturalWidth > 0) {
            img.dataset.loaded = 'true';
        }
    });
}


function importProperty(key){
    fetch(`/api/v1/add-single-property/${key}`).catch(()=>{});
}


// LOAD MORE CLICK
if (loadMoreBtn) {
loadMoreBtn.addEventListener("click", function(){
    if (loader) {
        loader.style.display = "flex";
    }
    skip += 10;
    loadResults(currentKeyword, false);

});
}

if (clearBtn) {
clearBtn.addEventListener("click", function(){
if (loader) {
loader.style.display = "none";
}
        if (dropdown) {
        dropdown.style.display = "none";
        }
        const smartInputEl = document.getElementById("smartInput");
        if (smartInputEl) {
            smartInputEl.value='';
        }

});
}


let selectedFilters = {
    transaction: '',
    status: ''
};

document.querySelectorAll('.filter-btn').forEach(btn => {

    btn.addEventListener('click', function() {

        let type = this.dataset.type;
        let value = this.dataset.value;

        // if already active → deactivate
        if (this.classList.contains('active')) {

            this.classList.remove('active');
            selectedFilters[type] = '';

        } else {

            // Remove active from same group
            document.querySelectorAll(`.filter-btn[data-type="${type}"]`)
                .forEach(b => b.classList.remove('active'));

            this.classList.add('active');
            selectedFilters[type] = value;
        }

        skip = 0;
        loadResults(document.getElementById("smartInput").value, true);
    });
    
    
    
    

});



 document.addEventListener("click", function (e) {
    const dropdown = document.getElementById("searchDropdown");
    const input = document.getElementById("smartInput");

    // If click is NOT inside dropdown AND NOT on input
    if (!dropdown || !input) {
        return;
    }
    if (
        !dropdown.contains(e.target) &&
        !input.contains(e.target)
    ) {
        dropdown.style.display = "none";
    }
});


document.querySelectorAll('.navigation li').forEach(li => {

    const submenu = li.querySelector(':scope > ul');

    if (submenu && !li.querySelector(':scope > .mega-dropdown')){
        li.classList.add('has-dropdown');

        const navLink = li.querySelector('a');
        if (!navLink) {
            return;
        }
        navLink.addEventListener('click', function(e){

            if (window.innerWidth > 991) {
                return;
            }

            // prevent link jump
            e.preventDefault();

            li.classList.toggle('open');
        });
    }

});

</script>

