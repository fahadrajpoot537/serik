<div class="form-group">
    <label for="keyword" class="control-label">{{ trans('plugins/real-estate::filters.keyword') }}</label>
    <div class="input-has-icon">
        <input type="text" id="keyword" class="form-control" name="k" value="{{ BaseHelper::stringify(request()->input('k')) }}"
            placeholder="{{ trans('plugins/real-estate::filters.enter_keyword') }}">
        <i class="far fa-search"></i>
    </div>
</div>
