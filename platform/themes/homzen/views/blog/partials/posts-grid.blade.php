<div class="row blog-posts-grid">
    @forelse($posts as $post)
        <div class="col-12 col-md-6 col-lg-4">
            <article class="flat-blog-item">
                <a class="img-style" href="{{ $post->url }}" aria-label="{{ $post->name }}">
                    @if ($post->image)
                        {{ RvMedia::image($post->image, $post->name, 'medium-rectangle') }}
                    @else
                        <span class="blog-card-img-placeholder" aria-hidden="true"></span>
                    @endif
                    <span class="date-post">{{ Theme::formatDate($post->created_at) }}</span>
                </a>
                <div class="content-box">
                    <div class="post-author">
                        @if (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type) && ($author = $post->author ?? null) && trim($author->name))
                            <span class="text-black fw-7">{{ $author->name }}</span>
                        @endif

                        @if($category = $post->firstCategory)
                            <span>
                                <a href="{{ $category->url }}">{{ $category->name }}</a>
                            </span>
                        @endif
                    </div>
                    <h5 class="title">
                        <a href="{{ $post->url }}">
                            {!! BaseHelper::clean($post->name) !!}
                        </a>
                    </h5>
                    @if($post->description)
                        <p class="description body-1">{!! BaseHelper::clean(Str::limit($post->description, 120)) !!}</p>
                    @endif
                    <a href="{{ $post->url }}" class="btn-read-more">{{ __('Read More') }}</a>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="blog-posts-empty">{{ __('No posts found in this category.') }}</div>
        </div>
    @endforelse
</div>

@if ($posts instanceof \Illuminate\Contracts\Pagination\Paginator && $posts->hasPages())
    {{ $posts->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) }}
@endif
