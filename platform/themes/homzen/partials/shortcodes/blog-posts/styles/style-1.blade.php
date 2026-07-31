<style>
    /* Desktop / tablet blog cards */
    #page-home .flat-latest-new .flat-blog-item {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(22, 30, 45, 0.08);
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(16, 24, 40, 0.06);
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }

    #page-home .flat-latest-new .flat-blog-item .img-style {
        display: block;
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 10;
        overflow: hidden;
        background: #f3f4f6;
    }

    #page-home .flat-latest-new .flat-blog-item .img-style img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block;
    }

    #page-home .flat-latest-new .flat-blog-item .content-box {
        padding: 1rem 1.1rem 1.15rem;
        margin-top: 0 !important;
        flex: 1 1 auto;
    }

    #page-home .flat-latest-new .row > .box {
        display: flex;
        margin-bottom: 1.25rem;
    }

    /* ------------------------------------------------------------------ */
    /* Mobile-only blog carousel — fixed vw widths, native swipe          */
    /* ------------------------------------------------------------------ */
    .serik-blog-m {
        display: none;
    }

    @media (max-width: 767.98px) {
        #page-home .flat-latest-new {
            padding-top: 1.25rem !important;
            padding-bottom: 1.25rem !important;
        }

        #page-home .flat-latest-new .section-title {
            font-size: 1.3rem !important;
            margin-top: 0.25rem !important;
            margin-bottom: 0.85rem !important;
            text-align: left !important;
        }

        #page-home .flat-latest-new .btn-view.button-prop,
        #page-home .flat-latest-new > .container > br {
            display: none !important;
        }

        #page-home .flat-latest-new > .container > div:first-child {
            text-align: left !important;
            font-size: 0.8125rem;
            color: #5b6573 !important;
            margin-bottom: 0.15rem;
        }

        .serik-blog-m {
            display: block;
            width: 100%;
            margin: 0;
            /* Bleed to screen edges for natural carousel feel */
            margin-left: calc(-1 * clamp(16px, 2.5vw, 40px));
            margin-right: calc(-1 * clamp(16px, 2.5vw, 40px));
            width: calc(100% + 2 * clamp(16px, 2.5vw, 40px));
            padding: 0 clamp(16px, 2.5vw, 40px);
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: clamp(16px, 2.5vw, 40px);
            overscroll-behavior-x: contain;
            touch-action: pan-x;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .serik-blog-m::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .serik-blog-m__track {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 12px;
            width: max-content;
            padding-bottom: 2px;
        }

        .serik-blog-m__card {
            flex: 0 0 78vw;
            width: 78vw;
            max-width: 300px;
            min-width: 240px;
            scroll-snap-align: start;
            background: #fff;
            border: 1px solid rgba(22, 30, 45, 0.1);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(16, 24, 40, 0.06);
            display: flex;
            flex-direction: column;
        }

        .serik-blog-m__media {
            position: relative;
            display: block;
            width: 100%;
            height: 150px;
            overflow: hidden;
            background: #eef1f5;
            flex-shrink: 0;
        }

        .serik-blog-m__media img,
        .serik-blog-m__img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center;
            display: block;
            border: 0;
        }

        .serik-blog-m__date {
            position: absolute;
            left: 0;
            bottom: 0;
            z-index: 1;
            background: var(--primary-color, #0255a1);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            padding: 5px 10px;
            line-height: 1.2;
        }

        .serik-blog-m__body {
            padding: 12px 14px 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1 1 auto;
        }

        .serik-blog-m__cat {
            font-size: 11px;
            font-weight: 600;
            color: var(--primary-color, #0255a1);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .serik-blog-m__title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: -0.01em;
        }

        .serik-blog-m__title a {
            color: #161e2d;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .serik-blog-m__excerpt {
            margin: 0;
            font-size: 12.5px;
            line-height: 1.45;
            color: #5b6573;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }
</style>

<section class="flat-section-v3 flat-latest-new" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        <div style="text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>

        <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>

        <a href="{{ get_blog_page_url() }}" class="btn-view button-prop" style="float:right; margin-top:-45px;">
            <span class="text" style="font-weight: 500;">View All</span>
            <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
        </a>

        <br>

        @include(Theme::getThemeNamespace('views.blog.partials.posts'), ['carouselOnMobile' => true])
    </div>
</section>
