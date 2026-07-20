<div class="form-group">
    <label for="select-bathroom" class="control-label">{{ trans('plugins/real-estate::filters.bathrooms') }}</label>
    <div class="select--arrow">
        <select name="bathroom" id="select-bathroom" class="form-control">
            <option value="">{{ trans('plugins/real-estate::filters.select') }}</option>
            @for($i = 1; $i < 5; $i++)
                <option value="{{ $i }}" @if (request()->input('bathroom') == $i) selected @endif>
                    {{ $i }} {{ $i == 1 ? trans('plugins/real-estate::filters.room') : trans('plugins/real-estate::filters.rooms') }}
                </option>
            @endfor
            <option value="5" @if (request()->input('bathroom') == 5) selected @endif>{{ trans('plugins/real-estate::filters.5_rooms') }}</option>
        </select>
        <i class="fas fa-angle-down"></i>
    </div>
</div>