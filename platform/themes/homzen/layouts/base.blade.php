<!DOCTYPE html>
<html {!! Theme::htmlAttributes() !!}>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport"/>
<meta name="google-site-verification" content="DnbR_f8W1AL0-M1AWElchvX8AZQQae51aLL_FNhq7rE" /><!--Google SIte Verification-->
@php
    $serikThemeOptions = [
        'primary_color' => theme_option('primary_color', '#db1d23'),
        'hover_color' => theme_option('hover_color', '#cd380f'),
        'top_header_background_color' => theme_option('top_header_background_color', '#f7f7f7'),
        'top_header_text_color' => theme_option('top_header_text_color', '#161e2d'),
        'main_header_background_color' => theme_option('main_header_background_color', '#ffffff'),
        'main_header_text_color' => theme_option('main_header_text_color', '#161e2d'),
        'main_header_border_color' => theme_option('main_header_border_color', '#e4e4e4'),
        'map_marker_image' => theme_option('map_marker_image'),
    ];
    $serikMapMarkerUrl = $serikThemeOptions['map_marker_image']
        ? RvMedia::getImageUrl($serikThemeOptions['map_marker_image'])
        : Theme::asset()->url('images/map-icon.png');
@endphp
        <style>
            :root {
                --primary-color: {{ $serikThemeOptions['primary_color'] }};
                --hover-color: {{ $serikThemeOptions['hover_color'] }};
                --top-header-background-color: {{ $serikThemeOptions['top_header_background_color'] }};
                --top-header-text-color: {{ $serikThemeOptions['top_header_text_color'] }};
                --main-header-background-color: {{ $serikThemeOptions['main_header_background_color'] }};
                --main-header-text-color: {{ $serikThemeOptions['main_header_text_color'] }};
                --main-header-border-color: {{ $serikThemeOptions['main_header_border_color'] }};
                --map-marker-icon-image: url({{ $serikMapMarkerUrl }});
            }

            .flag-tag.status-sold,
            .homeya-box .top .flag-tag.status-sold {
                background-color: var(--primary-color) !important;
                color: #fff !important;
            }

            .flag-tag.status-active {
                background-color: #198754 !important;
                color: #fff !important;
            }
            
            @media (max-width: 992px) {
                html, body {
                    overflow-x: hidden;
                    touch-action: pan-y; /* allow only vertical gestures */
                }

                /* Horizontal carousels must still receive swipe gestures */
                .swiper,
                .tf-sw-blog,
                .tf-sw-locations,
                .tf-sw-services,
                .tf-sw-agents,
                .tf-sw-categories,
                .tf-sw-testimonial,
                .city-swiper,
                .serik-blog-m {
                    touch-action: pan-x pan-y;
                }
            }

            .flat-section, .flat-section-v2, .flat-section-v3 {
                padding-top: 36px;
                padding-bottom: 36px;
            }

            .tf-btn {
                padding: 10px 16px;
            }

            .modal-content {
                border-radius: 12px;
            }

            /* Homepage: use fluid width with modest side padding instead of narrow container */
            #page-home .container {
                max-width: 100%;
                width: 100%;
                padding-left: clamp(16px, 2.5vw, 40px);
                padding-right: clamp(16px, 2.5vw, 40px);
            }

            /* Keep cashback calculator at original contained width */
            #page-home .cashback-calculator > .container {
                max-width: 1320px;
                margin-left: auto;
                margin-right: auto;
            }

            #page-home .flat-section,
            #page-home .flat-section-v2,
            #page-home .flat-section-v3,
            #page-home .flat-section-v4 {
                padding-top: clamp(1.75rem, 3.2vw, 3.25rem);
                padding-bottom: clamp(1.75rem, 3.2vw, 3.25rem);
            }
@php
    $isSerikHomepage = \App\Support\SerikHomepage::isHomepageRequest();
@endphp
        </style>
