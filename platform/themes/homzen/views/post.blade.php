@php
    $showFeaturedImage = theme_option('blog_show_featured_image_in_post_detail', 'yes') == 'yes';
    // Hero banner shows the post H1; featured image stays in the article body.
    Theme::set('breadcrumbEnabled', 'yes');
    Theme::set('breadcrumbStyle', 'default');
    Theme::set('currentPostId', $post->getKey());
    $bottomPostDetailSidebar = dynamic_sidebar('bottom_post_detail_sidebar');
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
                        @if ($post->image && $showFeaturedImage)
                            <div class="serik-blog-detail__featured">
                                {{ RvMedia::image($post->image, $post->name, lazy: false, attributes: ['class' => 'serik-blog-detail__featured-img']) }}
                            </div>
                        @endif

                        @if($post->firstCategory)
                            <a href="{{ $post->firstCategory->url }}" class="blog-tag primary serik-blog-detail__cat">{{ $post->firstCategory->name }}</a>
                        @endif
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

                    @php
                        $relatedPosts = get_related_posts($post->id, 5);
                    @endphp

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
                                <h3 class="serik-blog-detail__author-name">{{ $authorName ?: 'Serik Realty' }}</h3>
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
