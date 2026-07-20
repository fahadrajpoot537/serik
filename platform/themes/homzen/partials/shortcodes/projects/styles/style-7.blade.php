<section class="flat-section flat-project-v3 wow fadeInUpSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
    <div class="container">
        {!! Theme::partial('shortcode-heading', compact('shortcode')) !!}
        <div class="wrap-sw-project">
            <div class="swiper tf-sw-project">
                <div class="swiper-wrapper">
                    @foreach($projects as $project)
                        <div class="swiper-slide">
                            <div class="wrap-project-v2 style-1">
                                <div class="box-inner-left">
                                    {{ RvMedia::image($project->image, $project->name) }}
                                </div>
                                <div class="box-inner-right">
                                    <div class="content-project">
                                        <div class="box-tag">
                                            @if($project->is_featured)
                                                <span class="flag-tag success">{{ __('Featured') }}</span>
                                            @endif
                                        </div>
                                        <div class="box-name">
                                            <h5 class="title">
                                                <a href="{{ $project->url }}" class="link line-clamp-1">{{ $project->name }}</a>
                                            </h5>
                                            @if($project->short_address)
                                                <p class="location">
                                                    <span class="icon icon-mapPin"></span>
                                                    {{ $project->short_address }}
                                                </p>
                                            @endif
                                        </div>
                                        <ul class="list-info">
                                            @if($project->number_block)
                                                <li class="item">
                                                    <x-core::icon name="ti ti-packages" />
                                                    <span>{{ number_format($project->number_block) }}</span>
                                                </li>
                                            @endif
                                            @if($project->number_floor)
                                                <li class="item">
                                                    <x-core::icon name="ti ti-stairs" />
                                                    <span>{{ number_format($project->number_floor) }}</span>
                                                </li>
                                            @endif
                                            @if($project->number_flat)
                                                <li class="item">
                                                    <x-core::icon name="ti ti-building" />
                                                    <span>{{ number_format($project->number_flat) }}</span>
                                                </li>
                                            @endif
                                        </ul>
                                        @if($author = $project->author)
                                            <div class="box-avatar d-flex gap-12 align-items-center">
                                                <div class="avatar avt-60 round">
                                                    {{ RvMedia::image($author->avatar_url, $author->name, 'thumb') }}
                                                </div>
                                                <div class="info">
                                                    <p class="body-2 text-variant-1">{{ __('Developer') }}</p>
                                                    <div class="mt-4 h7 fw-7">{{ $author->name }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="pricing-project">
                                            @if (!setting('real_estate_hide_price', false))
                                                <div class="d-flex align-items-center">
                                                    <h5>{{ $project->formatted_price }}</h5>
                                                </div>
                                            @endif
                                            @if (RealEstateHelper::isEnabledWishlist())
                                                <div class="d-flex gap-12">
                                                    <button type="button" class="box-icon w-52"
                                                            data-type="project"
                                                            data-bb-toggle="add-to-wishlist"
                                                            data-id="{{ $project->getKey() }}"
                                                            data-add-message="{{ __('Added ":name" to wishlist successfully!', ['name' => $project->name]) }}"
                                                            data-remove-message="{{ __('Removed ":name" from wishlist successfully!', ['name' => $project->name]) }}"
                                                    >
                                                        <x-core::icon name="ti ti-heart" />
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="box-navigation">
                <div class="navigation swiper-nav-next nav-next-project">
                    <x-core::icon name="ti ti-chevron-left" />
                </div>
                <div class="navigation swiper-nav-prev nav-prev-project">
                    <x-core::icon name="ti ti-chevron-right" />
                </div>
            </div>
        </div>
    </div>
</section>
