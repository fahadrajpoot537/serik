{!! '<' . '?' . 'xml version="1.0" encoding="UTF-8"?>' . "\n" !!}
@if (null != $style)
    {!! '<' . '?' . 'xml-stylesheet href="' . asset($style) . '" type="text/xsl"?>' . "\n" !!}
@endif


@php
if (request()->path() === 'agents.xml') {

    $items = collect($items)
        ->filter(function ($item) {

            return rtrim($item['loc'], '/')
                !== rtrim(url('/'), '/');

        })
        ->values();
}
@endphp



@php

use Illuminate\Support\Facades\DB;

if (request()->is('properties-*.xml')) {

    $subtypeMap = [

        // Houses
        'Detached' => 'detached-houses',
        'Detached Condo' => 'detached-houses',
        'Semi-Detached' => 'semi-detached-houses',
        'Link' => 'link-houses',

        // Townhouses
        'Att/Row/Townhouse' => 'townhouses',
        'Condo Townhouse' => 'townhouses',

        // Condos
        'Condo Apartment' => 'condos',
        'Co-op Apartment' => 'condos',
        'Co-Ownership Apartment' => 'condos',
        'Leasehold Condo' => 'condos',
        'Common Element Condo' => 'condos',

        // Multi-family
        'Duplex' => 'duplex',
        'Fourplex' => 'fourplex',
        'Multiplex' => 'multiplex',

        // fallback
        'Other' => 'houses',
    ];

    // REMOVE these types from sitemap
    $excludedTypes = [
        'Land',
        'Farm',
        'Commercial Retail',
        'Industrial',
        'Investment',
        'Office',
        'MobileTrailer',
        'Modular Home',
        'Lower Level',
        'Locker',
    ];

    // collect MLS ids
    $mlsIds = collect($items)
        ->map(function ($item) {

            if (
                preg_match(
                    '/-([A-Z]\d+)$/i',
                    basename($item['loc']),
                    $match
                )
            ) {
                return strtoupper($match[1]);
            }

            return null;

        })
        ->filter()
        ->unique()
        ->values();

    // ONE query
    $propertyTypes = DB::table('re_properties')
        ->whereIn('external_id', $mlsIds)
        ->pluck(
            'PropertySubType',
            'external_id'
        );

    $items = collect($items)

        // remove unwanted property types
        ->filter(function ($item) use ($propertyTypes, $excludedTypes) {

            if (! str_contains($item['loc'], '/properties/')) {
                return true;
            }

            preg_match(
                '/-([A-Z]\d+)$/i',
                basename($item['loc']),
                $match
            );

            $mlsId =
                strtoupper($match[1] ?? '');

            $propertySubtype =
                $propertyTypes[$mlsId]
                ?? '';

            return ! in_array(
                $propertySubtype,
                $excludedTypes
            );

        })

        // rewrite remaining urls
        ->map(function ($item) use ($propertyTypes, $subtypeMap) {

            if (! str_contains($item['loc'], '/properties/')) {
                return $item;
            }

            $slug = basename($item['loc']);

            preg_match(
                '/-([A-Z]\d+)$/i',
                $slug,
                $match
            );

            $mlsId =
                strtoupper($match[1] ?? '');

            $propertySubtype =
                $propertyTypes[$mlsId]
                ?? '';

            $subtype =
                $subtypeMap[$propertySubtype]
                ?? 'houses';

            // allowed cities only
$cities = [
    'brampton',
    'mississauga',
    'vaughan',
    'milton',
    'oakville',
    'niagarafalls',
    'toronto',
    'kitchener',
    'waterloo',
    'cambridge',
    'hamilton',
    'ottawa',
    'london',
    'markham',
    'windsor',
    'richmondhill',
    'burlington',
    'oshawa',
    'barrie',
    'guelph',
    'kingston',
    'whitby',
    'ajax',
    'peterborough',
    'sarnia',
    'thunderbay',
    'sudbury',
    'northbay',
    'orillia',
    'brantford',
    'stcatharines',
    'welland',
    'pickering',
    'clarington',
];

$city = 'ontario';

foreach ($cities as $allowedCity) {

    if (
        preg_match(
            '/-' . preg_quote($allowedCity, '/') . '-on-/i',
            strtolower($slug)
        )
    ) {
        $city = $allowedCity;
        break;
    }
}

            $item['loc'] = url(
                "on/{$subtype}-for-sale-in-{$city}/map/{$slug}"
            );

            return $item;

        })

        ->values();
}

@endphp


@php

if (request()->is('pages.xml')) {

    $items = collect($items)
        ->map(function ($item) {

            $url = $item['loc'];

            // match:
            // /houses-for-sale-in-kwc
            if (
                preg_match(
                    '#https://serik\.ca/([a-z0-9\-]+)$#i',
                    $url,
                    $match
                )
            ) {

                $slug = $match[1];

                // only convert listing pages
                if (
                    str_contains($slug, '-for-sale-in-') ||
                    str_contains($slug, '-for-lease-in-')
                ) {

                    $item['loc'] =
                        url(
                            "on/{$slug}/map"
                        );
                }
            }

            return $item;

        })
        ->values();
        
        
      $hiddenUrls = [
            url('/register-thanks'),
            url('/contact-thanks'),
        ];
        
        $items = collect($items)
            ->filter(function ($item) use ($hiddenUrls) {
        
                if (empty($item['loc'])) {
                    return false;
                }
        
                return !in_array(
                    rtrim($item['loc'], '/'),
                    array_map(fn($u) => rtrim($u, '/'), $hiddenUrls)
                );
        
            })
            ->values();
}

