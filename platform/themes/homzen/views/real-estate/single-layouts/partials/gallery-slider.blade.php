@php
    use App\Support\SerikMediaUrl;
    use Theme\homzen\Supports\TrebPropertyHelper;

    $images = SerikMediaUrl::mapListingGalleryUrls(
        $property->external_id ?? null,
        $property->image_val ?? null,
        is_array($property->images) ? $property->images : []
    );

    $statusLabel = $property->isSoldHistory()
        ? TrebPropertyHelper::soldStatusLabel($property->MlsStatus)
        : ($property->TransactionType ?? __('For Sale'));

    $galleryAlt = collect([
        $property->name,
        $property->external_id ?? $property->unique_id ?? null,
        ($property->type && method_exists($property->type, 'label')) ? $property->type->label() : ($property->PropertySubType ?? null),
    ])->filter()->unique()->implode(' - ') ?: __('Property listing');
@endphp

@include(Theme::getThemeNamespace('partials.property-photo-lightbox'))

<style>
.property-gallery {
    padding: 20px;
}

.gallery-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    height: 500px;
}

.gallery-main {
    height: 500px;
}

.gallery-main a,
.gallery-row a {
    display: block;
    height: 100%;
    position: relative;
    cursor: pointer;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 16px;
}

.gallery-side {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 15px;
    height: 500px;
    position: relative;
}

.gallery-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    height: 100%;
}

.gallery-row a {
    height: 240px;
}

.gallery-row img {
    width: 100%;
    height: 240px;
    object-fit: cover;
    border-radius: 16px;
}

.badge-sale {
    position: absolute;
    bottom: 15px;
    left: 15px;
    background: color-mix(in srgb, var(--primary-color) 15%, white);
    color: var(--primary-color);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    pointer-events: none;
}

.badge-sale.status-sold-wrap .flag-tag.status-sold {
    position: static;
    display: inline-block;
}

.see-all-btn {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: var(--primary-color);
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 25px;
    font-size: 14px;
    cursor: pointer;
    z-index: 2;
}

