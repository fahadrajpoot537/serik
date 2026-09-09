<style>
.banner-wrapper,
.ads-banner-item {
    position: relative;
    display: block;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    margin: 0 0 1rem;
    border-radius: 12px;
    line-height: 0;
}

.banner-image {
    position: relative;
    display: block;
    width: 100%;
}

.banner-image img,
.ads-banner-item img {
    width: 100%;
    max-width: 100%;
    height: auto;
    display: block;
}

.banner-overlay {
    position: absolute;
    left: 50%;
    bottom: 12px;
    top: auto;
    transform: translateX(-50%);
    width: auto !important;
    max-width: calc(100% - 16px);
    margin: 0 !important;
    padding: 0;
    text-align: center;
    z-index: 2;
    line-height: 1.2;
}

.btn-whatsapp {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    background: #25D366;
    color: #fff !important;
    padding: 0.55rem 0.95rem;
    border-radius: 999px;
    text-decoration: none !important;
    font-weight: 700;
    font-size: 0.8125rem;
    white-space: nowrap;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
    max-width: 100%;
}

.btn-whatsapp:hover {
    background: #1ebe5d;
    color: #fff !important;
}

/* Narrow blog aside: keep CTA inside the ad card, never over next headings */
.serik-blog-detail__aside .banner-wrapper,
.serik-blog-detail__aside .ads-banner-item,
.ads-sidebar .banner-wrapper,
.ads-sidebar .ads-banner-item {
    margin-bottom: 1.25rem;
}

.serik-blog-detail__aside .banner-overlay,
.ads-sidebar .banner-overlay {
    bottom: 10px;
}

.serik-blog-detail__aside .btn-whatsapp,
.ads-sidebar .btn-whatsapp {
    font-size: 0.75rem;
    padding: 0.45rem 0.75rem;
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

    @php
        $adUrl = trim((string) ($item->url ?? ''));
        $openNewTab = (bool) ($item->open_in_new_tab ?? false);
        $wrapperAttrs = $attributes;
        $existingClass = trim((string) ($wrapperAttrs['class'] ?? ''));
        $wrapperAttrs['class'] = trim($existingClass . ' ads-banner-item banner-wrapper');
    @endphp

    <div {!! Html::attributes($wrapperAttrs) !!}>
        <div class="banner-image">
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

                {{ RvMedia::image($item->image_url, $item->name, attributes: ['style' => 'max-width: 100%; height: auto; display: block;']) }}
            </picture>

            <div class="banner-overlay">
                @if ($adUrl !== '')
                    <a
                        href="{{ $adUrl }}"
                        @if ($openNewTab) target="_blank" rel="noopener noreferrer" @endif
                        title="{{ __('WhatsApp Inquiry') }}"
                        class="btn-whatsapp"
                    >
                        {{ __('WhatsApp Inquiry') }}
                    </a>
                @else
                    <span class="btn-whatsapp" role="text">{{ __('WhatsApp Inquiry') }}</span>
                @endif
            </div>
        </div>
    </div>
@endforeach
