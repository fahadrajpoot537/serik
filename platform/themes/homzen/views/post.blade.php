@php
    $showFeaturedImage = theme_option('blog_show_featured_image_in_post_detail', 'yes') == 'yes';
    Theme::set('breadcrumbEnabled', $showFeaturedImage ? 'no' : 'yes');
    Theme::set('breadcrumbStyle', 'without-title');
    Theme::set('currentPostId', $post->getKey());
    $bottomPostDetailSidebar = dynamic_sidebar('bottom_post_detail_sidebar');
    Theme::layout('full-width');
    Theme::set('pageTitle', $post->name);
@endphp


<style>
.toc-sidebar {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

.toc-sidebar ul {
    list-style: none;
    padding-left: 0;
}

.toc-sidebar li {
    margin-bottom: 8px;
}

.toc-sidebar a {
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.toc-sidebar a:hover {
    color: #0d6efd;
}

</style>


@if ($post->image && $showFeaturedImage)
    <section class="flat-banner-blog" style="height: 300px;">
        {{ RvMedia::image($post->image, $post->name, lazy: false) }}
    </section>
@endif

<section @class(['flat-section-v2', 'flat-section' => ! $bottomPostDetailSidebar])>
    <div class="container">
       

        <div class="row">

    <!-- LEFT: Table of Contents -->
                <div class="col-lg-2 d-none d-lg-block">
                    <div class="toc-sidebar sticky-top" style="top: 100px;zoom:0.7">
                        <h6>Table of Contents</h6>
                        <ul id="tocList">
                            
                        </ul>
                    </div>
                </div>
            
                <!-- CENTER: Main Content -->
                <div class="col-lg-8" style="zoom:0.7">
                <div class="flat-blog-detail">
                    @if($post->firstCategory)
                        <a href="{{ $post->firstCategory->url }}" class="blog-tag primary">{{ $post->firstCategory->name }}</a>
                    @endif
                    <h1 class="h2">{!! BaseHelper::clean($post->name) !!}</h1>
                    

                    <div class="ck-content single-detail">
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
                    
                    <div class="mt-12 d-flex align-items-center gap-16 mb-3" id="author">
                        @if (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type) && ($author = $post->author ?? null) && trim($author->name))
                            <div class="avatar avt-200 round">
                                {{ RvMedia::image($author->avatar_url, $author->name) }}
                            </div>
                        @endif
                        <div class="post-author style-1">
                            @if (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type) && ($author = $post->author ?? null) && trim($author->name))
                                <span>{{ $post->author->name }}</span>
                            @endif
                            <span>{{ Theme::formatDate($post->created_at) }}</span>
                         <p>we understand that real estate is about more than just transactions — it’s about important life decisions and transitions. We make the process easier by offering clear communication, honest advice, and a professional approach so that every client can move forward with confidence and clarity.</p>
                    
                        
                        </div>
                       </div>

                    @if($relatedPosts->isNotEmpty())
                        <div class="post-navigation">
                            @foreach($relatedPosts as $post)
                                <div @class(['previous-post' => $loop->first, 'next-post' => ! $loop->first])>
                                    <div class="subtitle">{{ $loop->first ? __('Previous') : __('Next') }}</div>
                                    <div class="h7 fw-7 text-black text-capitalize">
                                        <a href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    
                    
                    
                    

                    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $post) !!}
                </div>
                <div id="relposts"></div>
                @if($bottomPostDetailSidebar)
                    {!! $bottomPostDetailSidebar !!}
                @endif
            </div>
           <!-- RIGHT: Ads -->
            <div class="col-lg-2 d-none d-lg-block">
                <div class="ads-sidebar sticky-top" style="top: 100px;zoom:0.7; z-index:10 !important;">
                    {!! apply_filters('ads_render', null, 'post_detail_before') !!}
                   
                     <h2 style="color:#000; font-size:24px;margin-top:50px;">Properties For Sale</h2>
                    
                    <a href="https://serik.ca/map?city=Brampton">Houses for Sale in Brampton</a>

                    <a href="https://serik.ca/map?city=Mississauga">Houses for Sale in Mississauga</a>
                    
                    <a href="https://serik.ca/map?city=Toronto">Houses for Sale in Toronto</a>
                    
                    <a href="https://serik.ca/map?city=Vaughan">Houses for Sale in Vaughan</a>
                    
                    <a href="https://serik.ca/map?city=Oakville">Houses for Sale in Oakville</a>
                    
                    <a href="https://serik.ca/map?city=Milton">Houses for Sale in Milton</a>
                    
                    <a href="https://serik.ca/map?city=Hamilton">Houses for Sale in Hamilton</a>
                    
                    <a href="https://serik.ca/map?city=Ottawa">Houses for Sale in Ottawa</a>
                    
                    <a href="https://serik.ca/map?city=KWC">Houses for Sale in Kitchener</a>
                    
                    
                    
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
                $propertySubTypes = \Illuminate\Support\Facades\DB::table('re_properties')
                    ->select('PropertySubType', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->whereIn('PropertySubType', $allowedTypes)
                    ->groupBy('PropertySubType')
                    ->orderByRaw("FIELD(PropertySubType, 'Detached','Semi-Detached','Att/Row/Townhouse','Condo Townhouse','Condo Apartment','Duplex')")
                    ->get();
                @endphp
                <br>
                    <h2 style="color:#000; font-size:24px;margin-top:50px;">Properties Categories</h2>
                    @foreach ($propertySubTypes as $category)
                    <a href="{{ url('map') . '?transaction=For%20Sale&subtypes=' . urlencode($category->PropertySubType) }}"
                           title="{{ $category->PropertySubType }}">
                    
                                    {{ $category->PropertySubType === 'Att/Row/Townhouse' ? 'Freehold Townhouse' : $category->PropertySubType }}
                    </a><br>
                    
                   
                    @endforeach
                    
                     @if($post->tags->isNotEmpty())
                            <div class="d-flex flex-wrap align-items-center gap-12">
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
                    
                </div>
                
                 <div class="ads-sidebar sticky-top" style="margin-top: 50px;top:400px;zoom:0.7">
                   
                    
                   
                    
                </div>
                
            </div>
        
        </div>

       
    </div>
</section>





<script>
function showBlogItems() {
    document.querySelectorAll(".flat-blog-item").forEach(function (el) {
        el.style.setProperty("visibility", "visible", "important");
        el.style.setProperty("display", "block", "important");
        el.style.setProperty("opacity", "1", "important");
    });
}

// Run on load
document.addEventListener("DOMContentLoaded", showBlogItems);

// Run again after delay (for JS-loaded content)
setTimeout(showBlogItems, 1000);
</script>

<script>



document.addEventListener("DOMContentLoaded", function () {
    const content = document.querySelector(".ck-content");
    const toc = document.getElementById("tocList");

    if (!content || !toc) return;

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

    // ✅ ADD STATIC ITEMS AT END
    const staticItems = [
        { id: "author", text: "About the Author" },
        { id: "relposts", text: "Related Posts" }
    ];

    staticItems.forEach(item => {
        const li = document.createElement("li");

        const a = document.createElement("a");
        a.href = "#" + item.id;
        a.textContent = item.text;

        li.appendChild(a);
        toc.appendChild(li);
    });
});
</script>
