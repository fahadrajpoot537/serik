@php
    $bedroom = request()->input('bedroom');
    $minPrice = request()->input('min_price');
    $maxPrice = request()->input('max_price');
    $bathroom = request()->input('bathroom');
    $minSquare = request()->input('min_square');
    $keyword = request()->input('k');
    $homeTypes = (array) request()->input('home_types', []);
    $propertyCount = $propertyCount ?? null;
    $sortBy = request()->query('sort_by');

    $priceLabel = __('Any Price');
    if ($minPrice || $maxPrice) {
        $priceLabel = ($minPrice ? '$' . number_format((int) $minPrice) : '$0')
            . ' – '
            . ($maxPrice ? '$' . number_format((int) $maxPrice) : __('Max'));
    }

    $bedLabel = $bedroom ? ((int) $bedroom) . '+ ' . __('Bed') : __('Any Bed');

    $typeParts = [];
    if (in_array('house', $homeTypes, true)) {
        $typeParts[] = __('House');
    }
    if (in_array('condo', $homeTypes, true)) {
        $typeParts[] = __('Condo');
    }
    if (in_array('townhouse', $homeTypes, true)) {
        $typeParts[] = __('Townhouse');
    }
    $typeLabel = $typeParts !== [] ? implode(', ', $typeParts) : __('Home Type');

    $sortLabels = ['' => __('Newest')] + RealEstateHelper::getSortByList();
    $sortLabel = $sortLabels[$sortBy] ?? __('Newest');

    $activeChips = [];
    if ($minPrice || $maxPrice) {
        $activeChips[] = ['key' => 'price', 'label' => $priceLabel];
    }
    if ($bedroom) {
        $activeChips[] = ['key' => 'bedroom', 'label' => ((int) $bedroom) . '+ ' . __('Bed')];
    }
    foreach ($typeParts as $part) {
        $activeChips[] = ['key' => 'type', 'label' => $part];
    }
    if ($bathroom) {
        $activeChips[] = ['key' => 'bathroom', 'label' => ((int) $bathroom) . '+ ' . __('Bath')];
    }
    if ($minSquare) {
        $activeChips[] = ['key' => 'min_square', 'label' => number_format((int) $minSquare) . '+ sq ft'];
    }
    if ($keyword) {
        $activeChips[] = ['key' => 'k', 'label' => '"' . \Illuminate\Support\Str::limit($keyword, 24) . '"'];
    }
@endphp

<input type="hidden" name="per_page" value="{{ request()->integer('per_page', 12) }}">
<input type="hidden" name="type" value="sale">

