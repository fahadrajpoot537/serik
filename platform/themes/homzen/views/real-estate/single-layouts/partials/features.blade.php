@php
    $model = $model ?? $property ?? null;
@endphp

@if ($model->features->isNotEmpty())
    <div @class(['single-property-feature', $class ?? null]) id="features">
        <div class="h7 title fw-7">{{ __('Amenities and features') }}</div>
        <div class="box-feature">
            <ul>
                @foreach ($model->features as $feature)
                    <li class="feature-item">
                        @if ($feature->icon)
                            {!! BaseHelper::renderIcon($feature->icon) !!}
                        @else
                            {!! BaseHelper::renderIcon('ti ti-square-dot') !!}
                        @endif
                        {{ $feature->name }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
