@php
    $blogCategories = get_all_categories([], ['slugable']);

    $activeCategory = $category ?? null;
    $blogPageUrl = get_blog_page_url();
@endphp

<style>
    .blog-list-page {
        overflow-x: clip;
    }

    .blog-list-page .blog-list-layout {
        --bs-gutter-x: 1.5rem;
        --bs-gutter-y: 1.5rem;
    }

    .blog-list-page .flat-blog-list {
        padding-right: 0;
        min-width: 0;
    }

    .blog-list-page .flat-blog-list .flat-blog-item {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .blog-list-page .flat-blog-list .flat-blog-item .content-box {
        margin-top: 0;
    }

    .blog-list-page .flat-blog-list .flat-blog-item .img-style {
        border-radius: 12px;
    }

    .blog-list-page .blog-posts-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(0, 1fr);
        width: 100%;
    }

    .blog-list-page .blog-category-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 0 0 16px;
        margin-bottom: 8px;
    }

    .blog-list-page .blog-category-tab {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid #dbe2ea;
        background: #fff;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        cursor: pointer;
    }

    .blog-list-page .blog-category-tab:hover,
    .blog-list-page .blog-category-tab.active {
        background: #0255a1;
        border-color: #0255a1;
        color: #fff;
    }

    .blog-list-page .blog-posts-container {
        position: relative;
        min-height: 120px;
        min-width: 0;
    }

    .blog-list-page .blog-posts-container.is-loading {
        opacity: 0.55;
        pointer-events: none;
    }

    .blog-list-page .flat-blog-item {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .blog-list-page .flat-blog-item:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        transform: translateY(-2px);
    }

    .blog-list-page .flat-blog-item .img-style {
        display: block;
        position: relative;
        aspect-ratio: 400 / 260;
        overflow: hidden;
        background: #f1f5f9;
    }

    .blog-list-page .flat-blog-item .img-style img,
    .blog-list-page .flat-blog-item .blog-card-img-placeholder {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .blog-list-page .flat-blog-item .blog-card-img-placeholder {
        background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
    }

    .blog-list-page .flat-blog-item .content-box {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 14px 16px 18px;
    }

    .blog-list-page .flat-blog-item .post-author {
        font-size: 12px;
        margin-bottom: 6px;
    }

    .blog-list-page .flat-blog-item .title {
        font-size: 1rem;
        line-height: 1.35;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.7em;
    }

    .blog-list-page .flat-blog-item .title a {
        color: #0f172a;
        text-decoration: none;
    }

    .blog-list-page .flat-blog-item .description {
        font-size: 14px;
        line-height: 1.5;
        color: #64748b;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .blog-list-page .flat-blog-item .btn-read-more {
        margin-top: auto;
        font-size: 13px;
        font-weight: 600;
        color: #0255a1;
        text-decoration: none;
    }

    .blog-list-page .blog-posts-empty {
        grid-column: 1 / -1;
        padding: 40px 20px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px dashed #dbe2ea;
    }

    .blog-list-page .sidebar-blog {
        min-width: 0;
    }

    @media (min-width: 576px) {
        .blog-list-page .blog-posts-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }
    }

    @media (min-width: 992px) {
        .blog-list-page.flat-section {
            padding-top: 40px;
            padding-bottom: 48px;
        }

        .blog-list-page .blog-list-layout {
            --bs-gutter-x: 2rem;
        }

        .blog-list-page .sidebar-blog {
            position: sticky;
            top: 96px;
        }

        .blog-list-page .blog-posts-grid {
            gap: 1.5rem;
        }
    }

    @media (min-width: 1200px) {
        .blog-list-page .blog-posts-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1400px) {
        .blog-list-page .blog-posts-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 768px) and (max-width: 991px) {
        .blog-list-page .sidebar-blog .recent-post-item {
            gap: 12px;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .blog-list-page .sidebar-blog .recent-post-item .img-style {
            width: 72px;
            height: 72px;
        }

        .blog-list-page .sidebar-blog .recent-post-item .content .title {
            font-size: 14px;
            line-height: 1.35;
        }
    }

    @media (max-width: 991px) {
        .blog-list-page.flat-section {
            padding-top: 24px;
            padding-bottom: 32px;
        }

        .blog-list-page .sidebar-blog {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #e8edf2;
            position: static;
        }

        .blog-list-page .sidebar-blog .widget-search,
        .blog-list-page .sidebar-blog .widget-box {
            margin-top: 0;
        }

        .blog-list-page .sidebar-blog .widget-box + .widget-box {
            margin-top: 20px;
        }

        .blog-list-page .sidebar-blog .recent-post-item {
            gap: 12px;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .blog-list-page .sidebar-blog .recent-post-item .img-style {
            width: 76px;
            height: 76px;
        }

        .blog-list-page .sidebar-blog .recent-post-item .content .title {
            font-size: 15px;
            line-height: 1.35;
        }
    }

    @media (max-width: 767px) {
        .blog-list-page .blog-category-tabs {
            gap: 6px;
            padding-bottom: 12px;
        }

        .blog-list-page .blog-category-tab {
            font-size: 13px;
            padding: 7px 14px;
        }

        .blog-list-page .flat-blog-item .content-box {
            padding: 12px 14px 16px;
        }

        .blog-list-page .flat-blog-item .title {
            font-size: 1.05rem;
            min-height: 0;
        }

        .blog-list-page .flat-blog-item .description {
            font-size: 13px;
            -webkit-line-clamp: 2;
            margin-bottom: 10px;
        }

        .blog-list-page .flat-blog-item .post-author {
            font-size: 11px;
        }

        .blog-list-page .flat-blog-item .img-style {
            aspect-ratio: 16 / 10;
        }

        .blog-list-page .flat-pagination {
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }
    }

    @media (max-width: 575px) {
        .blog-list-page .blog-posts-grid {
            gap: 1rem;
        }
    }
</style>

<section class="flat-section blog-list-page">
    {!! apply_filters('ads_render', null, 'blog_list_before') !!}

    <div class="row g-4 align-items-start blog-list-layout">
        <div class="col-12 col-md-8 col-lg-8 col-xl-9">
            <div class="flat-blog-list">
                @if ($blogCategories->isNotEmpty())
                    <nav class="blog-category-tabs" aria-label="{{ __('Blog categories') }}">
                        <a href="{{ $blogPageUrl }}"
                           data-category-id=""
                           @class(['blog-category-tab', 'active' => ! $activeCategory])>
                            {{ __('All') }}
                        </a>
                        @foreach ($blogCategories as $blogCategory)
                            <a href="{{ $blogCategory->url }}"
                               data-category-id="{{ $blogCategory->id }}"
                               @class(['blog-category-tab', 'active' => $activeCategory && $activeCategory->id === $blogCategory->id])>
                                {{ $blogCategory->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <div id="blog-posts-container" class="blog-posts-container">
                    @include(Theme::getThemeNamespace('views.blog.partials.posts-grid'), ['posts' => $posts])
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4 col-xl-3">
            <aside class="sidebar-blog">
                {!! dynamic_sidebar('blog_sidebar') !!}
            </aside>
        </div>
    </div>

    {!! apply_filters('ads_render', null, 'blog_list_after') !!}
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('blog-posts-container');
    const tabsNav = document.querySelector('.blog-category-tabs');

    if (!container || !tabsNav) {
        return;
    }

    let currentCategoryId = tabsNav.querySelector('.blog-category-tab.active')?.dataset.categoryId || '';

    function bindPagination() {
        container.querySelectorAll('.flat-pagination a').forEach((link) => {
            if (link.dataset.blogAjaxBound) {
                return;
            }

            link.dataset.blogAjaxBound = '1';
            link.addEventListener('click', function (event) {
                event.preventDefault();

                const pageUrl = new URL(this.href, window.location.origin);
                const page = parseInt(pageUrl.searchParams.get('page') || '1', 10);

                loadPosts(currentCategoryId, page, this.href);
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function loadPosts(categoryId, page, pushUrl) {
        const endpoint = new URL('{{ route('public.ajax.blog-posts') }}', window.location.origin);

        if (categoryId) {
            endpoint.searchParams.set('category_id', categoryId);
        }

        if (page > 1) {
            endpoint.searchParams.set('page', String(page));
        }

        container.classList.add('is-loading');

        fetch(endpoint.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then((response) => response.json())
            .then((result) => {
                if (result?.data) {
                    container.innerHTML = result.data;
                    bindPagination();
                }
            })
            .catch(() => {})
            .finally(() => {
                container.classList.remove('is-loading');
            });

        if (pushUrl) {
            history.pushState({ categoryId: categoryId || '', page: page || 1 }, '', pushUrl);
        }
    }

    tabsNav.addEventListener('click', function (event) {
        const tab = event.target.closest('.blog-category-tab');

        if (!tab) {
            return;
        }

        event.preventDefault();

        tabsNav.querySelectorAll('.blog-category-tab').forEach((item) => item.classList.remove('active'));
        tab.classList.add('active');

        currentCategoryId = tab.dataset.categoryId || '';
        loadPosts(currentCategoryId, 1, tab.href);
    });

    bindPagination();

    window.addEventListener('popstate', function (event) {
        if (!event.state || !document.querySelector('.blog-category-tabs')) {
            return;
        }

        const categoryId = event.state.categoryId || '';
        const page = event.state.page || 1;
        const tab = tabsNav.querySelector(`[data-category-id="${categoryId}"]`);

        tabsNav.querySelectorAll('.blog-category-tab').forEach((item) => item.classList.remove('active'));

        if (tab) {
            tab.classList.add('active');
        }

        currentCategoryId = categoryId;
        loadPosts(currentCategoryId, page, null);
    });
});
</script>
