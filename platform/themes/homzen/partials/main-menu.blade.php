
<style>
/* ===== BASE ===== */
.mega-menu {
    display: flex;
    max-width: 100%;
    margin: 0 auto;
    gap: 16px;
}

.mega-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.menu-item {
    position: relative;
}

.menu-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 20px;
    font-weight: 600;
    text-decoration: none;
}

/* ===== MEGA DROPDOWN ===== */
.mega-dropdown {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(8px);
    width: min(920px, calc(100vw - 48px));
    max-width: 920px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.14);
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    z-index: 1000;
    transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s ease;
}

/* Layout */
.mega-wrapper {
    display: flex;
    gap: 20px;
}

.mega-left {
    width: 28%;
    flex-shrink: 0;
    background: #f6f8fc;
    padding: 16px;
    border-radius: 12px;
}

.mega-right {
    flex: 1;
    min-width: 0;
}

.mega-right a {
    display: block;
    text-decoration: none;
    color: #334155;
    font-size: 14px;
    line-height: 1.45;
    padding: 5px 8px;
    border-radius: 6px;
    transition: background 0.15s ease, color 0.15s ease;
}

.mega-right a:hover {
    background: #eef4ff;
    color: #0255a1;
}

.mega-right h4 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 10px;
}

/* Feature box */
.feature-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 12px;
    text-decoration: none;
    color: #0255a1;
    font-weight: 600;
    font-size: 14px;
    border: 1px solid #dbeafe;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.feature-box:hover {
    background: #eff6ff;
    border-color: #93c5fd;
}

/* Images */
.mega-left img {
    width: 100%;
    border-radius: 8px;
}

/* Titles inside columns */
.main-city {
    font-weight: 700;
    padding: 10px 8px 4px;
    font-size: 14px;
    color: #0255a1 !important;
    margin-top: 4px;
}

.main-city:first-child {
    margin-top: 0;
    padding-top: 4px;
}

.mega-column a:not(.main-city) {
    padding-left: 14px;
    font-size: 13px;
    color: #475569;
}

/* ===== DESKTOP ===== */
@media (min-width: 992px) {
    .mega-close {
        display: none !important;
    }

    .mega-dropdown {
        position: fixed !important;
        top: var(--serik-mega-top, 90px) !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;
        min-width: 100vw !important;
        max-width: none !important;
        margin: 0 !important;
        transform: none !important;
        padding: 20px 32px 22px;
        border-radius: 0 0 18px 18px;
        box-sizing: border-box !important;
        z-index: 2147483000 !important;
    }

    /* Tiny gap only — a tall full-width bridge covers sibling nav items and sticks the previous menu. */
    .has-dropdown.is-active .mega-dropdown::before,
    .mega-dropdown.serik-mega-portal.is-mega-open::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 100%;
        height: 10px;
        pointer-events: none;
    }

    .mega-dropdown:not(.is-mega-open),
    .mega-dropdown.serik-mega-portal:not(.is-mega-open),
    body > .mega-dropdown.serik-mega-portal:not(.is-mega-open) {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    /* Only one dropdown open at a time — JS controls .is-active / .is-mega-open (no :hover). */
    .has-dropdown.is-active > .mega-dropdown,
    .mega-dropdown.serik-mega-portal.is-mega-open,
    body > .mega-dropdown.serik-mega-portal.is-mega-open,
    .mega-dropdown.serik-mega-portal.is-mega-open a {
        opacity: 1;
        visibility: visible;
        pointer-events: auto !important;
        transform: none !important;
    }

    #header.main-header .main-menu .navigation > li.menu-item > a.menu-link:hover,
    #header.main-header .main-menu .navigation > li.menu-item.is-active > a.menu-link {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.16) !important;
        box-shadow: inset 0 -2px 0 #fff;
    }

    #header.main-header .main-menu .navigation > li.menu-item.current > a.menu-link {
        color: #fff !important;
        box-shadow: inset 0 -2px 0 #fff;
    }

    #header.main-header .main-menu .navigation > li.menu-item.current.is-active > a.menu-link,
    #header.main-header .main-menu .navigation > li.menu-item.current:hover > a.menu-link {
        background: rgba(255, 255, 255, 0.16) !important;
    }

    #header.main-header .main-menu .navigation > li.menu-item > a.menu-link:focus-visible {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.2) !important;
        outline: 2px solid #fff !important;
        outline-offset: 3px !important;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35) !important;
        border-radius: 4px;
    }

    html.serik-mega-open iframe[title*="chat"],
    html.serik-mega-open iframe[title*="Chat"],
    html.serik-mega-open .widget-visible {
        pointer-events: none !important;
        visibility: hidden !important;
    }

    .mega-left {
        width: 18%;
        max-width: 260px;
    }

    .mega-column a,
    .mega-right a,
    .main-city,
    .mega-dropdown.serik-mega-portal a,
    body > .mega-dropdown a {
        position: relative;
        z-index: 2;
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
        pointer-events: auto !important;
    }

    .mega-column a:not(.main-city) {
        font-size: 13px;
        padding: 4px 8px;
    }

    .main-city {
        font-size: 13.5px;
        padding: 8px 8px 2px;
    }

    .menu-arrow {
        display: none;
    }

    /* Header property search is open — keep mega panels fully suppressed (JS also blocks activation). */
    html.serik-header-search-active .mega-dropdown,
    html.serik-header-search-active .has-dropdown.is-active > .mega-dropdown,
    html.serik-header-search-active .mega-dropdown.serik-mega-portal,
    html.serik-header-search-active .mega-dropdown.serik-mega-portal.is-mega-open,
    html.serik-header-search-active body > .mega-dropdown,
    html.serik-header-search-active body > .mega-dropdown.serik-mega-portal,
    html.serik-header-search-active body > .mega-dropdown.serik-mega-portal.is-mega-open,
    html.serik-header-search-active body > .mega-dropdown a,
    html.serik-header-search-active .mega-dropdown a,
    html.serik-header-search-active body > .mega-dropdown.serik-mega-portal a,
    html.serik-header-search-active body > .mega-dropdown.serik-mega-portal.is-mega-open a {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
}

