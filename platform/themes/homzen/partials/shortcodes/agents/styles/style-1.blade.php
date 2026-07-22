@php
$order = ['Gary Sodhi', 'Sadaqat Sheikh'];

$accounts = $accounts->sortBy(function ($account) use ($order) {
    $index = array_search($account->name, $order);
    return $index === false ? 999 : $index;
});
@endphp
<style>
    #about-agent.flat-agents .box-agent {
        gap: 12px;
    }

    #about-agent.flat-agents .box-agent .box-img {
        overflow: hidden;
        border-radius: 12px;
        background: #f8fafc;
        line-height: 0;
    }

    #about-agent.flat-agents .box-agent .box-img img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
        object-position: center top;
    }

    #about-agent.flat-agents .box-agent .content h6 {
        font-size: 15px;
        line-height: 1.35;
        margin-bottom: 4px;
    }

    #about-agent.flat-agents .box-agent .list-info {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    #about-agent.flat-agents .box-agent .list-info li {
        margin-bottom: 0 !important;
        font-size: 13px;
    }

    @media (min-width: 992px) {
        #about-agent.flat-agents > .container > .row {
            --bs-gutter-x: 1.25rem;
        }
    }

    @media (max-width: 767px) {
        #about-agent.flat-agents .box-agent .box-img img {
            max-height: none;
        }
    }
</style>
<section id="about-agent" class="flat-section flat-agents" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
         @if($shortcode->subtitle)
            <div  style="text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
        @endif

         @if($shortcode->title)
            <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
           
                <a href="https://serik.ca/about-us#about-agent" class="btn-view button-prop" style="float:right; margin-top:-45px;">
                    <span class="text" style="font-weight: 500;">View All</span>
                    <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
                </a>
            
        @endif
         <br>
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 row-cols-lg-4">
            @foreach ($accounts as $account)
                <div class="box col">
                    <div class="box-agent hover-img wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
                        <div class="box-img img-style mb-2">
                            {{ RvMedia::image($account->avatar_url, $account->name) }}
                            {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}
                        </div>
                        <div class="content">
                            <div class="info">
                                @if (\Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile())
                                    <h6>{{ $account->name }} {!! $account->badge !!}</h6>
                                @else
                                    <a href="{{ $account->url }}">
                                        <h6 class="link">{{ $account->name }} {!! $account->badge !!}</h6>
                                    </a>
                                @endif
                                {!! Theme::partial('shortcodes.agents.partials.info', compact('account')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>


<script>
function hideButton() {
    if (window.location.pathname.includes("about-us")) {
        const btns = document.querySelectorAll(".btn-view.button-prop");

        btns.forEach(btn => {
            btn.style.setProperty("display", "none", "important");
        });
    }
}

// Run multiple times to beat dynamic rendering
hideButton();
window.addEventListener("load", hideButton);
setTimeout(hideButton, 500);
setTimeout(hideButton, 1500);
</script>