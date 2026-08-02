@if($posts->isNotEmpty())
    @php($carouselOnMobile = (bool) ($carouselOnMobile ?? false))

    @if($carouselOnMobile)
        {{-- Dedicated mobile carousel (no Swiper classes — avoids CSS conflicts) --}}
        <div class="serik-blog-m d-md-none" role="region" aria-label="Latest blog posts">
            <div class="serik-blog-m__track">
                @foreach($posts as $post)
                    <article class="serik-blog-m__card">
                        <a class="serik-blog-m__media" href="{{ $post->url }}">
                            {{ RvMedia::image($post->image, $post->name, 'medium-rectangle', attributes: [
                                'width' => 400,
                                'height' => 260,
                                'decoding' => 'async',
                                'loading' => 'lazy',
                                'class' => 'serik-blog-m__img',
                            ]) }}
                            <span class="serik-blog-m__date">{{ Theme::formatDate($post->created_at) }}</span>
                        </a>
                        <div class="serik-blog-m__body">
                            @if($category = $post->firstCategory)
                                <span class="serik-blog-m__cat">{{ $category->name }}</span>
                            @endif
                            <h6 class="serik-blog-m__title">
                                <a href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                            </h6>
                            @if($post->description)
                                <p class="serik-blog-m__excerpt">{!! BaseHelper::clean(Str::limit($post->description, 90)) !!}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row {{ $carouselOnMobile ? 'd-none d-md-flex' : '' }}">
        @foreach($posts as $post)
            <div class="box col-lg-4 col-md-6">
                <div class="flat-blog-item hover-img wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
                    <a class="img-style" href="{{ $post->url }}">
                        {{ RvMedia::image($post->image, $post->name, 'medium-rectangle', attributes: ['width' => 400, 'height' => 300, 'decoding' => 'async', 'loading' => 'lazy']) }}
                        <span class="date-post">{{ Theme::formatDate($post->created_at) }}</span>
                    </a>
                    <div class="content-box">
                        <div class="post-author">
                            @if (theme_option('blog_show_author_name', 'yes') == 'yes' && class_exists($post->author_type) && ($author = $post->author ?? null) && trim($author->name))
                                <span class="fw-6">{{ $post->author->name }}</span>
                            @endif

                            @if($category = $post->firstCategory)
                                <span>
                                    <a href="{{ $category->url }}">{{ $category->name }}</a>
                                </span>
                            @endif
                        </div>
                        <h6 class="title">
                            <a href="{{ $post->url }}" class="w-100">{!! BaseHelper::clean($post->name) !!}</a>
                        </h6>
                        @if($post->description)
                            <p class="description" style="text-align:justify">{!! BaseHelper::clean(Str::limit($post->description)) !!}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
