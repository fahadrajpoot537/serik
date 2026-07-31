@php
    use App\Support\SerikMediaUrl;

    $model = $model ?? $property ?? null;
    $galleryImages = [];

    if ($model) {
        $galleryImages = SerikMediaUrl::mapListingGalleryUrls(
            $model->external_id ?? null,
            $model->image_val ?? null,
            is_array($model->images) ? $model->images : []
        );
    }
@endphp

@include(Theme::getThemeNamespace('partials.property-photo-lightbox'))

<style>
.hs-see-all-photos-btn {
    animation: hsSeeAllShadowPulse 1.7s ease-in-out infinite;
    will-change: box-shadow;
}
@keyframes hsSeeAllShadowPulse {
    0%, 100% {
        box-shadow:
            -12px 0 16px rgba(0, 0, 0, 0.28),
            12px 0 16px rgba(0, 0, 0, 0.28),
            0 4px 10px rgba(0, 0, 0, 0.18);
    }
    50% {
        box-shadow:
            -22px 0 28px rgba(0, 0, 0, 0.48),
            22px 0 28px rgba(0, 0, 0, 0.48),
            0 6px 14px rgba(0, 0, 0, 0.28);
    }
}
@media (prefers-reduced-motion: reduce) {
    .hs-see-all-photos-btn {
        animation: none;
        box-shadow:
            -12px 0 16px rgba(0, 0, 0, 0.28),
            12px 0 16px rgba(0, 0, 0, 0.28),
            0 4px 10px rgba(0, 0, 0, 0.18);
    }
}
</style>

@if (! empty($galleryImages))
    <section class="flat-gallery-single" id="propertyGalleryGrid" data-images='@json($galleryImages)'>
        @foreach($galleryImages as $image)
            @if($loop->first)
                <div class="item1 box-img">
                    <img src="{{ $image }}" alt="{{ $model->name }}" class="img-fluid w-100" loading="eager" onerror="this.src='{{ RvMedia::getDefaultImage() }}'">
                    <div class="box-btn">
                        <button type="button" class="tf-btn primary js-property-gallery-open-all hs-see-all-photos-btn">
                            {{ __('View All Photos (:count)', ['count' => count($galleryImages)]) }}
                        </button>
                    </div>
                </div>
            @else
                <a href="{{ $image }}"
                   class="item-{{ $loop->iteration }} box-img js-property-gallery-open"
                   data-gallery-index="{{ $loop->index }}"
                   @style(['display: none' => $loop->iteration > 5])>
                    <img src="{{ $image }}" alt="{{ $model->name }}" class="img-fluid w-100" loading="lazy" onerror="this.style.display='none'">
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
