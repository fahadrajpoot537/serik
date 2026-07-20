@php
    $categories = get_property_categories_for_select();
    $selectedCategoryId = request()->query('category_id');
    if (isset($category)) {
        $selectedCategoryId = $category->id;
    }
@endphp

<div class="form-group-3 form-style form-search-category" @if (theme_option('real_estate_enable_advanced_search', 'yes') != 'yes') style="border: none" @endif>
    <label for="category_id">{{ __('Category') }}</label>
    <div class="group-select">
        <select name="category_id" id="category_id" class="select_js">
            <option value="">{{ __('All') }}</option>
            @foreach($categories as $item)
                <option value="{{ $item->id }}"@selected($selectedCategoryId == $item->id)>{!! BaseHelper::clean($item->indent_text) !!} {!! BaseHelper::clean($item->name) !!}</option>
            @endforeach
        </select>
    </div>
</div>
