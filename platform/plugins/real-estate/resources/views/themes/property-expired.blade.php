@php
    Theme::set('pageTitle', trans('plugins/real-estate::property.property_no_longer_available_name', ['name' => $property->name]));
@endphp

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card text-center p-5">
                    <div class="card-body">
                        <div class="mb-4">
                            <i class="mdi mdi-home-off-outline text-danger" style="font-size: 60px;"></i>
                        </div>
                        <h2 class="mb-3">{{ trans('plugins/real-estate::property.this_property_is_no_longer_available') }}</h2>
                        <h4 class="text-muted mb-4">{{ $property->name }}</h4>

                        <p class="text-muted mb-4">
                            {{ trans('plugins/real-estate::property.sorry_this_property_listing_has_expired_and_is_no_longer_ava') }}
                        </p>

                        <div class="alert alert-info">
                            <p class="mb-0">
                                {{ trans('plugins/real-estate::property.looking_for_other_properties_browse_our_current_listings_to') }}
                            </p>
                        </div>

                        <div class="mt-4">
                            <a href="{{ $propertiesUrl }}" class="btn btn-primary">
                                <i class="mdi mdi-home-search me-2"></i>
                                {{ trans('plugins/real-estate::property.browse_available_properties') }}
                            </a>
                        </div>
                    </div>
                </div>

                @if($property->project && $property->project->id)
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ trans('plugins/real-estate::property.related_project') }}</h5>
                        <div class="d-flex align-items-center">
                            @if($property->project->image)
                            <img src="{{ RvMedia::getImageUrl($property->project->image, 'thumb') }}"
                                 alt="{{ $property->project->name }}"
                                 class="me-3"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                            @endif
                            <div>
                                <h6 class="mb-1">{{ $property->project->name }}</h6>
                                <p class="text-muted mb-0">{{ Str::limit($property->project->description, 100) }}</p>
                                <a href="{{ $property->project->url }}" class="btn btn-link p-0 mt-2">
                                    {{ trans('plugins/real-estate::property.view_project_details') }} <i class="mdi mdi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>