<section class="flat-project-v3">
    <div class="container-full">
        <div class="wrap-project-v2">
            @if($shortcode->background_image)
                <div class="box-inner-left img-animation wow">
                    {{ RvMedia::image($shortcode->background_image, $shortcode->title) }}
                </div>
            @endif
            <div class="box-inner-right">
                <div class="swiper tf-sw-project">
                    <div class="swiper-wrapper">
                        @foreach($projects as $project)
                            <div class="swiper-slide">
                                <div class="content-project">
                                    {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode, 'centered' => false]) !!}
                                    <ul class="box-tag">
                                        @if($project->is_featured)
                                            <span class="flag-tag success">{{ __('Featured') }}</span>
                                        @endif
                                    </ul>
                                    <div class="box-name">
                                        <h5 class="title">
                                            <a href="{{ $project->url }}" class="link">{!! BaseHelper::clean($project->name) !!}</a>
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
                        @endforeach
                    </div>
                </div>
                <div class="sw-pagination sw-pagination-project"></div>
            </div>
        </div>
    </div>
</section>
