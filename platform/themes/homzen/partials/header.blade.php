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

.section-title {
    font-weight:600;
    color:#666;
    margin-bottom:10px;
}

.location-item {
    padding:10px;
    border-radius:8px;
    cursor:pointer;
}

.location-item:hover {
    background:#f4f7f9;
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
    background-color: #9dbdfd;    height: 58px;
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
        background-color:#9dbdfd;
        border-top: 1px solid #eee;
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

    
</style>

<header
    id="header" style="background-color:#9dbdfd; height: 60px;"
    @class(['main-header', 'fixed-header' => theme_option('sticky_header_enabled', true), Theme::get('headerClass')])
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
                <div class="inner-container d-flex justify-content-between align-items-center">
                    <div class="logo-box d-flex align-items-center gap-3">
                        <div class="logo">
                            <a href="{{ BaseHelper::getHomepageUrl() }}">
                                {{ Theme::getLogoImage(maxHeight: 44) }}
                            </a>
                        </div>
                        
                        
                        <div class="mobile-search-panel" id="mobileSearchPanel">

                            <div class="mobile-search-header">
                                <span  id="closeMobileSearch">✕</span>
                            </div>

                               <div class="smart-search">
                                <div class="search-box">
                                     <x-core::icon name="ti ti-search" />
                                    <input type="text" id="smartInput" placeholder="Search address, street or listing...">
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
                                {!! Menu::renderMenuLocation('main-menu', [
                                    'options' => ['class' => 'navigation clearfix'],
                                    'view' => 'main-menu',
                                ]) !!}
                            </div>
                        </nav>
                        
                    </div>
                    <div class="header-account">
                        @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                            <div class="flat-bt-top">
                                <a class="tf-btn primary" href="{{ url('/contact-us') }}">{{ __('Contact Us') }}</a>
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
<script>




document.querySelectorAll('.mobile-dropdown .dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
        const parent = this.parentElement;

        // close others (optional)
        document.querySelectorAll('.mobile-dropdown .dropdown-item').forEach(item => {
            if (item !== parent) {
                item.classList.remove('active');
                item.querySelector('.dropdown-menu').style.display = 'none';
            }
        });

        // toggle current
        parent.classList.toggle('active');
        const menu = parent.querySelector('.dropdown-menu');

        menu.style.display = menu.style.display === 'contents' ? 'none' : 'contents';
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

closeSearch.addEventListener('click', function(){
    mobilePanel.classList.remove('active');
});

const input = document.getElementById("smartInput");
const dropdown = document.getElementById("searchDropdown");
const loadMoreBtn = document.getElementById("loadMoreBtn");
const loader = document.getElementById("dropdownLoader");
const clearBtn = document.getElementById("clearBtn");
const SITE_BASE = @json(rtrim(url('/'), '/'));
const isLoggedIn = @json((is_plugin_active('real-estate') && auth('account')->check()) || auth()->check());
const SOLD_STATUSES = ['Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape'];
let skip = 0;
let currentKeyword = "";
loadMoreBtn.style.display = "block";
let typingTimer;
const typingDelay = 250;
let searchController = null;

function isSoldListing(item) {
    return SOLD_STATUSES.includes(item.MlsStatus);
}

function guestBlurClass(item) {
    return (!isLoggedIn && isSoldListing(item)) ? 'blurred-content' : '';
}

input.addEventListener("keyup", function () {

    let keyword = this.value;
    currentKeyword = keyword;
    skip = 0;

    clearTimeout(typingTimer);

    if(keyword.length < 2){
        dropdown.style.display = "none";
        document.getElementById("locationResults").innerHTML = "";
        document.getElementById("listingResults").innerHTML = "";
        return;
    }

    loader.style.display = "flex";
    dropdown.style.display = "block";

    typingTimer = setTimeout(() => {
        loadResults(keyword, true);
    }, typingDelay);
    
    
    
});


function buildPropertyUrl(item) {
    const slug = String(item.URL || item.url || '').replace(/^\/+/, '');

    if (!slug) {
        return `${SITE_BASE}/properties`;
    }

    return `${SITE_BASE}/properties/${slug}`;
}




function loadResults(keyword, reset = false){

    if (searchController) {
        searchController.abort();
    }
    searchController = new AbortController();

    const isMlsKey = /^[a-z]{1,2}\d{5,}$/i.test(String(keyword || '').trim());
    // Exact MLS ingest can take longer than address Meili searches.
    const searchTimeoutMs = isMlsKey ? 45000 : 15000;
    const searchTimeoutId = setTimeout(() => searchController.abort(), searchTimeoutMs);

    let url = `/api/v1/smart-search?keyword=${encodeURIComponent(keyword)}&skip=${skip}`;

    // Map buy/sell filters must not hide an exact MLS# lookup.
    if (! isMlsKey && selectedFilters.transaction) {
        url += `&transaction=${encodeURIComponent(selectedFilters.transaction)}`;
    }

    if (! isMlsKey && selectedFilters.status) {
        url += `&status=${encodeURIComponent(selectedFilters.status)}`;
    }

    fetch(url, { signal: searchController.signal, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
    .then(res => res.json())
    .then(data => {

        clearTimeout(searchTimeoutId);
        loader.style.display = "none";
        dropdown.style.display = "block";

        if(!Array.isArray(data)){
            return;
        }

        if(data.length === 0 && reset){
            document.getElementById("locationResults").innerHTML = isMlsKey
                ? '<div style="padding:12px;color:#666;">MLS listing not found in TREB feed. Try again later or search by address.</div>'
                : '<div style="padding:12px;color:#666;">No listings found. Try another address or filter.</div>';
            document.getElementById("listingResults").innerHTML = '';
            return;
        }

        if(data.length === 0){
            return;
        }

        let locationsHTML = "";
        let listingsHTML = "";

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

            locationsHTML += `
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

        if(reset){
            document.getElementById("locationResults").innerHTML = locationsHTML;
            document.getElementById("listingResults").innerHTML = listingsHTML;
        }else{
            document.getElementById("listingResults")
                .insertAdjacentHTML("beforeend", listingsHTML);
        }

       setTimeout(loadImages, 0);

    })
    .catch(err => {
        clearTimeout(searchTimeoutId);
        if (err.name === 'AbortError') {
            return;
        }
        console.log(err);
        loader.style.display = "none";
        dropdown.style.display = "none";
    });

}




function loadImages() {

    document.querySelectorAll(".property-image").forEach(img => {

        const listingKey = img.dataset.key;

        // Skip if already loaded
        if (img.dataset.loaded) return;

        // Check if current image is NOT placeholder → skip API call
        if (!img.src.includes('placeholder.png')) {
            img.dataset.loaded = "true";
            return;
        }

        // Only fetch if it's placeholder
        fetch(`/api/v1/property-image/${listingKey}`)
            .then(res => res.json())
            .then(data => {
                const imgUrl = Array.isArray(data.media) ? (data.media[0] || '') : (data.media || '');

                if (imgUrl && !imgUrl.includes('placeholder.png')) {
                    img.src = imgUrl;

                    // Smooth fade-in
                    img.style.opacity = "0";
                    img.onload = () => {
                        img.style.transition = "opacity 0.3s ease";
                        img.style.opacity = "1";
                    };
                }

                img.dataset.loaded = "true";

            })
            .catch(() => {});
    });
}


function importProperty(key){
    fetch(`/api/v1/add-single-property/${key}`).catch(()=>{});
}


// LOAD MORE CLICK
loadMoreBtn.addEventListener("click", function(){
    loader.style.display = "flex";
    skip += 10;
    loadResults(currentKeyword, false);

});

clearBtn.addEventListener("click", function(){
loader.style.display = "none";
        dropdown.style.display = "none";
        document.getElementById("smartInput").value='';

});



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

        li.querySelector('a').addEventListener('click', function(e){

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

