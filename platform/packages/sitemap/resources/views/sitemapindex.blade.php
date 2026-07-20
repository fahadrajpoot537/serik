{!! '<' . '?' . 'xml version="1.0" encoding="UTF-8"?>' . "\n" !!}

@if (null != $style)
    {!! '<' . '?' . 'xml-stylesheet href="' . asset($style) . '" type="text/xsl"?>' . "\n" !!}
@endif

   
@php
 //'blog-posts-2025-11.xml',
// 'properties-2026-05.xml',  
$allowed = [
   
    'properties-2026-04.xml',
    'properties-2026-03.xml',
    'properties-2026-02.xml',
    'blog-posts-2026-03.xml',
    'pages.xml',
    'agents.xml',
];

$sitemaps = collect($sitemaps)
    ->filter(function ($item) use ($allowed) {

        $file = basename($item['loc']);

        return in_array($file, $allowed);

    })
    ->values();

@endphp

<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    @foreach ($sitemaps as $sitemap)

       <sitemap>
            <loc>{{ $sitemap['loc'] }}</loc>
            @if ($sitemap['lastmod'] !== null)
                <lastmod>{{ date('Y-m-d\TH:i:sP', strtotime($sitemap['lastmod'])) }}</lastmod>
            @endif
        </sitemap>

    @endforeach

</sitemapindex>