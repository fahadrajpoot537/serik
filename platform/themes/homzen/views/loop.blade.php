<style>
    img{
       /* height:250px !important;*/
    }
    .img-style{
        height: 250px;
        align-content: center;
        background-color: aliceblue;
    }
        
    
</style>

<section class="flat-section">
    {!! apply_filters('ads_render', null, 'blog_list_before') !!}

    <div class="row">
        <div class="col-lg-9">
            <div class="flat-blog-list">
                <div class="row">
                    
                     @foreach($posts as $post)
                     <div class="col-md-4" style="zoom:0.8">
                            <div class="flat-blog-item">
                                @if ($post->image)
                                    <a class="img-style" href="{{ $post->url }}">
                                        {{ RvMedia::image($post->image, $post->name) }}
                                        <span class="date-post">{{ Theme::formatDate($post->created_at) }}</span>
                                    </a>
                                @endif
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
        
                                        @if (! $post->image)
                                            <span>{{ Theme::formatDate($post->created_at) }}</span>
                                        @endif
                                    </div>
                                    <h5 class="title">
                                        <a href="{{ $post->url }}">
                                            {!! BaseHelper::clean($post->name) !!}
                                        </a>
                                    </h5>
                                    @if($post->description)
                                        <p class="description body-1">{!! BaseHelper::clean(Str::limit($post->description)) !!}</p>
                                    @endif
                                    <a href="{{ $post->url }}" class="btn-read-more">{{ __('Read More') }}</a>
                                </div>
                            </div>
                     </div>
                        
                    @endforeach
                </div>
               

                {{ $posts->links(Theme::getThemeNamespace('partials.pagination')) }}
            </div>
        </div>
        <div class="col-lg-3">
            <aside class="sidebar-blog" style="zoom:0.7">
                {!! dynamic_sidebar('blog_sidebar') !!}
            </aside>
        </div>
    </div>

    {!! apply_filters('ads_render', null, 'blog_list_after') !!}
</section>