@endphp


@php

if (request()->is('blog-posts-*.xml')) {

    $newUrl = 'https://serik.ca/blog/tips-for-renting-out-your-property';

    $exists = collect($items)->contains(function ($item) use ($newUrl) {
        return rtrim($item['loc'], '/') === rtrim($newUrl, '/');
    });

    if (! $exists) {

        $items = collect($items)
            ->push([
                'loc' => $newUrl,

                // same details as mortgage-calculator
                'priority' => '0.8',
                'freq' => 'daily',
                'lastmod' => '2026-03-31 15:13',

                'translations' => [],
                'alternates' => [],
                'images' => [],
                'videos' => [],
            ])
            ->values();

    }
}

@endphp

<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
>

    
    
    @foreach ($items as $item)
    
    
    
        <url>
            <loc>{{ $item['loc'] }}</loc>
            @if (!empty($item['translations']))
                @foreach ($item['translations'] as $translation)
                    <xhtml:link
                        hreflang="{{ $translation['language'] }}"
                        href="{{ $translation['url'] }}"
                        rel="alternate"
                    />
                @endforeach
            @endif

            @if (!empty($item['alternates']))
                @foreach ($item['alternates'] as $alternate)
                    <xhtml:link
                        href="{{ $alternate['url'] }}"
                        rel="alternate"
                        media="{{ $alternate['media'] }}"
                    />
                @endforeach
            @endif

            @if ($item['priority'] !== null)
                <priority>{{ $item['priority'] }}</priority>
            @endif

            @if ($item['lastmod'] !== null)
                <lastmod>{{ date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])) }}</lastmod>
            @endif

            @if ($item['freq'] !== null)
                <changefreq>{{ $item['freq'] }}</changefreq>
            @endif

            @if (!empty($item['images']))
                @foreach ($item['images'] as $image)
                    <image:image>
                        <image:loc>{{ $image['url'] }}</image:loc>
                        @if (isset($image['title']))
                            <image:title>{{ $image['title'] }}</image:title>
                        @endif
                        @if (isset($image['caption']))
                            <image:caption>{{ $image['caption'] }}</image:caption>
                        @endif
                        @if (isset($image['geo_location']))
                            <image:geo_location>{{ $image['geo_location'] }}</image:geo_location>
                        @endif
                        @if (isset($image['license']))
                            <image:license>{{ $image['license'] }}</image:license>
                        @endif
                    </image:image>
                @endforeach
            @endif

            @if (!empty($item['videos']))
                @foreach ($item['videos'] as $video)
                    <video:video>
                        @if (isset($video['thumbnail_loc']))
                            <video:thumbnail_loc>{{ $video['thumbnail_loc'] }}</video:thumbnail_loc>
                        @endif
                        @if (isset($video['title']))
                            <video:title>
                                <![CDATA[{{ $video['title'] }}]]>
                            </video:title>
                        @endif
                        @if (isset($video['description']))
                            <video:description>
                                <![CDATA[{{ $video['description'] }}]]>
                            </video:description>
                        @endif
                        @if (isset($video['content_loc']))
                            <video:content_loc>{{ $video['content_loc'] }}</video:content_loc>
                        @endif
                        @if (isset($video['duration']))
                            <video:duration>{{ $video['duration'] }}</video:duration>
                        @endif
                        @if (isset($video['expiration_date']))
                            <video:expiration_date>{{ $video['expiration_date'] }}</video:expiration_date>
                        @endif
                        @if (isset($video['rating']))
                            <video:rating>{{ $video['rating'] }}</video:rating>
                        @endif
                        @if (isset($video['view_count']))
                            <video:view_count>{{ $video['view_count'] }}</video:view_count>
                        @endif
                        @if (isset($video['publication_date']))
                            <video:publication_date>{{ $video['publication_date'] }}</video:publication_date>
                        @endif
                        @if (isset($video['family_friendly']))
                            <video:family_friendly>{{ $video['family_friendly'] }}</video:family_friendly>
                        @endif
                        @if (isset($video['requires_subscription']))
                            <video:requires_subscription>{{ $video['requires_subscription'] }}
                            </video:requires_subscription>
                        @endif
                        @if (isset($video['live']))
                            <video:live>{{ $video['live'] }}</video:live>
                        @endif
                        @if (isset($video['player_loc']))
                            <video:player_loc
                                allow_embed="{{ $video['player_loc']['allow_embed'] }}"
                                autoplay="' .
                            $video['player_loc']['autoplay'] }}"
                            >{{ $video['player_loc']['player_loc'] }}</video:player_loc>
                        @endif
                        @if (isset($video['restriction']))
                            <video:restriction relationship="{{ $video['restriction']['relationship'] }}">
                                {{ $video['restriction']['restriction'] }}</video:restriction>
                        @endif
                        @if (isset($video['gallery_loc']))
                            <video:gallery_loc title="{{ $video['gallery_loc']['title'] }}">
                                {{ $video['gallery_loc']['gallery_loc'] }}</video:gallery_loc>
                        @endif
                        @if (isset($video['price']))
                            <video:price currency="{{ $video['price']['currency'] }}">{{ $video['price']['price'] }}
                            </video:price>
                        @endif
                        @if (isset($video['uploader']))
                            <video:uploader info="{{ $video['uploader']['info'] }}">
                                {{ $video['uploader']['uploader'] }}</video:uploader>
                        @endif
                    </video:video>
                @endforeach
            @endif
        </url>
    @endforeach
</urlset>