@media (min-width: 992px) {
    .mega-overlay {
        display: none !important;
    }
}

/* ===== MOBILE FIXED ===== */
@media (max-width: 991px) {

    .mega-dropdown {
        position: static;
        transform: none;
        width: 100%;
        zoom: 1;
        box-shadow: none;
        padding: 16px;
        border-radius: 0;
        display: none;
    }

    .mega-dropdown.show {
        display: block;
    }

    .mega-wrapper {
        flex-direction: column;
        gap: 15px;
    }

    .mega-left,
    .mega-right {
        width: 100%;
    }

    .mega-menu {
        flex-direction: column;
    }

    .mega-column {
        width: 100%;
    }

    .menu-arrow {
        border: solid #000;
        border-width: 0 2px 2px 0;
        display: inline-block;
        padding: 4px;
        transform: rotate(45deg);
        margin-left: 10px;
    }

    /* Better spacing for links */
    .mega-column a {
        padding: 6px 0;
        font-size: 16px;
    }

    /* Optional: add divider between sections */
    .mega-column + .mega-column {
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
}



/* ===== MOBILE POPUP MODE ===== */
@media (max-width: 991px) {

    .mega-dropdown {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #fff;
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        overflow-y: auto;
        padding: 20px;
        display: block; /* IMPORTANT */
    }

    .mega-dropdown.show {
        transform: translateY(0);
    }

    /* Overlay background */
    .mega-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        display: none;
    }

    .mega-overlay.show {
        display: block;
    }

    /* Close button */
    .mega-close {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 15px;
        display: inline-block;
        cursor: pointer;
    }

    .mega-wrapper {
        flex-direction: column;
    }

    .mega-left,
    .mega-right {
        width: 100%;
    }
}
</style>

