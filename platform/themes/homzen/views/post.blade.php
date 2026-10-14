@php
    $showFeaturedImage = theme_option('blog_show_featured_image_in_post_detail', 'yes') == 'yes';
    // Hero banner shows the post H1; featured image stays in the article body.
    Theme::set('breadcrumbEnabled', 'yes');
    Theme::set('breadcrumbStyle', 'default');
    Theme::set('currentPostId', $post->getKey());
    Theme::layout('full-width');
    Theme::set('pageTitle', $post->name);
    Theme::set('pageH1', $post->name);
    $author = (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type))
        ? ($post->author ?? null)
        : null;
    $authorName = ($author && trim((string) $author->name)) ? trim((string) $author->name) : null;
    $authorAvatar = $author?->avatar_url ?? null;
    $shareThumb = $post->image ? RvMedia::getImageUrl($post->image) : null;
    $shareSocials = \Botble\Theme\Supports\ThemeSupport::getSocialSharingButtons(
        $post->url,
        $post->name,
        $shareThumb
    );
    // Theme option may be stored as "[]", which skips package defaults.
    if (empty($shareSocials)) {
        $shareUrl = urlencode($post->url);
        $shareTitle = rawurlencode(strip_tags((string) $post->name));
        $shareSocials = [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => '<i class="ti ti-brand-facebook" aria-hidden="true"></i>',
                'url' => 'https://www.facebook.com/sharer.php?u=' . $shareUrl,
            ],
            'x' => [
                'name' => 'X',
                'icon' => '<i class="ti ti-brand-x" aria-hidden="true"></i>',
                'url' => 'https://x.com/intent/tweet?url=' . $post->url . '&text=' . $shareTitle,
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => '<i class="ti ti-brand-linkedin" aria-hidden="true"></i>',
                'url' => 'https://www.linkedin.com/sharing/share-offsite?url=' . $shareUrl,
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => '<i class="ti ti-brand-whatsapp" aria-hidden="true"></i>',
                'url' => 'https://api.whatsapp.com/send?text=' . $shareTitle . '%20' . $post->url,
            ],
            'email' => [
                'name' => 'Email',
                'icon' => '<i class="ti ti-mail" aria-hidden="true"></i>',
                'url' => 'mailto:?subject=' . $shareTitle . '&body=' . $shareUrl,
            ],
        ];
    }
@endphp

