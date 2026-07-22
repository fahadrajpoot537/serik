<!DOCTYPE html>
<html {!! Theme::htmlAttributes() !!}>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport"/>
<meta name="google-site-verification" content="DnbR_f8W1AL0-M1AWElchvX8AZQQae51aLL_FNhq7rE" /><!--Google SIte Verification-->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "vo52awk0jq");
</script><!-- Microsoft clearity code -->


<!-- Meta Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1789817231630101');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" alt=""
    src="https://www.facebook.com/tr?id=1789817231630101&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->

<!-- Google tag (gtag.js) -->
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G0KFZYXM3D"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-G0KFZYXM3D');
</script> 
        <style>
            :root {
                --primary-color: {{ theme_option('primary_color', '#db1d23') }};
                --hover-color: {{ theme_option('hover_color', '#cd380f') }};
                --top-header-background-color: {{ theme_option('top_header_background_color', '#f7f7f7') }};
                --top-header-text-color: {{ theme_option('top_header_text_color', '#161e2d') }};
                --main-header-background-color: {{ theme_option('main_header_background_color', '#ffffff') }};
                --main-header-text-color: {{ theme_option('main_header_text_color', '#161e2d') }};
                --main-header-border-color: {{ theme_option('main_header_border_color', '#e4e4e4') }};
                --map-marker-icon-image: url({{ theme_option('map_marker_image') ? RvMedia::getImageUrl(theme_option('map_marker_image')) : Theme::asset()->url('images/map-icon.png') }});
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
        </style>
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-M57VSQWW');</script>
<!-- End Google Tag Manager -->

        {!! Theme::header() !!}
        <script src="{{ Theme::asset()->url('js/visitor-location.js') }}?v={{ get_cms_version() }}"></script>
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

        @if(!request()->boolean('iframe'))
            @include(Theme::getThemeNamespace('partials.visitor-city-detect'))
        @endif
   @if(!request()->has('iframe')) 
   <div id="chat-widget-mobile">
        <script 
  src="https://widgets.leadconnectorhq.com/loader.js"  
  data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js" 
 data-widget-id="69b43d7e4d840e870b2cf29f"   > 
 </script>
   </div>
  
 @endif



 <script>
 
function moveWidget() {
    if (!window.matchMedia("(max-width: 768px)").matches) return;

    const host = [...document.querySelectorAll("*")].find(el => el.shadowRoot);
    if (!host) return;

    const btn = host.shadowRoot.querySelector("#lc_text-widget--btn");

    if (btn) {
        btn.style.bottom = "80px";
        btn.style.position = "fixed";
    }
}

setInterval(moveWidget, 1000);

 /*  fetch("https://ipinfo.io/json")
  .then(res => res.json())
  .then(data => {
    console.log("IP:", data.ip);
    console.log("City:", data.city);
    console.log("Country:", data.country);
    const [lat, lon] = data.loc.split(",");

    console.log("Latitude:", lat);
    console.log("Longitude:", lon);
  });
  */
  
  
  
  
  
 </script>
 <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
   </body>
    
    
    
</html>