<ul {!! BaseHelper::clean($options) !!} class="main-menu">
    @foreach ($menu_nodes as $row)
        @php
            $menuKey = \Illuminate\Support\Str::slug((string) $row->title);
            if ($menuKey === '') {
                $menuKey = 'item-' . (string) ($row->id ?? 'x');
            }
            $titleNorm = mb_strtolower(trim((string) $row->title));
            $isBlogsItem = in_array($titleNorm, ['blog', 'blogs'], true);
            $childCount = ($row->has_child && isset($row->child) && is_countable($row->child))
                ? count($row->child)
                : 0;
            $showMega = (bool) $row->has_child && ! ($isBlogsItem && $childCount === 0);
            $panelId = $showMega ? 'serik-mega-' . $menuKey . '-' . (string) ($row->id ?? $menuKey) : null;
            $parentHref = \App\Support\MenuUrl::resolve($row->url);
        @endphp
        <li @class([
            'menu-item',
            'has-dropdown' => $showMega,
            'current' => $row->active,
            $row->css_class
        ])
            @if($showMega) data-menu-key="{{ $menuKey }}" @endif>

            <a href="{{ $parentHref }}"
               target="{{ $row->target }}"
               class="menu-link"
               @if($row->active) aria-current="page" @endif
               @if($showMega) aria-haspopup="true" aria-expanded="false" aria-controls="{{ $panelId }}" @endif>
                {!! BaseHelper::clean($row->icon_html) !!}
                {{ $row->title }}

                @if (!$showMega)
                    <span class="menu-arrow"></span>
                @endif
            </a>

            @if ($showMega && $row->title=='Buy')
                <div class="mega-dropdown" id="{{ $panelId }}" data-menu-parent="{{ $menuKey }}">
<div class="mega-close" role="button" tabindex="0" aria-label="{{ __('Close menu') }}">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left" >
                           
                            <a href="{{ url('/map') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>
                            <img src="{{ \App\Support\SerikMediaUrl::cmsImageUrl('269369802-11088650.png', 'medium') }}" width="400" height="300" loading="lazy" decoding="async" style="width:100%;" alt="{{ __('Serik Realty Ontario property search guide') }}"/>

                            
                        </div>

                        {{-- RIGHT --}}
                        <div class="mega-right" >
                            <div class="mega-menu">
                                <div class="mega-column">
                                    
                                    <a href="{{ url('/on/houses-for-sale-in-brampton/map') }}" class="main-city">
                                        Houses for Sale in Brampton
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-brampton/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-brampton/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-brampton/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-brampton/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-mississauga/map') }}" class="main-city">
                                        Houses for Sale in Mississauga
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-mississauga/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-mississauga/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-mississauga/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-mississauga/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-toronto/map') }}" class="main-city">
                                        Houses for Sale in Toronto
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-toronto/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-toronto/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-toronto/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-toronto/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                </div>
                                
                                <div class="mega-column">
                                
                                    <a href="{{ url('/on/houses-for-sale-in-vaughan/map') }}" class="main-city">
                                        Houses for Sale in Vaughan
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-vaughan/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-vaughan/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-vaughan/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-vaughan/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-oakville/map') }}" class="main-city">
                                        Houses for Sale in Oakville
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-oakville/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-oakville/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-oakville/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-oakville/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-milton/map') }}" class="main-city">
                                        Houses for Sale in Milton
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-milton/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-milton/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-milton/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-milton/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                </div>
                                
                                <div class="mega-column">
                                
                                    <a href="{{ url('/on/houses-for-sale-in-hamilton/map') }}" class="main-city">
                                        Houses for Sale in Hamilton
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-hamilton/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-hamilton/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-hamilton/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-hamilton/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-kitchener/map') }}" class="main-city">
                                        Houses for Sale in Kitchener
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-kitchener/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-kitchener/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-kitchener/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-kitchener/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                
                                    <a href="{{ url('/on/houses-for-sale-in-ottawa/map') }}" class="main-city">
                                        Houses for Sale in Ottawa
                                    </a>
                                
                                    <a href="{{ url('/on/detached-houses-for-sale-in-ottawa/map') }}">
                                        &gt; Detached Houses
                                    </a>
                                
                                    <a href="{{ url('/on/semi-detached-houses-for-sale-in-ottawa/map') }}">
                                        &gt; Semi-Detached Homes
                                    </a>
                                
                                    <a href="{{ url('/on/townhouses-for-sale-in-ottawa/map') }}">
                                        &gt; Townhouses for Sale
                                    </a>
                                
                                    <a href="{{ url('/on/condos-for-sale-in-ottawa/map') }}">
                                        &gt; Condos & Apartments
                                    </a>
                                
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
             @elseif ($showMega && $row->title=='Sell')    
                 <div class="mega-dropdown" id="{{ $panelId }}" data-menu-parent="{{ $menuKey }}">
