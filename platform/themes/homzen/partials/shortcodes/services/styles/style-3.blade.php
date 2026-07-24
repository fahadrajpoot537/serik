<link rel="preload" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css"></noscript>
<style>
    .banner-video{
    position:relative;
}

.btn-video{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    width:80px;
    height:80px;
    background:rgba(0,0,0,.1);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:24px;
}
</style>

<section
    class="flat-section-v3 flat-service-v2"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        <div class="row wrap-service-v2">
            <div class="col-lg-6">
                <div class="box-left">
                     <div  style="font-weight: 700;color: #0255a1;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
                     <h2 class="section-title mt-4" style="font-weight: 700;text-align:left;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>

                    @if ($shortcode->description)
                        <p style="font-size: 16px; text-align: justify;">{!! BaseHelper::clean(nl2br($shortcode->description)) !!}</p>
                    @endif

                    <ul class="list-view">
                        @php
                            $checklist = array_filter(explode(',', ($shortcode->checklist ?: '')));
                        @endphp

                        @foreach ($checklist as $item)
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M8 15.9947C12.4183 15.9947 16 12.4154 16 8C16 3.58462 12.4183 0.00524902 8 0.00524902C3.58172 0.00524902 0 3.58462 0 8C0 12.4154 3.58172 15.9947 8 15.9947Z"
                                        fill="#198754"
                                    />
                                    <path
                                        d="M7.35849 12.2525L3.57599 9.30575L4.65149 7.9255L6.97424 9.735L10.8077 4.20325L12.2462 5.19975L7.35849 12.2525Z"
                                        fill="white"
                                    />
                                </svg>
                                {!! BaseHelper::clean($item) !!}
                            </li>
                        @endforeach
                    </ul>

                    @if ($shortcode->button_label && $shortcode->button_url)
                        <a
                            href="{{ $shortcode->button_url }}"
                            class="btn-view"
                        >
                            <span class="text">{{ $shortcode->button_label }}</span>
                            <x-core::icon
                                name="ti ti-arrow-right"
                                style="stroke-width: 2"
                            />
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="box-right">
                    @foreach ($services as $service)
                        <div class="box-service style-1 hover-btn-view" id="formMain" >
                            <div class="icon-box">
                                @if ($service['icon_image'])
                                    {{ RvMedia::image($service['icon_image'], $service['title'], attributes: ['class' => 'icon', 'data-bb-lazy' => 'false', 'width' => 48, 'height' => 48, 'decoding' => 'async', 'loading' => 'lazy', 'style' => sprintf('max-width: %spx !important; max-height: %spx !important;', $iconImageSize, $iconImageSize)]) }}
                                @elseif($service['icon'])
                                    <x-core::icon
                                        :name="$service['icon']"
                                        class="icon"
                                    />
                                @endif
                            </div>
                            <div class="content">
                                <h6 class="title">{!! BaseHelper::clean($service['title']) !!}</h6>
                                <p class="description">{!! BaseHelper::clean(nl2br($service['description'])) !!}</p>
                                @if ($service['button_url'])
                                    <a
                                        href="{{ $service['button_url'] }}"
                                        class="btn-view style-1"
                                    >
                                        <span class="text">{{ $service['button_label'] ?? __('Learn More') }}</span>
                                        <x-core::icon
                                            name="ti ti-arrow-right"
                                            style="stroke-width: 2"
                                        />
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                       
                        
                    @endforeach
                        <div class="box-service style-1 hover-btn-view" id="secondMain" style="min-height: auto;">
                            <div class="banner-video">
                                <img src="https://serik.ca/storage/artboard-3-edit.jpg"
                                alt="Welcome To The Serik Realty"
                                width="640"
                                height="360"
                                decoding="async"
                                loading="lazy"
                                class="w-100 h-100 object-fit-cover">
            
                                    <a
                                        href="https://serik.ca/storage/videoplayback.mp4"
                                        data-fancybox="gallery2"
                                        class="btn-video"
                                        data-type="video"
                                    >
                                        <span class="icon icon-play" style="font-size: 50px;"></span>
                                    </a>
                                
                            </div>
                        </div>
                </div>
                
            </div>
        </div>
    </div>
</section>


<script>
(function () {
    function isAboutUsPage() {
        return window.location.pathname === '/' || window.location.pathname === '';
    }

    if (isAboutUsPage()) {
        document.getElementById('formMain').style.display = 'none';
    } else {
        document.getElementById('secondMain').style.display = 'none';
    }

    let fancyboxReady = false;

    function loadFancybox(callback) {
        if (fancyboxReady && window.Fancybox) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js';
        script.onload = function () {
            fancyboxReady = true;
            callback();
        };
        document.body.appendChild(script);
    }

    document.querySelectorAll('[data-fancybox]').forEach((trigger) => {
        trigger.addEventListener('click', function (event) {
            if (window.Fancybox) {
                return;
            }

            event.preventDefault();
            const target = this;
            loadFancybox(function () {
                if (window.Fancybox) {
                    window.Fancybox.show([{ src: target.getAttribute('href'), type: target.dataset.type || 'video' }]);
                }
            });
        }, { once: true });
    });
})();
</script>





