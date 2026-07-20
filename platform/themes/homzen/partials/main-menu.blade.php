
<style>
/* ===== BASE ===== */
.mega-menu {
    display: flex;
    max-width: 1200px;
    margin: auto;
    gap: 20px;
}

.mega-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
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
    zoom: 0.7;
    position: absolute;
    top: 100%;
    left: 55%;
    transform: translateX(-50%);
    width: 95vw;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,.12);
    padding: 24px;
    display: none;
    z-index: 1000;
}

/* Layout */
.mega-wrapper {
    display: flex;
    gap: 30px;
}

.mega-left {
    width: 25%;
    background: #f6f8fc;
    padding: 24px;
    border-radius: 14px;
}

.mega-right {
    width: 75%;
}

.mega-right a {
    display: block;
    text-decoration: none;
}

/* Feature box */
.feature-box {
    display: flex;
    gap: 6px;
    padding: 14px;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 12px;
    text-decoration: none;
}

/* Images */
.mega-left img {
    width: 100%;
}

/* Titles inside columns */
.main-city {
    font-weight: 600;
    padding: 5px 0;
    font-size: 20px;
}

/* ===== DESKTOP ===== */
@media (min-width: 992px) {
    .has-dropdown:hover .mega-dropdown {
        display: block;
    }

    .menu-arrow {
        display: none;
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

            <a href="{{ $row->url === 'https://serik.ca/properties' ? '#' : $row->url }}"
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
                            <img src="https://serik.ca/storage/269369802-11088650.png" style="width:100%;"/>

                            
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
                            
                            <a href="properties" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="https://serik.ca/storage/269369790-11088646.png" style="width:100%;"/>
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
                            
                            <a href="properties" class="feature-box">
                                <span>🏠</span>
                                <span>Find Home →</span>
                            </a>

                            <img src="https://serik.ca/storage/269369790-11088646.png" style="width:100%;"/>
                        </div>

                        {{-- RIGHT --}}
                        <div class="mega-right">
                            <h4>Features</h4>

                            @foreach ($row->child as $child)
                                <a href="{{ $child->url }}">
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
document.querySelectorAll('.has-dropdown > .menu-link').forEach(link => {
    link.addEventListener('click', function (e) {
        if (window.innerWidth <= 991) {
            e.preventDefault();

            const dropdown = this.nextElementSibling;
            const overlay = document.querySelector('.mega-overlay');

            // Close all first
            document.querySelectorAll('.mega-dropdown').forEach(menu => {
                menu.classList.remove('show');
            });

            // Open current
            dropdown.classList.add('show');
            overlay.classList.add('show');
        }
    });
});

// Close on overlay click
document.querySelector('.mega-overlay').addEventListener('click', () => {
    closeMegaMenu();
});

// Close button
document.querySelectorAll('.mega-close').forEach(btn => {
    btn.addEventListener('click', closeMegaMenu);
});

function closeMegaMenu() {
    document.querySelectorAll('.mega-dropdown').forEach(menu => {
        menu.classList.remove('show');
    });
    document.querySelector('.mega-overlay').classList.remove('show');
}
</script>
