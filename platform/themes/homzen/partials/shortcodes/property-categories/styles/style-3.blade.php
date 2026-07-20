<section class="flat-section flat-categories hs-cat-section" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        {!! Theme::partial('shortcode-heading', compact('shortcode')) !!}

        <div class="wrap-categories wow fadeInUpSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
            @php
                $allowedTypes = [
                    'Detached',
                    'Semi-Detached',
                    'Att/Row/Townhouse',
                    'Condo Townhouse',
                    'Condo Apartment',
                    'Duplex'
                ];

                // Fetch from DB and order by custom sequence
                $categories = \Illuminate\Support\Facades\DB::table('re_properties')
                    ->select('PropertySubType', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->whereIn('PropertySubType', $allowedTypes)
                    ->groupBy('PropertySubType')
                    ->orderByRaw("FIELD(PropertySubType, 'Detached','Semi-Detached','Att/Row/Townhouse','Condo Townhouse','Condo Apartment','Duplex')")
                    ->get();

                $icons = [
                    'Detached' => '🏠',
                    'Semi-Detached' => '🏘️',
                    'Att/Row/Townhouse' => '🏘️',
                    'Condo Townhouse' => '🏢',
                    'Condo Apartment' => '🏬',
                    'Duplex' => '🏡',
                ];
            @endphp

            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 g-md-4">
                @foreach ($categories as $category)
                    @php
                        $label = $category->PropertySubType === 'Att/Row/Townhouse'
                            ? 'Freehold Townhouse'
                            : $category->PropertySubType;
                    @endphp
                    <div class="col">
                        <a href="{{ url('map') . '?transaction=For%20Sale&subtypes=' . urlencode($category->PropertySubType) }}"
                           class="hs-cat-card"
                           title="{{ $label }}">
                            <span class="hs-cat-icon">{{ $icons[$category->PropertySubType] ?? '🏠' }}</span>
                            <span class="hs-cat-title">{{ $label }}</span>
                            <span class="hs-cat-count">
                                {{ number_format($category->total) }} {{ $category->total == 1 ? 'Property' : 'Properties' }}
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<style>
    .hs-cat-section .wrap-categories {
        margin-top: 8px;
    }
    .hs-cat-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        text-align: center;
        gap: 10px;
        height: 100%;
        padding: 22px 14px 18px;
        background: #fff;
        border: 1px solid #eef1f6;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .hs-cat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(2, 85, 161, 0.14);
        border-color: #cfe0f5;
    }
    .hs-cat-icon {
        width: 56px;
        height: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        line-height: 1;
        border-radius: 50%;
        background: #eef4ff;
        transition: background .2s ease;
    }
    .hs-cat-card:hover .hs-cat-icon {
        background: #dbe9ff;
    }
    .hs-cat-title {
        font-size: 15px;
        font-weight: 700;
        color: #161e2d;
        line-height: 1.3;
    }
    .hs-cat-count {
        font-size: 12.5px;
        color: #6b7280;
        margin-top: -2px;
    }
    @media (max-width: 575px) {
        .hs-cat-card {
            padding: 18px 10px 14px;
            border-radius: 14px;
        }
        .hs-cat-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }
        .hs-cat-title {
            font-size: 13.5px;
        }
    }
</style>