<div class="serik-listing-toolbar sticky-top bg-white border-bottom">
    <div class="container py-2 py-md-3">
        <ul class="nav-filters-menu serik-nav-filters list-unstyled d-flex flex-wrap align-items-center gap-2 mb-0">
            <li class="dropdown">
                <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    {{ __('For Sale') }}
                </button>
                <div class="dropdown-menu serik-filter-menu p-2">
                    <button type="button" class="serik-filter-option active w-100 text-start">{{ __('For Sale') }}</button>
                </div>
            </li>

            <li class="dropdown">
                <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="serikPriceChip">
                    {{ $priceLabel }}
                </button>
                <div class="dropdown-menu serik-filter-menu p-3" style="min-width: 280px;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1" for="price-min">{{ __('Min') }}</label>
                            <input type="number" name="min_price" id="price-min" class="form-control form-control-sm serik-price-input" placeholder="{{ __('Min') }}" value="{{ $minPrice }}" min="0" step="10000">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1" for="price-max">{{ __('Max') }}</label>
                            <input type="number" name="max_price" id="price-max" class="form-control form-control-sm serik-price-input" placeholder="{{ __('Max') }}" value="{{ $maxPrice }}" min="0" step="10000">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 serik-price-presets mb-2">
                        @foreach([
                            ['', '', __('Any')],
                            ['0', '500000', 'Under $500K'],
                            ['500000', '1000000', '$500K–$1M'],
                            ['1000000', '2000000', '$1M–$2M'],
                            ['2000000', '', '$2M+'],
                        ] as [$min, $max, $lbl])
                            <button type="button" class="serik-filter-pill" data-min="{{ $min }}" data-max="{{ $max }}">{{ $lbl }}</button>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-primary w-100 serik-price-apply">{{ __('Apply Price') }}</button>
                </div>
            </li>

            <li class="dropdown">
                <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown" id="serikBedChip">{{ $bedLabel }}</button>
                <div class="dropdown-menu serik-filter-menu p-2">
                    <input type="hidden" name="bedroom" id="serikBedInput" value="{{ $bedroom }}">
                    <div class="d-flex flex-wrap gap-1 serik-bed-presets">
                        @foreach(['' => __('Any'), '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+', '5' => '5+'] as $val => $lbl)
                            <button type="button" @class(['serik-filter-pill serik-bed-preset', 'active' => (string) $bedroom === (string) $val]) data-bed="{{ $val }}">{{ $lbl }}</button>
                        @endforeach
                    </div>
                </div>
            </li>

            <li class="dropdown">
                <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown" id="serikTypeChip">{{ $typeLabel }}</button>
                <div class="dropdown-menu serik-filter-menu p-3" style="min-width: 200px;">
                    @foreach(['house' => __('House'), 'condo' => __('Condo'), 'townhouse' => __('Townhouse')] as $val => $lbl)
                        <div class="form-check mb-2">
                            <input class="form-check-input serik-home-type serik-instant-filter" type="checkbox" name="home_types[]" value="{{ $val }}" id="ptype-{{ $val }}" @checked(in_array($val, $homeTypes, true))>
                            <label class="form-check-label" for="ptype-{{ $val }}">{{ $lbl }}</label>
                        </div>
                    @endforeach
                </div>
            </li>

            <li class="dropdown">
                <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown">{{ __('More') }}</button>
                <div class="dropdown-menu serik-filter-menu p-3" style="min-width: 300px;">
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="min_baths">{{ __('Bathrooms') }}</label>
                        <select name="bathroom" id="min_baths" class="form-select form-select-sm serik-instant-filter">
                            <option value="">{{ __('Any') }}</option>
                            @foreach(range(1, 5) as $i)
                                <option value="{{ $i }}" @selected((string) $bathroom === (string) $i)>{{ $i }}+</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="min_sqft">{{ __('Square Feet') }}</label>
                        <select name="min_square" id="min_sqft" class="form-select form-select-sm serik-instant-filter">
                            <option value="">{{ __('Any') }}</option>
                            @foreach([500, 750, 1000, 1250, 1500, 1750, 2000, 2500, 3000, 3500] as $sq)
                                <option value="{{ $sq }}" @selected((string) $minSquare === (string) $sq)>{{ number_format($sq) }}+ sq ft</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1" for="attribute-terms">{{ __('Keywords') }}</label>
                        <input type="text" name="k" id="attribute-terms" class="form-control form-control-sm serik-instant-filter-delay" placeholder="{{ __('City, address, MLS…') }}" value="{{ $keyword }}">
                    </div>
                </div>
            </li>

            @if (request()->query())
                <li>
                    <a href="{{ $actionUrl ?? url('/properties') }}" class="serik-filter-btn serik-filter-btn--ghost reset-filter-btn">{{ __('Clear') }}</a>
                </li>
            @endif

            <li class="ms-md-auto d-flex flex-wrap align-items-center gap-2">
                <div class="dropdown">
                    <button type="button" class="serik-filter-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="text-muted small">{{ __('Sort') }}:</span> {{ $sortLabel }}
                    </button>
                    <div class="dropdown-menu serik-filter-menu dropdown-menu-end p-2" style="min-width: 180px;">
                        <select name="sort_by" class="form-select form-select-sm serik-instant-filter serik-sort-select border-0">
                            <option value="" @selected($sortBy === null || $sortBy === '')>{{ __('Newest') }}</option>
                            @foreach (RealEstateHelper::getSortByList() as $key => $sort)
                                <option value="{{ $key }}" @selected($sortBy === $key)>{{ $sort }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <a href="{{ url('/map') }}" class="serik-filter-btn serik-filter-btn--map">
                    <x-core::icon name="ti ti-map-pin" class="me-1" />{{ __('Map Search') }}
                </a>
            </li>
        </ul>

        <div class="serik-active-filters mt-2 @if ($activeChips === []) d-none @endif" id="serikActiveFilters">
            @foreach ($activeChips as $chip)
                <span class="serik-active-chip" data-chip="{{ $chip['key'] }}">{{ $chip['label'] }}</span>
            @endforeach
        </div>

        <p class="serik-listing-count mb-0 mt-2 pt-2 border-top">
            <strong id="serikListingCount">{{ $propertyCount !== null ? number_format($propertyCount) : '—' }}</strong>
            <span id="serikListingCountLabel">{{ $activeChips !== [] ? __('homes match your filters') : __('homes for sale in Ontario') }}</span>
            <span class="serik-filter-loading ms-2 d-none" id="serikFilterLoading" aria-hidden="true">{{ __('Updating…') }}</span>
        </p>
    </div>
</div>

<a href="{{ url('/map') }}" class="serik-mobile-map-fab d-md-none" aria-label="{{ __('Map Search') }}">
    <x-core::icon name="ti ti-map-pin" /> {{ __('Map') }}
</a>

@once
<style>
.serik-properties-page #sectionhead,
.serik-properties-page .flat-title-page { display: none !important; }
.serik-properties-page.listing-no-map .flat-title-page { padding: 0 !important; }
.serik-properties-page .serik-properties-filters-section { padding-top: 0; background: #fff; }
.serik-properties-page .flat-section-v5,
.serik-properties-page .flat-recommended-v2 {
    margin-top: 0;
    padding-top: 28px;
    padding-bottom: 48px;
}
@media (max-width: 991px) {
    .serik-properties-page .flat-section-v5,
    .serik-properties-page .flat-recommended-v2 {
        margin-top: 0;
        padding-top: 28px;
    }
}
.serik-properties-page [data-bb-toggle="data-listing"] {
    margin-top: 16px;
    padding-top: 8px;
    scroll-margin-top: calc(var(--serik-top-header-height, 0px) + var(--serik-main-header-height, 60px) + 88px);
}
.serik-properties-page .serik-prop-grid { margin-top: 8px; }
.serik-listing-toolbar.is-stuck { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08); }
.serik-properties-page [data-bb-toggle="list-map"],
.serik-properties-page .flat-map .top-map,
.serik-properties-page [data-bb-toggle="data-listing"] .leaflet-container { display: none !important; }
.serik-mobile-map-fab {
    position: fixed;
    right: 14px;
    bottom: calc(78px + env(safe-area-inset-bottom, 0px));
    z-index: 9990;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--primary-color, #0255a1);
    color: #fff !important;
    border-radius: 999px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(2, 85, 161, 0.35);
}
.serik-mobile-map-fab:hover { color: #fff !important; opacity: 0.95; }
.serik-listing-toolbar {
    top: calc(var(--serik-top-header-height, 0px) + var(--serik-main-header-height, 60px));
    z-index: 9998;
}
.serik-nav-filters { row-gap: 8px; }
.serik-filter-btn {
    border: 1px solid #d1d5db; border-radius: 8px; background: #fff;
    padding: 8px 14px; font-size: 14px; font-weight: 600; color: #111827;
    line-height: 1.2; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap;
}
.serik-filter-btn:hover, .serik-filter-btn:focus { border-color: var(--primary-color, #0255a1); color: var(--primary-color, #0255a1); }
.serik-filter-btn--ghost { font-weight: 500; color: #6b7280; }
.serik-filter-btn--map { background: var(--primary-color, #0255a1); border-color: var(--primary-color, #0255a1); color: #fff !important; }
.serik-filter-btn--map:hover { opacity: 0.92; color: #fff !important; }
.serik-filter-menu { border-radius: 10px; box-shadow: 0 10px 32px rgba(15,23,42,.12); border: 1px solid #e5e7eb; }
.serik-filter-pill, .serik-filter-option {
    border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 8px;
    padding: 6px 12px; font-size: 13px; font-weight: 500; cursor: pointer;
}
.serik-filter-pill:hover, .serik-filter-pill.active, .serik-filter-option.active {
    background: #e8f2fc; border-color: var(--primary-color, #0255a1); color: var(--primary-color, #0255a1);
}
.serik-listing-count { font-size: 15px; color: #374151; }
.serik-filter-loading { font-size: 13px; color: var(--primary-color, #0255a1); font-weight: 500; }
.serik-active-filters { display: flex; flex-wrap: wrap; gap: 6px; }
.serik-active-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #eef4fc; color: #1e4a7a; border: 1px solid #c7daf0;
    border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 600;
}
.serik-filter-pill.active { background: #e8f2fc; border-color: var(--primary-color, #0255a1); color: var(--primary-color, #0255a1); }
.serik-properties-page .box-title-listing .btn-filter-mobile,
.serik-properties-page .box-title-listing .nav-tab-filter { display: none !important; }
.serik-properties-page [data-bb-toggle="data-listing"].is-loading { opacity: 0.55; pointer-events: none; transition: opacity 0.15s; }
.serik-properties-page .loading-spinner { position: absolute; top: 40%; left: 50%; transform: translate(-50%,-50%); z-index: 5; }
@media (max-width: 767px) {
    .serik-nav-filters { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 4px; }
    .serik-filter-btn { font-size: 13px; padding: 7px 12px; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.serik-properties-page .filter-form');
    if (!form) return;

    let debounceTimer = null;
    let filterXhr = null;

    const formatPriceLabel = () => {
        const min = form.querySelector('input[name="min_price"]');
        const max = form.querySelector('input[name="max_price"]');
        const minV = min?.value ? '$' + Number(min.value).toLocaleString() : '';
        const maxV = max?.value ? '$' + Number(max.value).toLocaleString() : '';
        if (!minV && !maxV) return 'Any Price';
        return (minV || '$0') + ' – ' + (maxV || 'Max');
    };

    const updateActiveChips = () => {
        const wrap = document.getElementById('serikActiveFilters');
        const countLabel = document.getElementById('serikListingCountLabel');
        if (!wrap) return;

        const chips = [];
        const priceLabel = formatPriceLabel();
        if (priceLabel !== 'Any Price') chips.push(priceLabel);

        const bed = form.querySelector('#serikBedInput')?.value;
        if (bed) chips.push(bed + '+ Bed');

        form.querySelectorAll('.serik-home-type:checked').forEach((el) => {
            const label = form.querySelector('label[for="' + el.id + '"]');
            if (label) chips.push(label.textContent.trim());
        });

        const bath = form.querySelector('select[name="bathroom"]')?.value;
        if (bath) chips.push(bath + '+ Bath');

        const sqft = form.querySelector('select[name="min_square"]')?.value;
        if (sqft) chips.push(Number(sqft).toLocaleString() + '+ sq ft');

        const keyword = form.querySelector('input[name="k"]')?.value?.trim();
        if (keyword) chips.push('"' + keyword + '"');

        wrap.innerHTML = chips.map((text) => '<span class="serik-active-chip">' + text + '</span>').join('');
        wrap.classList.toggle('d-none', chips.length === 0);
        if (countLabel) {
            countLabel.textContent = chips.length ? 'homes match your filters' : 'homes for sale in Ontario';
        }

        const typeChip = form.querySelector('#serikTypeChip');
        if (typeChip) {
            const types = [];
            form.querySelectorAll('.serik-home-type:checked').forEach((el) => {
                const label = form.querySelector('label[for="' + el.id + '"]');
                if (label) types.push(label.textContent.trim());
            });
            typeChip.textContent = types.length ? types.join(', ') : 'Home Type';
        }
    };

    const submitFilters = () => {
        const pageInput = form.querySelector('input[name="page"]');
        if (pageInput) pageInput.value = '1';
        updateActiveChips();
        form.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
            const toggle = menu.previousElementSibling;
            if (toggle && typeof bootstrap !== 'undefined') {
                bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            }
        });
        if (typeof jQuery !== 'undefined') {
            jQuery(form).trigger('submit');
        } else {
            form.requestSubmit();
        }
    };

    const debouncedSubmit = (delay = 250) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(submitFilters, delay);
    };

    window.serikPropertiesFilter = {
        setLoading(isLoading) {
            document.getElementById('serikFilterLoading')?.classList.toggle('d-none', !isLoading);
        },
        setTotal(total) {
            const el = document.getElementById('serikListingCount');
            if (el && total != null) {
                el.textContent = Number(total).toLocaleString();
            }
        },
        abortPending() {
            if (filterXhr && filterXhr.readyState !== 4) {
                filterXhr.abort();
            }
        },
        setXhr(xhr) {
            filterXhr = xhr;
        },
    };

    form.querySelectorAll('.serik-bed-preset').forEach((btn) => {
        btn.addEventListener('click', () => {
            form.querySelectorAll('.serik-bed-preset').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const input = form.querySelector('#serikBedInput');
            if (input) input.value = btn.dataset.bed || '';
            const chip = form.querySelector('#serikBedChip');
            if (chip) chip.textContent = btn.dataset.bed ? btn.dataset.bed + '+ Bed' : 'Any Bed';
            submitFilters();
        });
    });

    form.querySelectorAll('.serik-price-presets .serik-filter-pill').forEach((btn) => {
        btn.addEventListener('click', () => {
            const min = form.querySelector('input[name="min_price"]');
            const max = form.querySelector('input[name="max_price"]');
            if (min) min.value = btn.dataset.min || '';
            if (max) max.value = btn.dataset.max || '';
            form.querySelectorAll('.serik-price-presets .serik-filter-pill').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const chip = form.querySelector('#serikPriceChip');
            if (chip) chip.textContent = formatPriceLabel();
            submitFilters();
        });
    });

    form.querySelector('.serik-price-apply')?.addEventListener('click', () => {
        const chip = form.querySelector('#serikPriceChip');
        if (chip) chip.textContent = formatPriceLabel();
        submitFilters();
    });

    form.querySelectorAll('.serik-instant-filter').forEach((el) => {
        if (el.classList.contains('serik-home-type')) {
            el.addEventListener('change', () => debouncedSubmit(300));
            return;
        }
        el.addEventListener('change', submitFilters);
    });

    form.querySelectorAll('.serik-instant-filter-delay').forEach((el) => {
        el.addEventListener('input', () => debouncedSubmit(450));
    });

    form.querySelector('#serikTypeChip')?.addEventListener('click', updateActiveChips);
    updateActiveChips();
});
</script>
@endonce
