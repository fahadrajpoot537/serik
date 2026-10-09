<div class="blog-posts-grid">
    @forelse($posts as $post)
        <article class="flat-blog-item blog-post-card">
            <a class="img-style" href="{{ $post->url }}" aria-label="{{ $post->name }}">
                @if ($post->image)
                    {{ RvMedia::image($post->image, $post->name, 'medium-rectangle') }}
                @else
                    <span class="blog-card-img-placeholder" aria-hidden="true"></span>
                @endif
                <span class="date-post">{{ Theme::formatDate($post->created_at) }}</span>
            </a>
            <div class="content-box">
                @if($category = $post->firstCategory)
                    <div class="post-author">
                        <span>
                            <a href="{{ $category->url }}">{{ $category->name }}</a>
                        </span>
                    </div>
                @endif
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
    @empty
        <div class="blog-posts-empty">{{ __('No posts found in this category.') }}</div>
    @endforelse
</div>

@if ($posts instanceof \Illuminate\Contracts\Pagination\Paginator && $posts->hasPages())
    {{ $posts->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) }}
@endif