@if ($isSerikHomepage)
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
@else
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"></noscript>
@endif

        {{-- Favicons are emitted by Theme::header() (Google-compliant ≥48×48 root icons) --}}
        @stack('header')
        {!! Theme::header() !!}
{{-- Site-wide top bar + navbar + dropdown chrome (same as homepage) --}}
<link rel="stylesheet" href="{{ Theme::asset()->url('css/site-chrome.css') }}?v={{ get_cms_version() }}-sc6">
@if ($isSerikHomepage)
{{-- MUST load AFTER Theme::header() so redesign beats style.css --}}
<link rel="stylesheet" href="{{ Theme::asset()->url('css/homepage-premium.css') }}?v={{ get_cms_version() }}-hp36">
@endif
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "xu00ale4yi");
        </script>
        <!--Start of Tawk.to Script (bottom-right)-->
        <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        Tawk_API.customStyle = {
            visibility: {
                desktop: {
                    position: 'br',
                    xOffset: '20px',
                    yOffset: '20px'
                },
                mobile: {
                    position: 'br',
                    xOffset: '12px',
                    yOffset: '70px'
                }
            }
        };
        (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/6a6d0ff4f9ea531d4e9995a8/1jut0cl8v';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
        })();
        </script>
        <style>
            /* Keep Tawk.to bubble on the right */
            iframe[title="chat widget"],
            iframe[title="chat widget"][style],
            div.widget-visible {
                right: 20px !important;
                left: auto !important;
            }
            @media (max-width: 768px) {
                iframe[title="chat widget"],
                iframe[title="chat widget"][style],
                div.widget-visible {
                    right: 12px !important;
                    left: auto !important;
                    bottom: 70px !important;
                }
            }
        </style>
        <!--End of Tawk.to Script-->
    </head>

    <body {!! Theme::bodyAttributes() !!}>
        
      
        
        <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M57VSQWW"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
        
        {!! apply_filters(THEME_FRONT_BODY, null) !!}

        <div id="wrapper">
            <div class="clearfix">
                @yield('content')
            </div>
        </div>
     

        {!! Theme::footer() !!}

        {{-- Always load analytics: homepage uses deferred path inside the partial --}}
        @include(Theme::getThemeNamespace('partials.deferred-analytics'))
        @include(Theme::getThemeNamespace('partials.ads-contact-conversion'))

        @if(!request()->boolean('iframe'))
            @include(Theme::getThemeNamespace('partials.visitor-city-detect'))
        @endif
        {{-- LeadConnector chat temporarily disabled (Tawk.to is active) --}}
        @if(false)
   @if(!request()->has('iframe'))
   <div id="chat-widget-mobile"></div>
 @endif

 <script>
(function () {
    if (window.__serikChatLoaderBound) {
        return;
    }
    window.__serikChatLoaderBound = true;

    function moveWidget() {
        if (!window.matchMedia('(max-width: 768px)').matches) {
            return;
        }

        const host = [...document.querySelectorAll('*')].find((el) => el.shadowRoot);
        if (!host) {
            return;
        }

        const btn = host.shadowRoot.querySelector('#lc_text-widget--btn');
        if (!btn) {
            return;
        }

        btn.style.bottom = '80px';
        btn.style.position = 'fixed';
    }

    function loadChatWidget() {
        if (window.__serikChatLoaded) {
            return;
        }
        window.__serikChatLoaded = true;

        const mount = document.getElementById('chat-widget-mobile');
        if (!mount) {
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://widgets.leadconnectorhq.com/loader.js';
        script.setAttribute('data-resources-url', 'https://widgets.leadconnectorhq.com/chat-widget/loader.js');
        script.setAttribute('data-widget-id', '69b43d7e4d840e870b2cf29f');
        script.onload = function () {
            const observer = new MutationObserver(moveWidget);
            observer.observe(document.body, { childList: true, subtree: true });
            moveWidget();
            setTimeout(() => observer.disconnect(), 15000);
        };
        mount.appendChild(script);
    }

    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach((eventName) => {
        window.addEventListener(eventName, loadChatWidget, { once: true, passive: true });
    });
    window.setTimeout(loadChatWidget, {{ $isSerikHomepage ? '12000' : '6000' }});
})();
 </script>
        @endif
 @if(! $isSerikHomepage && !request()->boolean('iframe') && ! app()->environment('local'))
 <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
 @endif
   </body>
    
    
    
</html>
