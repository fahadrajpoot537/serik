@php
    Theme::set('pageTitle', __('Property No Longer Available: :name', ['name' => $property->name]));
    Theme::set('breadcrumbEnabled', 'no');
@endphp

<section class="flat-section pt-0" style="margin-top: 60px;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="wd-find-select style-2 shadow-st mt-5 d-block">
                    <div class="text-center py-5">
                        <h3 class="mb-3">{{ __('Property No Longer Available') }}</h3>
                        <p class="text-muted fs-5 mb-2">{{ $property->name }}</p>

                        <p class="text-muted mb-4">
                            {{ __('This listing has expired and is no longer on the market.') }}
                        </p>

                        <a href="{{ $propertiesUrl }}" class="tf-btn primary w-auto px-4 d-inline-block">
                            <span>{{ __('Browse Available Properties') }}</span>
                        </a>
                    </div>
                </div>

                @if($property->project && $property->project->id)
                <div class="bg-white rounded-4 shadow-sm p-4 mt-4">
                    <h5 class="mb-3">{{ __('Related Project') }}</h5>
                    <div class="d-flex align-items-center">
                        @if($property->project->image)
                        <div class="flex-shrink-0">
                            <img src="{{ RvMedia::getImageUrl($property->project->image, 'thumb') }}"
                                 alt="{{ $property->project->name }}"
                                 class="rounded-3 me-3"
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                        @endif
                        <div class="flex-grow-1">
                            <h6 class="mb-2">{{ $property->project->name }}</h6>
                            <p class="text-muted mb-2">{{ Str::limit($property->project->description, 150) }}</p>
                            <a href="{{ $property->project->url }}" class="tf-btn style-1 w-auto">
                                <span>{{ __('View Project Details') }}</span>
                                <i class="icon icon-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @php
                    $similarProperties = \Botble\RealEstate\Models\Property::query()
                        ->active()
                        ->whereHas('categories', function($query) use ($property) {
                            $query->whereIn('category_id', $property->categories->pluck('id'));
                        })
                        ->where('id', '!=', $property->id)
                        ->limit(2)
                        ->get();
                @endphp

                @if($similarProperties->count() > 0)
                <div class="mt-5">
                    <h4 class="mb-4 text-center">{{ __('Similar Properties You Might Like') }}</h4>
                    <div class="row">
                        @foreach($similarProperties as $property)
                        <div class="col-md-6 col-lg-6 mb-4">
                            @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
