@php
    $model = $model ?? $property ?? null;
    $galleryImages = [];

    if ($model && $model->images) {
        foreach ((array) $model->images as $image) {
            $url = RvMedia::getImageUrl($image);
            if ($url) {
                $galleryImages[] = $url;
            }
        }
    }
@endphp

@include(Theme::getThemeNamespace('partials.property-photo-lightbox'))

@if (! empty($galleryImages))
    <section class="flat-gallery-single" id="propertyGalleryGrid" data-images='@json($galleryImages)'>
        @foreach($galleryImages as $image)
            @if($loop->first)
                <div class="item1 box-img">
                    {{ RvMedia::image($image, $model->name) }}
                    <div class="box-btn">
                        <button type="button" class="tf-btn primary js-property-gallery-open-all">
                            {{ __('View All Photos (:count)', ['count' => count($galleryImages)]) }}
                        </button>
                    </div>
                </div>
            @else
                <a href="{{ $image }}"
                   class="item-{{ $loop->iteration }} box-img js-property-gallery-open"
                   data-gallery-index="{{ $loop->index }}"
                   @style(['display: none' => $loop->iteration > 5])>
                    {{ RvMedia::image($image, $model->name, lazy: false) }}
                </a>
            @endif
        @endforeach
    </section>

    <script>
    (function () {
        function getImages() {
            const root = document.getElementById('propertyGalleryGrid');
            if (!root) return [];
            try {
                const parsed = JSON.parse(root.dataset.images || '[]');
                if (Array.isArray(parsed) && parsed.length) return parsed.filter(Boolean);
            } catch (e) {}
            return [...root.querySelectorAll('.js-property-gallery-open')].map((el) => el.getAttribute('href')).filter(Boolean);
        }

        function openAt(index) {
            const images = getImages();
            if (images.length && window.SerikPhotoLightbox) {
                window.SerikPhotoLightbox.open(images, index || 0);
            }
        }

        document.addEventListener('click', function (e) {
            const root = document.getElementById('propertyGalleryGrid');
            if (!root) return;

            if (e.target.closest('.js-property-gallery-open-all')) {
                e.preventDefault();
                openAt(0);
                return;
            }

            const link = e.target.closest('.js-property-gallery-open');
            if (link && root.contains(link)) {
                e.preventDefault();
                openAt(Number(link.dataset.galleryIndex || 0));
            }

            const firstImage = e.target.closest('.item1.box-img img');
            if (firstImage && root.contains(firstImage)) {
                e.preventDefault();
                openAt(0);
            }
        });
    })();
    </script>
@endif
