<template id="property-map-content">
    <div class="map-listing-item">
        <div class="inner-box">
            <div class="image-box">
                <a data-href="__url__" href="#">
                    <img data-src="__image__" data-alt="__name__" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="">
                </a>
                __status__
            </div>
            <div class="content">
                <p class="location">
                    <x-core::icon name="ti ti-map-pin" />
                    __location__
                </p>
                <div class="title">
                    <a data-href="__url__" data-title="__name__" href="#" class="line-clamp-2">__name__</a>
                </div>
                <div class="price">__price__</div>
                <ul class="list-info">
                    <li class="map-number-of-bedroom"><x-core::icon name="ti ti-bed" />__bedroom__</li>
                    <li class="map-number-of-bathroom"><x-core::icon name="ti ti-bath" />__bathroom__</li>
                    <li class="map-square"><x-core::icon name="ti ti-ruler" />__square__</li>
                </ul>
            </div>
        </div>
    </div>
</template>
