<div class="form-group">
    <label for="select-category" class="control-label">{{ trans('plugins/real-estate::filters.category') }}</label>
    <div class="select--arrow">
        <select name="category_id" id="select-category" class="form-control">
            <option value="">{{ trans('plugins/real-estate::filters.select') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @if (request()->input('category_id') == $category->id) selected @endif>{!! BaseHelper::clean($category->indent_text) !!} {{ $category->name }}</option>
            @endforeach
        </select>
        <i class="fas fa-angle-down"></i>
    </div>
</div>
