<style>
    /* Layout wrapper */
    .page-wrapper {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }

    /* Scroll container */
    .scroll-container {
        position: relative;
        width: 50%;
        height: 100%;
        overflow: hidden;
    }

    /* Fade overlays */
    .scroll-container::before,
    .scroll-container::after {
        content: "";
        position: absolute;
        left: 0;
        width: 100%;
        height: 90px;
        z-index: 2;
        pointer-events: none;
    }

    .scroll-container::before {
        top: 0;
        background: linear-gradient(to bottom, #fff, transparent);
    }

    .scroll-container::after {
        bottom: 0;
        background: linear-gradient(to top, #fff, transparent);
    }

    /* Scroll track */
    .scroll-track {
        display: flex;
        flex-direction: column;
        
    }

    .scroll-track img {
        width: 100%;
        border-radius: 14px;
        display: block;
        object-fit: cover;
    }

    /* Animations */
    .scroll-up {
        animation: scrollUp 25s linear infinite;
    }

    .scroll-down {
        animation: scrollDown 25s linear infinite;
    }

    @keyframes scrollUp {
        from {
            transform: translateY(0);
        }
        to {
            transform: translateY(-50%);
        }
    }

    @keyframes scrollDown {
        from {
            transform: translateY(-50%);
        }
        to {
            transform: translateY(0);
        }
    }

    /* Pause on hover */
    .scroll-container:hover .scroll-track {
        animation-play-state: paused;
    }
</style>


<section class="flat-section pt-0 flat-banner" id="secondMain1" style="padding-bottom:0px;">
    
    
    <div class="container-fluid">
        <div class="wrap-banner bg-surface">
            <div class="box-left">
                <div class="box-title">
                     @if($shortcode->title)
                        <h2 class="section-title mt-4" style="color: #000">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                    @endif
                    @if($shortcode->subtitle)
                        <div class="text-subtitle text-primary">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
                    @endif
                   
                </div>
                @if($shortcode->button_label)
                    <a href="{{ $shortcode->button_url }}" class="tf-btn primary size-1">
                        {{ $shortcode->button_label }}
                    </a>
                @endif
            </div>
            @if($shortcode->image)
                <div class="box-right">
                    {{ RvMedia::image($shortcode->image, $shortcode->title) }}
                </div>
            @endif
        </div>
    </div>

</section>



<section class="flat-section pt-0 flat-banner" style="padding:0px;" id="formMain1">
    <div class="container-fluid">
        <div class="wrap-banner bg-surface" style="justify-content: normal;">
             @if($shortcode->image)
                <div class="box-right">
                   <div class="page-wrapper">

                        <!-- LEFT COLUMN (UP) -->
                        <div class="scroll-container">
                            <div class="scroll-track scroll-up">
                                <img src="https://vke.899.mytemp.website/storage/images-1.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-2.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-3.jfif">
                    
                                <!-- Duplicate for seamless loop -->
                                <img src="https://vke.899.mytemp.website/storage/images-1.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-2.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-3.jfif">
                            </div>
                        </div>
                    
                        <!-- RIGHT COLUMN (DOWN) -->
                        <div class="scroll-container">
                            <div class="scroll-track scroll-down">
                                <img src="https://vke.899.mytemp.website/storage/images.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-4.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-5.jfif">
                    
                                <!-- Duplicate for seamless loop -->
                                <img src="https://vke.899.mytemp.website/storage/images.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-4.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-5.jfif">
                            </div>
                        </div>
                         <div class="scroll-container">
                            <div class="scroll-track scroll-up">
                                <img src="https://vke.899.mytemp.website/storage/images-1.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-2.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-3.jfif">
                    
                                <!-- Duplicate for seamless loop -->
                                <img src="https://vke.899.mytemp.website/storage/images-1.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-2.jfif">
                                <img src="https://vke.899.mytemp.website/storage/images-3.jfif">
                            </div>
                        </div>
                    
                    
                    </div>

                    
                   <!-- {{ RvMedia::image($shortcode->image, $shortcode->title) }}-->
                </div>
            @endif
            <div class="box-left">
                <div class="box-title">
                   
                    <div>
                    <img style="height: 50px;" src=" https://vke.899.mytemp.website/storage/backgroundbordershadow.png">
                </div><br>
                    @if($shortcode->subtitle)
                        <div class="text-subtitle text-primary" style="font-size:30px;color:linear-gradient(#000000, #2C638B);">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
                    @endif
                    @if($shortcode->title)
                        <h2 class="section-title mt-4" style="font-size:40px;font-weight:900; color:black;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                    @endif
                </div>
                <div>
                    <img src="https://vke.899.mytemp.website/storage/container.png">
                </div>
                <br>
                @if($shortcode->button_label)
                    <a href="{{ $shortcode->button_url }}" class="tf-btn primary size-1">
                        {{ $shortcode->button_label }}
                    </a>
                @endif
            </div>
           
        </div>
    </div>
</section>

<script>
function isAboutUsPage() {
    return window.location.pathname === '/about-us' 
        || window.location.pathname === '/evaluation' || window.location.pathname === '/appointment-scheduler';
}



if (isAboutUsPage()) {
    document.getElementById("formMain1").style.display = "none";
} else {
    document.getElementById("secondMain1").style.display = "none";
}

</script>
