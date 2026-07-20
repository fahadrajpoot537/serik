<div class="property-item homeya-box list-style-1 position-relative" @if ($property->latitude && $property->longitude) data-lat="{{ $property->latitude }}" data-lng="{{ $property->longitude }}" @endif>
@if ($property->isSoldHistory() && !auth('account')->check())
    {!! Theme::partial('sold-property-login-gate') !!}
@endif
<div class="@if($property->isSoldHistory() && !auth('account')->check()) blurred-content @endif">
    <a href="{{ (!$property->isSoldHistory() || auth('account')->check()) ? $property->url : '#modalRegister' }}" 
       @if($property->isSoldHistory() && !auth('account')->check()) data-bs-toggle="modal" @endif
       class="images-group">
        <div class="images-style">
            @include(Theme::getThemeNamespace('views.real-estate.partials.property-image'), [
                'property' => $property,
                'size' => 'medium-square',
            ])
        </div>
        <div class="top">
            <ul class="d-flex gap-4 flex-column">
                @if($property->is_featured)
                    <span class="flag-tag success">{{ __('Featured') }}</span>
                @endif
                {!! BaseHelper::clean($property->status_html) !!}
            </ul>
            @if (RealEstateHelper::isEnabledWishlist())
                <div class="d-flex gap-4">
                    <button type="button" class="box-icon w-32"
                            data-type="property"
                            data-bb-toggle="add-to-wishlist"
                            data-id="{{ $property->getKey() }}"
                            data-add-message="{{ __('Added ":name" to wishlist successfully!', ['name' => $property->name]) }}"
                            data-remove-message="{{ __('Removed ":name" from wishlist successfully!', ['name' => $property->name]) }}"
                    >
                        <x-core::icon name="ti ti-heart" />
                    </button>
                </div>
            @endif
        </div>
        @if($property->category)
            <div class="bottom">
                <span class="flag-tag style-2">{{ $property->category->name }}</span>
            </div>
        @endif
    </a>
    <div class="content">
        <div class="archive-top">
            <div class="h7 text-capitalize fw-7">
                <a href="{{ (!$property->isSoldHistory() || auth('account')->check()) ? $property->url : '#modalRegister' }}" 
                   @if($property->isSoldHistory() && !auth('account')->check()) data-bs-toggle="modal" @endif
                   class="link line-clamp-1" title="{{ $property->display_name }}">{!! BaseHelper::clean($property->display_name) !!}</a>
            </div>
            @if($property->short_address)
                <div class="desc">
                    <i class="icon icon-mapPin"></i>
                    <p class="line-clamp-1">{{ $property->short_address }}</p>
                </div>
            @endif
            <ul class="meta-list">
                @if($property->number_bedroom)
                    <li class="item">
                        <i class="icon icon-bed"></i>
                        <span>{{ number_format($property->number_bedroom) }}</span>
                    </li>
                @endif
                @if($property->number_bathroom)
                    <li class="item">
                        <i class="icon icon-bathtub"></i>
                        <span>{{ number_format($property->number_bathroom) }}</span>
                    </li>
                @endif
                @if($property->square)
                    <li class="item">
                        <i class="icon icon-ruler"></i>
                        <span>{{ $property->square_text }}</span>
                    </li>
                @endif
            </ul>
        </div>
        <div class="d-flex justify-content-between align-items-center archive-bottom">
            @if (! \Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile() && ($author = $property->author) && $author->exists)
                <div class="d-flex gap-8 align-items-center">
                    <div class="avatar avt-40 round">
                        {{ RvMedia::image($author->avatar_url, $author->name, 'thumb') }}
                    </div>
                    <span>{{ $author->name }} {!! $author->badge !!}</span>
                </div>
            @endif
            @if (!setting('real_estate_hide_price', false))
                <div class="d-flex align-items-center">
                    @if(!$property->isSoldHistory() || auth('account')->check())
                        <div class="h7 fw-7">{{ $property->price_format }}</div>
                    @else
                        <div class="h7 fw-7 blur-text">******</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
</div>