<style>
    /* Match navbar pill: logo (left) ↔ phone (right) */
    .serik-blog-detail > .container,
    .serik-blog-related > .container {
        width: min(1600px, calc(100% - 32px)) !important;
        max-width: min(1600px, calc(100% - 32px)) !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 22px !important;
        padding-right: 22px !important;
        box-sizing: border-box !important;
    }
    @media (max-width: 991.98px) {
        .serik-blog-detail > .container,
        .serik-blog-related > .container {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 16px !important;
            padding-right: 16px !important;
        }
    }

    .serik-blog-related {
        padding: 48px 0 64px !important;
        background: #f8fafc;
        border-top: 1px solid #e8edf2;
    }
    .serik-blog-related__title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--serik-reference-navy, #0b2340);
        margin: 0 0 1.5rem;
        text-align: center;
    }
    .serik-blog-related__grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
        width: 100% !important;
        align-items: stretch;
    }
    @media (min-width: 768px) {
        .serik-blog-related__grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }
    .serik-blog-related__card {
        display: flex !important;
        flex-direction: column !important;
        min-width: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        background: #fff !important;
        border: 1px solid #e8edf2 !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        float: none !important;
        clear: none !important;
    }
    .serik-blog-related__media {
        display: block !important;
        aspect-ratio: 16 / 10;
        overflow: hidden;
        background: #f1f5f9;
    }
    .serik-blog-related__media img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
    }
    .serik-blog-related__body {
        padding: 14px 16px 18px;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        gap: 8px;
    }
    .serik-blog-related__meta { font-size: 12px; color: #64748b; }
    .serik-blog-related__card-title {
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.35;
        margin: 0;
    }
    .serik-blog-related__card-title a { color: #0b2340; text-decoration: none; }
    .serik-blog-related__excerpt {
        font-size: 13px;
        line-height: 1.45;
        color: #64748b;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .serik-blog-related__read {
        margin-top: auto;
        font-size: 13px;
        font-weight: 600;
        color: #0b4c9f;
        text-decoration: none;
    }
</style>

<section class="flat-section-v2 flat-section serik-blog-detail">
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
                        @if ($post->image && $showFeaturedImage)
                            <div class="serik-blog-detail__featured">
                                {{ RvMedia::image($post->image, $post->name, lazy: false, attributes: ['class' => 'serik-blog-detail__featured-img']) }}
                            </div>
                        @endif
                    </header>

                    <div class="ck-content single-detail serik-blog-detail__body">
                        {!! BaseHelper::clean($post->content) !!}
                    </div>

                    <section class="serik-blog-detail__author-card" id="author" aria-label="{{ __('About the Author') }}">
                        <div class="serik-blog-detail__author-card-main">
                            <div class="serik-blog-detail__author-avatar">
                                @if ($authorAvatar)
                                    {{ RvMedia::image($authorAvatar, $authorName ?: __('Author'), attributes: ['width' => 96, 'height' => 96]) }}
                                @else
                                    <span class="serik-blog-detail__author-avatar-fallback" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($authorName ?: 'S', 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="serik-blog-detail__author-copy">
                                <p class="serik-blog-detail__author-label">{{ __('About the Author') }}</p>
                                @php
                                    $authorDisplay = $authorName ?: 'Serik Realty';
                                    $authorLinkedIn = stripos($authorDisplay, 'sadaqat') !== false
                                        ? 'https://www.linkedin.com/in/sadaqat-sheikh-1b92981b6'
                                        : null;
                                @endphp
                                <h3 class="serik-blog-detail__author-name">
                                    <span>{{ $authorDisplay }}</span>
                                    @if ($authorLinkedIn)
                                        <a
                                            href="{{ $authorLinkedIn }}"
                                            class="serik-blog-detail__author-linkedin"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="{{ __('LinkedIn profile of :name', ['name' => $authorDisplay]) }}"
                                            title="LinkedIn"
                                        >
                                            <x-core::icon name="ti ti-brand-linkedin" />
                                        </a>
                                    @endif
                                </h3>
                                <p class="serik-blog-detail__author-bio">
                                    {{ __('We understand that real estate is about more than just transactions — it’s about important life decisions and transitions. We make the process easier by offering clear communication, honest advice, and a professional approach so that every client can move forward with confidence and clarity.') }}
                                </p>
                            </div>
                        </div>

                        @if ($shareSocials)
                            <div class="serik-blog-detail__share">
                                <p class="serik-blog-detail__share-label">{{ __('Share this article') }}</p>
                                <ul class="serik-blog-detail__share-list">
                                    @foreach($shareSocials as $social)
                                        <li>
                                            <a
                                                href="{{ $social['url'] }}"
                                                class="serik-blog-detail__share-btn"
                                                title="{{ $social['name'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                {!! $social['icon'] !!}
                                                <span>{{ $social['name'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>

                    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $post) !!}
                </article>
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

@php
    $relatedPosts = collect(get_related_posts($post->getKey(), 3));
    if ($relatedPosts->count() < 3) {
        $excludeIds = $relatedPosts->pluck('id')->push($post->getKey())->filter()->all();
        $filler = collect(get_recent_posts(8))
            ->reject(static fn ($p) => in_array($p->id, $excludeIds, true))
            ->take(3 - $relatedPosts->count());
        $relatedPosts = $relatedPosts->concat($filler)->values()->take(3);
    }
@endphp
@if ($relatedPosts->isNotEmpty())
    <section class="flat-section serik-blog-related" aria-label="{{ __('Related Posts') }}">
        <div class="container">
            <h2 class="serik-blog-related__title">{{ __('Related Posts') }}</h2>
            <div class="serik-blog-related__grid">
                @foreach ($relatedPosts as $related)
                    <article class="serik-blog-related__card">
                        <a class="serik-blog-related__media" href="{{ $related->url }}" tabindex="-1" aria-hidden="true">
                            {{ RvMedia::image($related->image, $related->name, 'medium-rectangle', attributes: [
                                'width' => 400,
                                'height' => 260,
                                'decoding' => 'async',
                                'loading' => 'lazy',
                            ]) }}
                        </a>
                        <div class="serik-blog-related__body">
                            <span class="serik-blog-related__meta">{{ Theme::formatDate($related->created_at) }}</span>
                            <h3 class="serik-blog-related__card-title">
                                <a href="{{ $related->url }}">{!! BaseHelper::clean($related->name) !!}</a>
                            </h3>
                            @if ($related->description)
                                <p class="serik-blog-related__excerpt">{!! BaseHelper::clean(Str::limit($related->description, 100)) !!}</p>
                            @endif
                            <a href="{{ $related->url }}" class="serik-blog-related__read">
                                {{ __('Read More') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".serik-blog-related__card, .flat-blog-item").forEach(function (el) {
        el.style.setProperty("visibility", "visible", "important");
        el.style.setProperty("opacity", "1", "important");
        el.style.removeProperty("display");
    });

    const content = document.querySelector(".serik-blog-detail__body");
    const toc = document.getElementById("tocList");
    if (!content || !toc) {
        return;
    }

    // Prefer H2 section titles; many posts store them as H4. Never include H3.
    let headings = Array.from(content.querySelectorAll("h2"));
    if (headings.length === 0) {
        headings = Array.from(content.querySelectorAll("h4"));
    }
    headings.forEach((heading, index) => {
        const id = heading.id || ("heading-" + index);
        heading.setAttribute("id", id);

        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = "#" + id;
        a.textContent = (heading.textContent || "").trim();
        li.appendChild(a);
        toc.appendChild(li);
    });

    const author = document.getElementById("author");
    if (author) {
        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = "#author";
        a.textContent = "About the Author";
        li.appendChild(a);
        toc.appendChild(li);
    }
});
</script>
