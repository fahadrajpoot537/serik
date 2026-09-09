@php
    $showFeaturedImage = theme_option('blog_show_featured_image_in_post_detail', 'yes') == 'yes';
    Theme::set('breadcrumbEnabled', $showFeaturedImage ? 'no' : 'yes');
    Theme::set('breadcrumbStyle', 'without-title');
    Theme::set('currentPostId', $post->getKey());
    $bottomPostDetailSidebar = dynamic_sidebar('bottom_post_detail_sidebar');
    Theme::layout('full-width');
    Theme::set('pageTitle', $post->name);
    Theme::set('pageH1ProvidedByContent', true);
    $author = (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type))
        ? ($post->author ?? null)
        : null;
    $authorName = ($author && trim((string) $author->name)) ? trim((string) $author->name) : null;
@endphp

@if ($post->image && $showFeaturedImage)
    <section class="flat-banner-blog serik-blog-banner" aria-label="{{ __('Featured image') }}">
        {{ RvMedia::image($post->image, $post->name, lazy: false) }}
    </section>
@endif

<section @class(['flat-section-v2 serik-blog-detail', 'flat-section' => ! $bottomPostDetailSidebar])>
    <div class="container">
        <div class="row g-4 g-xl-5">

            <div class="col-lg-2 d-none d-lg-block">
                <div class="toc-sidebar sticky-top serik-blog-detail__toc">
                    <h6>{{ __('Table of Contents') }}</h6>
                    <ul id="tocList"></ul>
                </div>
            </div>

            <div class="col-lg-8">
                <article class="flat-blog-detail serik-blog-detail__article">
                    <header class="serik-blog-detail__header">
                        @if($post->firstCategory)
                            <a href="{{ $post->firstCategory->url }}" class="blog-tag primary serik-blog-detail__cat">{{ $post->firstCategory->name }}</a>
                        @endif
                        <h1 class="serik-blog-detail__title">{!! BaseHelper::clean($post->name) !!}</h1>
                        <div class="serik-blog-detail__meta">
                            @if ($authorName)
                                <span class="serik-blog-detail__meta-item">{{ $authorName }}</span>
                            @endif
                            <span class="serik-blog-detail__meta-item">{{ Theme::formatDate($post->created_at) }}</span>
                        </div>
                    </header>

                    <div class="ck-content single-detail serik-blog-detail__body">
                        {!! BaseHelper::clean($post->content) !!}
                    </div>

                    <div class="my-40 d-flex justify-content-between flex-wrap gap-16">
                        @php
                            $shareSocials = \Botble\Theme\Supports\ThemeSupport::getSocialSharingButtons($post->url, $post->name);
                        @endphp
                        @if($shareSocials)
                            <div class="d-flex flex-wrap align-items-center gap-16">
                                <span class="text-black">{{ __('Share:') }}</span>
                                <ul class="d-flex flex-wrap gap-12">
                                    @foreach($shareSocials as $social)
                                        <li>
                                            <a href="{{ $social['url'] }}" class="box-icon w-40 social square" title="{{ $social['name'] }}">
                                                {!! $social['icon'] !!}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    @php
                        $relatedPosts = get_related_posts($post->id, 5);
                    @endphp

                    @if ($authorName)
                        <div class="mt-12 d-flex align-items-center gap-16 mb-3 serik-blog-detail__author" id="author">
                            <div class="avatar avt-200 round">
                                {{ RvMedia::image($author->avatar_url, $authorName) }}
                            </div>
                            <div class="post-author style-1">
                                <span>{{ $authorName }}</span>
                                <span>{{ Theme::formatDate($post->created_at) }}</span>
                                <p>{{ __('We understand that real estate is about more than just transactions — it’s about important life decisions and transitions. We make the process easier by offering clear communication, honest advice, and a professional approach so that every client can move forward with confidence and clarity.') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($relatedPosts->isNotEmpty())
                        <div class="post-navigation" id="relposts">
                            @foreach($relatedPosts as $related)
                                <div @class(['previous-post' => $loop->first, 'next-post' => ! $loop->first])>
                                    <div class="subtitle">{{ $loop->first ? __('Previous') : __('Next') }}</div>
                                    <div class="h7 fw-7 text-black text-capitalize">
                                        <a href="{{ $related->url }}">{!! BaseHelper::clean($related->name) !!}</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div id="relposts"></div>
                    @endif

                    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $post) !!}
                </article>
                @if($bottomPostDetailSidebar)
                    {!! $bottomPostDetailSidebar !!}
                @endif
            </div>

            <div class="col-lg-2 d-none d-lg-block">
                <div class="ads-sidebar sticky-top serik-blog-detail__aside">
                    {!! apply_filters('ads_render', null, 'post_detail_before') !!}

                    <div class="serik-blog-detail__aside-block">
                        <h2 class="serik-blog-detail__aside-title">{{ __('Properties For Sale') }}</h2>
                        <nav class="serik-blog-detail__aside-nav" aria-label="{{ __('Properties For Sale') }}">
                            <a href="https://serik.ca/map?city=Brampton">Houses for Sale in Brampton</a>
                            <a href="https://serik.ca/map?city=Mississauga">Houses for Sale in Mississauga</a>
                            <a href="https://serik.ca/map?city=Toronto">Houses for Sale in Toronto</a>
                            <a href="https://serik.ca/map?city=Vaughan">Houses for Sale in Vaughan</a>
                            <a href="https://serik.ca/map?city=Oakville">Houses for Sale in Oakville</a>
                            <a href="https://serik.ca/map?city=Milton">Houses for Sale in Milton</a>
                            <a href="https://serik.ca/map?city=Hamilton">Houses for Sale in Hamilton</a>
                            <a href="https://serik.ca/map?city=Ottawa">Houses for Sale in Ottawa</a>
                            <a href="https://serik.ca/map?city=KWC">Houses for Sale in Kitchener</a>
                        </nav>
                    </div>

                    @php
                        $allowedTypes = [
                            'Detached',
                            'Semi-Detached',
                            'Att/Row/Townhouse',
                            'Condo Townhouse',
                            'Condo Apartment',
                            'Duplex'
                        ];

                        $propertySubTypes = \Illuminate\Support\Facades\DB::table('re_properties')
                            ->select('PropertySubType', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                            ->whereIn('PropertySubType', $allowedTypes)
                            ->groupBy('PropertySubType')
                            ->orderByRaw("FIELD(PropertySubType, 'Detached','Semi-Detached','Att/Row/Townhouse','Condo Townhouse','Condo Apartment','Duplex')")
                            ->get();
                    @endphp
                    <div class="serik-blog-detail__aside-block">
                        <h2 class="serik-blog-detail__aside-title">{{ __('Properties Categories') }}</h2>
                        <nav class="serik-blog-detail__aside-nav" aria-label="{{ __('Properties Categories') }}">
                            @foreach ($propertySubTypes as $category)
                                <a href="{{ url('map') . '?transaction=For%20Sale&subtypes=' . urlencode($category->PropertySubType) }}"
                                   title="{{ $category->PropertySubType }}">
                                    {{ $category->PropertySubType === 'Att/Row/Townhouse' ? 'Freehold Townhouse' : $category->PropertySubType }}
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    @if($post->tags->isNotEmpty())
                        <div class="d-flex flex-wrap align-items-center gap-12 mt-4">
                            <span class="text-black">{{ __('Tag:') }}</span>
                            <ul class="d-flex flex-wrap gap-12">
                                @foreach($post->tags as $tag)
                                    <li>
                                        <a href="{{ $tag->url }}" class="blog-tag">{{ $tag->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {!! apply_filters('ads_render', null, 'post_detail_after') !!}
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".flat-blog-item").forEach(function (el) {
        el.style.setProperty("visibility", "visible", "important");
        el.style.setProperty("display", "block", "important");
        el.style.setProperty("opacity", "1", "important");
    });

    const content = document.querySelector(".ck-content");
    const toc = document.getElementById("tocList");
    if (!content || !toc) {
        return;
    }

    const headings = content.querySelectorAll("h2, h3, h4, h5, h6");
    headings.forEach((heading, index) => {
        const id = "heading-" + index;
        heading.setAttribute("id", id);

        const li = document.createElement("li");
        li.style.marginLeft = heading.tagName === "H3" ? "10px" : "0";

        const a = document.createElement("a");
        a.href = "#" + id;
        a.textContent = heading.textContent;

        li.appendChild(a);
        toc.appendChild(li);
    });

    [
        { id: "author", text: "About the Author" },
        { id: "relposts", text: "Related Posts" }
    ].forEach((item) => {
        if (!document.getElementById(item.id)) {
            return;
        }
        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = "#" + item.id;
        a.textContent = item.text;
        li.appendChild(a);
        toc.appendChild(li);
    });
});
</script>