<div class="mega-close" role="button" tabindex="0" aria-label="{{ __('Close menu') }}">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left">
                            
                            <a href="{{ url('/properties') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="{{ \App\Support\SerikMediaUrl::cmsImageUrl('269369790-11088646.png', 'medium') }}" width="400" height="300" loading="lazy" decoding="async" style="width:100%;" alt="{{ __('Serik Realty services and resources') }}"/>
                        </div>

                        {{-- RIGHT --}}
                        <div class="mega-right">
                            <h4>Features</h4>

                                <a href="{{ url('/free-home-evaluation') }}">&gt; Free Home Evaluation</a>
                                <a href="https://serik.ca/tips-for-home-selling">&gt; Tips For Home Selling</a>
                                <a href="https://www.google.com/search?sca_esv=5007095e94022ac2&biw=1536&bih=738&sxsrf=ANbL-n6A5KhK6IOO-0FVdTuAlRbAQEQ3MA:1776271594886&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOfvoulo1K3CdIC5M45JUCC4r873m2qwN7EicjGCMgYWtNzBTKNl8PkUaJZYYaU6q_EC5LNKLYfGq1WitFm3vQOmt5TFOzgO3dLn3bfm3a6YNV2Pe8g%3D%3D&q=Serik+Realty+Inc.+Reviews&sa=X&ved=2ahUKEwjz-7-rp_CTAxVmVqQEHbnzCsUQ0bkNegQIJRAH" target="_blank">&gt; Customers' testimonials</a>
                        </div>

                    </div>
                </div>   
            @elseif ($showMega)
                <div class="mega-dropdown" id="{{ $panelId }}" data-menu-parent="{{ $menuKey }}">
<div class="mega-close" role="button" tabindex="0" aria-label="{{ __('Close menu') }}">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left">
                            
                            <a href="{{ url('/properties') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="{{ \App\Support\SerikMediaUrl::cmsImageUrl('269369790-11088646.png', 'medium') }}" width="400" height="300" loading="lazy" decoding="async" style="width:100%;" alt="{{ __('Serik Realty services and resources') }}"/>
                        </div>

                        {{-- RIGHT --}}
                        <div class="mega-right">
                            <h4>Features</h4>

                            @foreach ($row->child as $child)
                                <a href="{{ \App\Support\MenuUrl::resolve($child->url) }}">
                                    {{ $child->title }}
                                </a>
                            @endforeach
                        </div>

                    </div>
                </div>    
            @endif

        </li>
    @endforeach
</ul>
<div class="mega-overlay"></div>

