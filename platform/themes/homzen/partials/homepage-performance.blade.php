<style>
    /* Reserve space before images/fonts/sliders paint — homepage CLS */
    .logo img,
    .nav-logo img {
        width: auto;
        height: 44px;
        aspect-ratio: 160 / 44;
        object-fit: contain;
    }

    .flat-slider.home-2 {
        min-height: clamp(360px, 42vw, 520px);
    }

    .flat-slider.home-2 .img-banner-left {
        min-height: 240px;
    }

    .flat-slider.home-2 .img-banner-left img,
    .flat-slider.home-2 .slider-home2 img {
        width: 100%;
        height: auto;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        display: block;
    }

    .flat-slider.home-2 .swiper.slider-sw-home2,
    .flat-slider.home-2 .swiper.slider-sw-home2 .swiper-slide {
        min-height: clamp(220px, 28vw, 360px);
    }

    .flat-slider.home-2 .swiper.slider-sw-home2 .swiper-wrapper {
        min-height: inherit;
    }

    .hero-banner-headline,
    .flat-slider.home-2 .title1 {
        min-height: 1.2em;
    }

    #about-agent.flat-agents .box-agent .box-img {
        aspect-ratio: 3 / 4;
        min-height: 200px;
    }

    #about-agent.flat-agents .box-agent .box-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .flat-blog-item .img-style {
        display: block;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: #f3f4f6;
    }

    .flat-blog-item .img-style img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .flat-service-v2 .icon-box .icon,
    .flat-service-v2 .icon-box img.icon {
        width: 48px;
        height: 48px;
        aspect-ratio: 1 / 1;
        object-fit: contain;
    }

    .flat-service-v2 .banner-video {
        aspect-ratio: 16 / 9;
        min-height: 180px;
        overflow: hidden;
        background: #f3f4f6;
    }

    .flat-service-v2 .banner-video img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .calculator-buttons img {
        width: 100%;
        aspect-ratio: 3 / 1;
        object-fit: contain;
    }

    .tf-sw-locations {
        min-height: 220px;
    }

    .tf-sw-locations .swiper-wrapper {
        min-height: inherit;
    }

    .serik-prop-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .shortcode-lazy-loading {
        min-height: 120px;
    }

    body {
        font-display: swap;
    }
</style>
