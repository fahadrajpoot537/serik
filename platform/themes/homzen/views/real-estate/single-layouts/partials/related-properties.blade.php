@php
    $relatedProperties = $relatedProperties ?? ($relatedPropertiesPayload['relatedProperties'] ?? collect());
    $sectionTitle = $sectionTitle ?? ($relatedPropertiesPayload['sectionTitle'] ?? __('Similar Properties'));
    $deferPropertyId = (int) ($relatedPropertiesPayload['deferPropertyId'] ?? 0);
@endphp

@if ($relatedProperties->isNotEmpty())
    <section class="flat-section pt-0 flat-latest-property" id="similarProperties">
        <div class="container">
            <div class="box-title">
                <div class="text-subtitle text-primary">{{ __('Similar Properties') }}</div>
                <h2 class="section-title mt-4">
                    {{ $sectionTitle ?? __('Similar Properties') }}
                </h2>
            </div>
            <div class="swiper tf-latest-property" data-preview-lg="3" data-preview-md="2" data-preview-sm="2" data-space="30" data-loop="true">
                <div class="swiper-wrapper">
                    @foreach($relatedProperties as $property)
                        <div class="swiper-slide">
                            @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'), ['property' => $property, 'class' => 'style-2'])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@elseif ($deferPropertyId > 0)
    <section
        class="flat-section pt-0 flat-latest-property is-loading"
        id="similarProperties"
        data-related-defer="{{ $deferPropertyId }}"
        aria-busy="true"
    >
        <div class="container">
            <div class="box-title">
                <div class="text-subtitle text-primary">{{ __('Similar Properties') }}</div>
                <h2 class="section-title mt-4">{{ __('Similar Properties') }}</h2>
            </div>
            <div class="hs-related-defer-status" style="padding:12px 0 24px;color:#64748b;font-size:14px;">
                {{ __('Loading similar properties…') }}
            </div>
            <div class="swiper tf-latest-property" data-preview-lg="3" data-preview-md="2" data-preview-sm="2" data-space="30" data-loop="true" hidden>
                <div class="swiper-wrapper"></div>
            </div>
        </div>
    </section>
    <script>
    (function () {
        var section = document.getElementById('similarProperties');
        if (!section || !section.getAttribute('data-related-defer') || window.__serikRelatedDeferBound) {
            return;
        }
        window.__serikRelatedDeferBound = true;
        var propertyId = section.getAttribute('data-related-defer');
        fetch('/api/v1/related-properties/' + encodeURIComponent(propertyId), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (!data || !data.success || !data.html) {
                section.remove();
                return;
            }
            var titleEl = section.querySelector('.section-title');
            if (titleEl && data.sectionTitle) {
                titleEl.textContent = data.sectionTitle;
            }
            var status = section.querySelector('.hs-related-defer-status');
            if (status) {
                status.remove();
            }
            var swiperRoot = section.querySelector('.swiper.tf-latest-property');
            var wrapper = swiperRoot && swiperRoot.querySelector('.swiper-wrapper');
            if (!swiperRoot || !wrapper) {
                section.remove();
                return;
            }
            wrapper.innerHTML = data.html;
            swiperRoot.hidden = false;
            section.classList.remove('is-loading');
            section.removeAttribute('aria-busy');
            section.removeAttribute('data-related-defer');
            if (window.Swiper && !swiperRoot.swiper) {
                try {
                    new Swiper(swiperRoot, {
                        slidesPerView: 1.15,
                        spaceBetween: 16,
                        loop: data.count > 2,
                        breakpoints: {
                            576: { slidesPerView: 2, spaceBetween: 20 },
                            992: { slidesPerView: 3, spaceBetween: 30 }
                        }
                    });
                } catch (e) {}
            }
        }).catch(function () {
            section.remove();
        });
    })();
    </script>
@endif