<script>
(function () {
    const DESKTOP_BP = 992;
    let closeTimer = null;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_BP;
    }

    function isHeaderSearchPanelOpen() {
        const dropdown = document.getElementById('searchDropdown');
        if (!dropdown || dropdown.style.display !== 'block') {
            return false;
        }
        try {
            return window.getComputedStyle(dropdown).display === 'block';
        } catch (err) {
            return true;
        }
    }

    function isHeaderSearchActive() {
        if (document.documentElement.classList.contains('serik-header-search-active')) {
            return true;
        }
        const header = document.getElementById('header');
        if (header && header.classList.contains('serik-header-search-active')) {
            return true;
        }
        const input = document.getElementById('smartInput');
        if (input && document.activeElement === input) {
            return true;
        }
        return isHeaderSearchPanelOpen();
    }

    function updateMegaTop() {
        const header = document.getElementById('header');
        if (!header) {
            return;
        }
        const bottom = Math.round(header.getBoundingClientRect().bottom);
        document.documentElement.style.setProperty('--serik-mega-top', bottom + 'px');
        return bottom;
    }

    document.querySelectorAll('.has-dropdown').forEach((item) => {
        const panel = item.querySelector(':scope > .mega-dropdown');
        if (panel) {
            panel._megaHome = item;
            item._megaPanel = panel;
        }
    });

    function restoreMega(dropdown) {
        if (!dropdown) {
            return;
        }
        const home = dropdown._megaHome;
        dropdown.classList.remove('serik-mega-portal', 'is-mega-open');
        dropdown.style.removeProperty('position');
        dropdown.style.removeProperty('left');
        dropdown.style.removeProperty('right');
        dropdown.style.removeProperty('top');
        dropdown.style.removeProperty('width');
        dropdown.style.removeProperty('min-width');
        dropdown.style.removeProperty('max-width');
        dropdown.style.removeProperty('z-index');
        dropdown.style.removeProperty('transform');
        dropdown.style.removeProperty('margin');
        dropdown.style.removeProperty('pointer-events');
        dropdown.style.removeProperty('opacity');
        dropdown.style.removeProperty('visibility');
        if (home && dropdown.parentElement !== home) {
            home.appendChild(dropdown);
        }
    }

    function portalMega(item) {
        const dropdown = item && item._megaPanel;
        if (!dropdown || !isDesktop() || isHeaderSearchActive()) {
            return;
        }

        document.querySelectorAll('.mega-dropdown.serik-mega-portal').forEach((panel) => {
            if (panel !== dropdown) {
                restoreMega(panel);
            }
        });

        const top = updateMegaTop();
        document.body.appendChild(dropdown);
        dropdown.classList.add('serik-mega-portal', 'is-mega-open');
        dropdown.style.position = 'fixed';
        dropdown.style.left = '0';
        dropdown.style.right = '0';
        dropdown.style.top = (top || 90) + 'px';
        dropdown.style.width = '100vw';
        dropdown.style.minWidth = '100vw';
        dropdown.style.maxWidth = 'none';
        dropdown.style.margin = '0';
        dropdown.style.transform = 'none';
        dropdown.style.zIndex = '2147483000';
        dropdown.style.pointerEvents = 'auto';
        dropdown.style.opacity = '1';
        dropdown.style.visibility = 'visible';
        document.documentElement.classList.add('serik-mega-open');
    }

    function closeMegaMenu(options) {
        const restoreFocus = options && options.restoreFocus;
        const trigger = restoreFocus
            ? document.querySelector('.has-dropdown.is-active > .menu-link, .has-dropdown.is-open > .menu-link')
            : null;
        clearTimeout(closeTimer);
        document.documentElement.classList.remove('serik-mega-open');
        document.querySelectorAll('.mega-dropdown').forEach((menu) => {
            menu._megaLock = false;
            menu.classList.remove('show');
            restoreMega(menu);
        });
        document.querySelectorAll('.has-dropdown').forEach((item) => {
            item.classList.remove('is-open', 'is-active');
            const link = item.querySelector(':scope > .menu-link');
            if (link) {
                link.setAttribute('aria-expanded', 'false');
            }
        });
        const overlay = document.querySelector('.mega-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
        if (trigger && typeof trigger.focus === 'function') {
            trigger.focus();
        }
    }

    window.closeMegaMenu = closeMegaMenu;

    function setActiveMegaItem(item) {
        if (isHeaderSearchActive()) {
            closeMegaMenu();
            return;
        }
        document.querySelectorAll('.has-dropdown.is-active').forEach((el) => {
            if (el !== item) {
                el.classList.remove('is-active');
                restoreMega(el._megaPanel);
                const prev = el.querySelector(':scope > .menu-link');
                if (prev) {
                    prev.setAttribute('aria-expanded', 'false');
                }
            }
        });
        if (item) {
            item.classList.add('is-active');
            const link = item.querySelector(':scope > .menu-link');
            if (link) {
                link.setAttribute('aria-expanded', 'true');
            }
            portalMega(item);
        }
    }

    let lastPointerX = 0;
    let lastPointerY = 0;

    function pointerOverMega() {
        const el = document.elementFromPoint(lastPointerX, lastPointerY);
        if (!el || !el.closest) {
            return false;
        }
        return !!(el.closest('.mega-dropdown') || el.closest('.has-dropdown'));
    }

    function scheduleClose(item) {
        clearTimeout(closeTimer);
        closeTimer = setTimeout(() => {
            const panel = item && item._megaPanel;
            if (panel && panel._megaLock) {
                return;
            }
            if (pointerOverMega()) {
                return;
            }
            closeMegaMenu();
        }, 180);
    }

    document.querySelectorAll('.has-dropdown > .menu-link').forEach((link) => {
        link.addEventListener('click', function (e) {
            if (isDesktop() && isHeaderSearchActive()) {
                e.preventDefault();
                closeMegaMenu();
                return;
            }
            if (isDesktop()) {
                e.preventDefault();
                return;
            }
            e.preventDefault();

            const parent = this.closest('.has-dropdown');
            const dropdown = parent && parent._megaPanel;
            const overlay = document.querySelector('.mega-overlay');

            closeMegaMenu();

            if (dropdown) {
                dropdown.classList.add('show');
            }
            if (parent) {
                parent.classList.add('is-open');
            }
            if (overlay) {
                overlay.classList.add('show');
            }
            this.setAttribute('aria-expanded', 'true');
        });

        link.addEventListener('keydown', function (e) {
            if (!isDesktop() || isHeaderSearchActive()) {
                return;
            }
            if (e.key !== 'ArrowDown' && e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            const parent = this.closest('.has-dropdown');
            if (!parent) {
                return;
            }
            setActiveMegaItem(parent);
            this.setAttribute('aria-expanded', 'true');
            const panel = parent._megaPanel;
            const first = panel && panel.querySelector('a[href]');
            if (first) {
                first.focus();
            }
        });
    });

    document.querySelectorAll('.has-dropdown').forEach((item) => {
        item.addEventListener('mouseenter', () => {
            if (!isDesktop()) {
                return;
            }
            if (isHeaderSearchActive()) {
                closeMegaMenu();
                return;
            }
            clearTimeout(closeTimer);
            setActiveMegaItem(item);
        });

        item.addEventListener('mouseleave', (e) => {
            if (!isDesktop()) {
                return;
            }
            const panel = item._megaPanel;
            const next = e.relatedTarget;
            if (next && (item.contains(next) || (panel && panel.contains(next)))) {
                return;
            }
            scheduleClose(item);
        });
    });

    document.querySelectorAll('.mega-dropdown').forEach((dropdown) => {
        dropdown.addEventListener('mouseenter', () => {
            if (!isDesktop()) {
                return;
            }
            if (isHeaderSearchActive()) {
                closeMegaMenu();
                return;
            }
            clearTimeout(closeTimer);
            const parent = dropdown._megaHome;
            if (parent) {
                setActiveMegaItem(parent);
            }
        });

        dropdown.addEventListener('pointerdown', (e) => {
            const link = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!link || !dropdown.contains(link)) {
                return;
            }
            const raw = (link.getAttribute('href') || '').trim();
            if (!raw || raw === '#' || raw.toLowerCase().startsWith('javascript:')) {
                return;
            }
            dropdown._megaLock = true;
            if ((link.getAttribute('target') || '') === '_blank') {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }
            window.location.href = link.href;
        }, true);

        dropdown.addEventListener('mouseleave', (e) => {
            if (!isDesktop()) {
                return;
            }
            if (dropdown._megaLock) {
                return;
            }
            const parent = dropdown._megaHome;
            const next = e.relatedTarget;
            if (parent && next && (parent.contains(next) || dropdown.contains(next))) {
                return;
            }
            scheduleClose(parent);
        });
    });

    window.addEventListener('scroll', updateMegaTop, { passive: true });
    window.addEventListener('resize', () => {
        updateMegaTop();
        if (!isDesktop()) {
            closeMegaMenu();
        }
    });

    const overlay = document.querySelector('.mega-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMegaMenu);
    }

    document.querySelectorAll('.mega-close').forEach((btn) => {
        btn.addEventListener('click', closeMegaMenu);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const open = document.querySelector('.has-dropdown.is-active, .has-dropdown.is-open, html.serik-mega-open');
            if (open) {
                e.preventDefault();
                closeMegaMenu({ restoreFocus: true });
            }
        }
    });

    document.addEventListener('pointermove', (e) => {
        lastPointerX = e.clientX;
        lastPointerY = e.clientY;
        if (!isDesktop() || !document.documentElement.classList.contains('serik-mega-open')) {
            return;
        }
        const t = e.target;
        if (t instanceof Element && (t.closest('.mega-dropdown') || t.closest('.has-dropdown'))) {
            clearTimeout(closeTimer);
            return;
        }
        scheduleClose(null);
    }, { passive: true });

    document.addEventListener('click', (e) => {
        if (!isDesktop() || !document.documentElement.classList.contains('serik-mega-open')) {
            return;
        }
        const t = e.target;
        if (t instanceof Element && (t.closest('.mega-dropdown') || t.closest('.has-dropdown'))) {
            return;
        }
        closeMegaMenu();
    }, true);
})();
</script>
