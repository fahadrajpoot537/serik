@php
$order = ['Gary Sodhi', 'Sadaqat Sheikh'];

$accounts = $accounts->sortBy(function ($account) use ($order) {
    $index = array_search($account->name, $order);
    return $index === false ? 999 : $index;
});
@endphp
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
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-{{ $shortcode->items_per_row ?: 4 }}">
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