@media (max-width: 992px) {
    .property-gallery {
        padding: 10px;
    }

    .gallery-grid {
        grid-template-columns: 1fr;
        height: auto !important;
        gap: 10px;
    }

    .gallery-main {
        height: auto;
    }

    .gallery-main img {
        height: 220px;
        width: 100%;
        object-fit: cover;
        border-radius: 12px;
    }

    .gallery-side {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
        height: auto;
        gap: 10px;
    }

    .gallery-row {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .gallery-row a {
        height: auto;
    }

    .gallery-row img {
        height: 120px;
        width: 100%;
        object-fit: cover;
        border-radius: 10px;
    }

    .see-all-btn {
        position: relative;
        width: 100%;
        margin-top: 10px;
        bottom: auto;
        right: auto;
        border-radius: 12px;
        font-size: 13px;
        padding: 10px;
    }

    .badge-sale {
        font-size: 12px;
        padding: 4px 10px;
        bottom: 10px;
        left: 10px;
    }
}
</style>

@if (!empty($images))
<section class="property-gallery" id="propertyGallery" data-listing-key="{{ $property->external_id ?? '' }}" data-images='@json($images)'>

    <div class="gallery-grid">

        <div class="gallery-main">
            <a href="{{ $images[0] }}" data-gallery-index="0" class="js-property-gallery-open" style="height:100%">
                <img src="{{ $images[0] }}" alt="{{ $galleryAlt }}" onerror="this.src='{{ RvMedia::getDefaultImage() }}'">
                <span class="badge-sale @if($property->isSoldHistory()) status-sold-wrap @endif">
                    @if($property->isSoldHistory())
                        {!! TrebPropertyHelper::soldStatusBadgeHtml($property->MlsStatus) !!}
                    @else
                        {{ $statusLabel }}
                    @endif
                </span>
            </a>
        </div>

        <div class="gallery-side">
            <div class="gallery-row">
                @foreach(array_slice($images, 1, 2) as $offset => $image)
                    <a href="{{ $image }}" data-gallery-index="{{ $offset + 1 }}" class="js-property-gallery-open">
                        <img src="{{ $image }}" alt="{{ $galleryAlt }}" loading="eager" onerror="this.style.display='none'">
                    </a>
                @endforeach
            </div>

            <div class="gallery-row">
                @foreach(array_slice($images, 3, 2) as $offset => $image)
                    <a href="{{ $image }}" data-gallery-index="{{ $offset + 3 }}" class="js-property-gallery-open">
                        <img src="{{ $image }}" alt="{{ $galleryAlt }}" loading="lazy" onerror="this.style.display='none'">
                    </a>
                @endforeach
            </div>

            @if (count($images) > 1)
            <button type="button" class="see-all-btn js-property-gallery-open-all">
                See all {{ count($images) }} photos
            </button>
            @endif
        </div>

    </div>

</section>
@endif

<script>
(function () {
    function getGalleryImages() {
        const gallery = document.getElementById('propertyGallery');
        if (!gallery) {
            return [];
        }

        try {
            const parsed = JSON.parse(gallery.dataset.images || '[]');
            if (Array.isArray(parsed) && parsed.length) {
                return parsed.filter(Boolean);
            }
        } catch (e) {}

        return [...gallery.querySelectorAll('[data-gallery-index]')]
            .map((el) => el.getAttribute('href'))
            .filter(Boolean);
    }

    function openPropertyGallery(startIndex) {
        const images = getGalleryImages();
        if (!images.length || !window.SerikPhotoLightbox) {
            return;
        }

        window.SerikPhotoLightbox.open(images, startIndex || 0);
    }

    window.openGallery = function () {
        openPropertyGallery(0);
    };

    document.addEventListener('click', function (e) {
        const gallery = document.getElementById('propertyGallery');
        if (!gallery) {
            return;
        }

        if (e.target.closest('.js-property-gallery-open-all')) {
            e.preventDefault();
            openPropertyGallery(0);
            return;
        }

        const link = e.target.closest('.js-property-gallery-open');
        if (link && gallery.contains(link)) {
            e.preventDefault();
            openPropertyGallery(Number(link.dataset.galleryIndex || 0));
        }
    });

    const gallery = document.getElementById('propertyGallery');
    if (!gallery) {
        return;
    }

    const listingKey = gallery.dataset.listingKey || '';
    const currentCount = getGalleryImages().length;
    if (!listingKey || currentCount > 1) {
        return;
    }

    fetch('/api/v1/getPropertyImages/' + encodeURIComponent(listingKey))
        .then((res) => (res.ok ? res.json() : null))
        .then((data) => {
            const images = data && Array.isArray(data.images) ? data.images.filter(Boolean) : [];
            if (images.length <= 1) {
                return;
            }

            gallery.dataset.images = JSON.stringify(images);

            const statusHtml = gallery.querySelector('.badge-sale');
            const statusBadge = statusHtml ? statusHtml.outerHTML : '';
            const propertyName = @json($galleryAlt);
            const defaultImg = @json(RvMedia::getDefaultImage());

            const sideRows = (start, count) => images.slice(start, start + count).map((src, i) =>
                `<a href="${src}" data-gallery-index="${start + i}" class="js-property-gallery-open"><img src="${src}" alt="${propertyName}" loading="lazy" onerror="this.style.display='none'"></a>`
            ).join('');

            gallery.innerHTML = `
                <div class="gallery-grid">
                    <div class="gallery-main">
                        <a href="${images[0]}" data-gallery-index="0" class="js-property-gallery-open" style="height:100%">
                            <img src="${images[0]}" alt="${propertyName}" onerror="this.src='${defaultImg}'">
                            ${statusBadge}
                        </a>
                    </div>
                    <div class="gallery-side">
                        <div class="gallery-row">${sideRows(1, 2)}</div>
                        <div class="gallery-row">${sideRows(3, 2)}</div>
                        <button type="button" class="see-all-btn js-property-gallery-open-all">See all ${images.length} photos</button>
                    </div>
                </div>
            `;
        })
        .catch(() => {});
})();
</script>
