<div class="form-group" style="position: relative;">
    <input type="hidden" name="city_id">
    <label for="location" class="control-label">{{ trans('plugins/real-estate::filters.location') }}</label>
    <div class="location-input" data-url="{{ route('public.ajax.cities') }}" style="position: relative;">
        <div class="input-has-icon">
            <input class="select-city-state form-control" id="location" name="location"
                value="{{ BaseHelper::stringify(request()->input('location')) }}" placeholder="{{ trans('plugins/real-estate::filters.city_state') }}"
                autocomplete="off">
            <i class="far fa-location"></i>
        </div>
        <div class="spinner-icon">
            <i class="fas fa-spin fa-spinner"></i>
        </div>
        <div class="suggestion">

        </div>
    </div>
</div>
