<h1>{{ trans('plugins/real-estate::general.properties') }}</h1>
{!! Theme::breadcrumb()->render() !!}

<div class="row">
    @foreach ($properties as $property)
        <div
            class="col-sm-6 col-lg-4 col-xl-3"
        >
            @include('plugins/real-estate::themes.partials.properties.item', ['property' => $property])
        </div>
    @endforeach
</div>

<br>

{!! $properties->withQueryString()->links() !!}
