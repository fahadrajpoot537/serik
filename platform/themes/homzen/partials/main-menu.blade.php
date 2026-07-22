
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
    .has-dropdown::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        height: 14px;
        z-index: 1001;
    }

    /* Only one dropdown open at a time — JS controls .is-active (no :hover) */
    .has-dropdown.is-active .mega-dropdown {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateX(-50%) translateY(0);
    }

    .menu-arrow {
        display: none;
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
        <li @class([
            'menu-item',
            'has-dropdown' => $row->has_child,
            'current' => $row->active,
            $row->css_class
        ])>

            <a href="{{ $row->has_child ? '#' : \App\Support\MenuUrl::resolve($row->url) }}"
               target="{{ $row->target }}"
               class="menu-link">
                {!! BaseHelper::clean($row->icon_html) !!}
                {{ $row->title }}

                @if (!$row->has_child)
                    <span class="menu-arrow"></span>
                @endif
            </a>

            @if ($row->has_child && $row->title=='Buy')
                <div class="mega-dropdown" >
<div class="mega-close">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left" >
                           
                            <a href="{{ url('/map') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>
                            <img src="https://serik.ca/storage/269369802-11088650.png" style="width:100%;" alt="{{ __('Serik Realty Ontario property search guide') }}"/>

                            
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
             @elseif ($row->has_child && $row->title=='Sell')    
                 <div class="mega-dropdown">
<div class="mega-close">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left">
                            
                            <a href="{{ url('/properties') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="https://serik.ca/storage/269369790-11088646.png" style="width:100%;" alt="{{ __('Serik Realty services and resources') }}"/>
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
            @elseif ($row->has_child)
                <div class="mega-dropdown">
<div class="mega-close">✕ Close</div>
                    <div class="mega-wrapper">

                        {{-- LEFT --}}
                        <div class="mega-left">
                            
                            <a href="{{ url('/properties') }}" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="https://serik.ca/storage/269369790-11088646.png" style="width:100%;" alt="{{ __('Serik Realty services and resources') }}"/>
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

    function closeMegaMenu() {
        document.querySelectorAll('.mega-dropdown').forEach(menu => {
            menu.classList.remove('show');
        });
        document.querySelectorAll('.has-dropdown').forEach(item => {
            item.classList.remove('is-open', 'is-active');
        });
        const overlay = document.querySelector('.mega-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    window.closeMegaMenu = closeMegaMenu;

    function setActiveMegaItem(item) {
        document.querySelectorAll('.has-dropdown.is-active').forEach(el => {
            if (el !== item) {
                el.classList.remove('is-active');
            }
        });
        if (item) {
            item.classList.add('is-active');
        }
    }

    document.querySelectorAll('.has-dropdown > .menu-link').forEach(link => {
        link.addEventListener('click', function (e) {
            if (isDesktop()) {
                e.preventDefault();
                return;
            }
            e.preventDefault();

            const dropdown = this.nextElementSibling;
            const overlay = document.querySelector('.mega-overlay');
            const parent = this.closest('.has-dropdown');

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
        });
    });

    document.querySelectorAll('.has-dropdown').forEach(item => {
        item.addEventListener('mouseenter', () => {
            if (!isDesktop()) {
                return;
            }
            clearTimeout(closeTimer);
            setActiveMegaItem(item);
        });

        item.addEventListener('mouseleave', () => {
            if (!isDesktop()) {
                return;
            }
            closeTimer = setTimeout(() => {
                item.classList.remove('is-active');
            }, 220);
        });
    });

    document.querySelectorAll('.mega-dropdown').forEach(dropdown => {
        dropdown.addEventListener('mouseenter', () => {
            if (!isDesktop()) {
                return;
            }
            clearTimeout(closeTimer);
        });

        dropdown.addEventListener('mouseleave', () => {
            if (!isDesktop()) {
                return;
            }
            const parent = dropdown.closest('.has-dropdown');
            closeTimer = setTimeout(() => {
                parent?.classList.remove('is-active');
            }, 220);
        });
    });

    const overlay = document.querySelector('.mega-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMegaMenu);
    }

    document.querySelectorAll('.mega-close').forEach(btn => {
        btn.addEventListener('click', closeMegaMenu);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMegaMenu();
        }
    });
})();
</script>
