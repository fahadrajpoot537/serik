<div class="form-group">
    <label for="select-bedroom" class="control-label">{{ trans('plugins/real-estate::filters.bedrooms') }}</label>
    <div class="select--arrow">
        <select name="bedroom" id="select-bedroom" class="form-control">
            <option value="">{{ trans('plugins/real-estate::filters.select') }}</option>
            @for($i = 1; $i < 5; $i++)
                <option value="{{ $i }}" @if (request()->input('bedroom') == $i) selected @endif>
                    {{ $i }} {{ $i == 1 ? trans('plugins/real-estate::filters.room') : trans('plugins/real-estate::filters.rooms') }}
                </option>
            @endfor
            <option value="5" @if (request()->input('bedroom') == 5) selected @endif>{{ trans('plugins/real-estate::filters.5_rooms') }}</option>
        </select>
        <i class="fas fa-angle-down"></i>
    </div>
</div>
