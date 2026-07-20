<div class="form-group">
    <label for="select-blocks" class="control-label">{{ trans('plugins/real-estate::filters.number_of_blocks') }}</label>
    <div class="select--arrow">
        <select name="blocks" id="select-blocks" class="form-control">
            <option value="">{{ trans('plugins/real-estate::filters.select') }}</option>
            @for($i = 1; $i < 5; $i++)
                <option value="{{ $i }}" @if (request()->input('blocks') == $i) selected @endif>{{ $i }} {{ $i == 1 ? trans('plugins/real-estate::filters.block') : trans('plugins/real-estate::filters.blocks') }}</option>
            @endfor
            <option value="5" @if (request()->input('blocks') == 5) selected @endif>{{ trans('plugins/real-estate::filters.5_blocks') }}</option>
        </select>
        <i class="fas fa-angle-down"></i>
    </div>
</div>