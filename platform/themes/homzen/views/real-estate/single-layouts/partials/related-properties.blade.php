@php
    $relatedProperties = $relatedProperties ?? ($relatedPropertiesPayload['relatedProperties'] ?? collect());
    $sectionTitle = $sectionTitle ?? ($relatedPropertiesPayload['sectionTitle'] ?? __('Similar Properties'));
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
@endif
