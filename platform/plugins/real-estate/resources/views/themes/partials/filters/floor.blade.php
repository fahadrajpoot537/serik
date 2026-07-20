<div class="form-group">
    <label for="select-floor" class="control-label">{{ trans('plugins/real-estate::filters.floors') }}</label>
    <div class="select--arrow">
        <select name="floor" id="select-floor" class="form-control">
            <option value="">{{ trans('plugins/real-estate::filters.select') }}</option>
            @for($i = 1; $i < 5; $i++)
                <option value="{{ $i }}" @if (request()->input('floor') == $i) selected @endif>
                    {{ $i }} {{ $i == 1 ? trans('plugins/real-estate::filters.floor') : trans('plugins/real-estate::filters.floors') }}
                </option>
            @endfor
            <option value="5" @if (request()->input('floor') == 5) selected @endif>{{ trans('plugins/real-estate::filters.5_floors') }}</option>
        </select>
        <i class="fas fa-angle-down"></i>
    </div>
</div>
