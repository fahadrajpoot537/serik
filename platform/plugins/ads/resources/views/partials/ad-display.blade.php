<style>
    .banner-wrapper {
    position: relative;
}

.banner-image {
    position: relative;
}

.banner-image img {
    width: 100%;
    display: block;
}

.banner-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    
    text-align: center;
    color: #fff;
    max-width: 700px;
    padding: 10px;
}


.btn-whatsapp {
    display: inline-block;
    background: #25D366;
    color: #fff;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
}

.btn-whatsapp:hover {
    background: #1ebe5d;
}
</style>

@foreach($data as $item)
    @if ($item->ads_type === 'google_adsense' && $item->google_adsense_slot_id)
        <div {!! Html::attributes($attributes) !!}>
            @include('plugins/ads::partials.google-adsense.unit-ads-slot', ['slotId' => $item->google_adsense_slot_id])
        </div>
        @continue
    @endif

    @continue(! $item->image)

    <div {!! Html::attributes($attributes) !!}>
        @if ($item->url)
            <a href="" @if ($item->open_in_new_tab) target="_blank" @endif title="{{ trans('plugins/ads::ads.banner') }}">
        @endif
                <picture>
                    <source
                        srcset="{{ $item->image_url }}"
                        media="(min-width: 1200px)"
                    />
                    <source
                        srcset="{{ $item->tablet_image_url }}"
                        media="(min-width: 768px)"
                    />
                    <source
                        srcset="{{ $item->mobile_image_url }}"
                        media="(max-width: 767px)"
                    />

                    {{ RvMedia::image($item->image_url, $item->name, attributes: ['style' => 'max-width: 100%']) }}
                </picture>
                
            <div class="banner-overlay" style="width: 280px;margin-top: -210px;">
              

               <a href="" @if ($item->open_in_new_tab) target="_blank" @endif title="{{ trans('plugins/ads::ads.banner') }}" class="btn-whatsapp"> 
                    WhatsApp Inquiry
                </a>
            </div>
                
        @if($item->url)
            </a>
        @endif
    </div>
@endforeach



