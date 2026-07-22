



<section
    class="flat-section-v3 flat-testimonial"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="cus-layout-1">
        <div class="row align-items-center">
            <div class="col-lg-3">
                {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode, 'centered' => false, 'animation' => false]) !!}

                @if ($shortcode->description)
                    <p class="text-variant-1 p-16">{!! BaseHelper::clean($shortcode->description) !!}</p>
                @endif

                <div class="box-navigation">
                    <div class="navigation swiper-nav-next nav-next-testimonial">
                        <x-core::icon
                            name="ti ti-chevron-left"
                            class="icon"
                        />
                    </div>
                    <div class="navigation swiper-nav-prev nav-prev-testimonial">
                        <x-core::icon
                            name="ti ti-chevron-right"
                            class="icon"
                        />
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div
                    class="swiper tf-sw-testimonial"
                    data-preview-lg="2.6"
                    data-preview-md="2"
                    data-preview-sm="2"
                    data-space="30"
                    {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
                >
                    <div class="swiper-wrapper">
                        @foreach ($testimonials as $testimonial)
                            <div class="swiper-slide">
                             <a href="https://www.google.com/search?sca_esv=2647d4455316a158&sxsrf=ANbL-n6MY0kVoYiuEVSqoyr4QkHbaVArxQ:1777899787713&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOfvoulo1K3CdIC5M45JUCC4r873m2qwN7EicjGCMgYWtNzBTKNl8PkUaJZYYaU6q_EC5LNKLYfGq1WitFm3vQOmt5TFOzgO3dLn3bfm3a6YNV2Pe8g%3D%3D&q=Serik+Realty+Inc.+Reviews&sa=X&ved=2ahUKEwihxavq2J-UAxUlUaQEHXlrBNMQ0bkNegQIKhAH&biw=1482&bih=704&dpr=1.25" target="_blank"></a>   <div
                                    class="box-tes-item wow fadeIn"
                                    data-wow-delay=".2s"
                                    data-wow-duration="2000ms"
                                >
                                    @include(Theme::getThemeNamespace('partials.shortcodes.testimonials.partials.content'))
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

 <style>
    .sr3-section {
      padding: 60px 20px;
      background: #f8fafc;
      font-family: Arial, sans-serif;
    }

    .sr3-title {
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .sr3-desc {
      text-align: center;
      max-width: 700px;
      margin: 0 auto 40px;
      color: #555;
    }

    .sr3-card {
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 8px 20px rgba(0,0,0,0.06);
      transition: 0.3s;
      height: 100%;
    }

    .sr3-card:hover {
      transform: translateY(-6px);
    }

    .sr3-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .sr3-content {
      padding: 20px;
    }

    .sr3-step {
      font-weight: 600;
      color: #2563eb;
      margin-bottom: 5px;
    }

    .sr3-heading {
      font-weight: 600;
      margin-bottom: 10px;
    }

    .sr3-text {
      color: #666;
      font-size: 0.95rem;
    }

    .sr3-cta {
      text-align: center;
      margin-top: 40px;
    }

    .sr3-btn {
      background: #2563eb;
      color: #fff;
      padding: 12px 30px;
      border-radius: 30px;
      border: none;
      font-weight: 600;
      transition: 0.3s;
    }

    .sr3-btn:hover {
      background: #1e4ed8;
    }
  </style>

<section class="sr3-section">
  <div class="container">

    <h2 class="sr3-title">Your Upsizing Journey Made Simple</h2>

    <p class="sr3-desc">
      From preparing your home for sale to finding the perfect upsized home, Serik Realty makes each step simple and clear.
    </p>

    <div class="row g-4">

      <!-- Step 1 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="sr3-card">
          <img src="https://serik.ca/storage/step1.jpg" class="sr3-img" alt="{{ img_alt(null, 'step1.jpg', __('Upsizing guide: Talk to Our Experts')) }}">
          <div class="sr3-content">
            <div class="sr3-step">Step 1</div>
            <div class="sr3-heading">Talk to Our Experts</div>
            <div class="sr3-text">
              Share your goals and get a personalized upsizing plan.
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="sr3-card">
          <img src="https://serik.ca/storage/step2.jpg" class="sr3-img" alt="{{ img_alt(null, 'step2.jpg', __('Upsizing guide: Prepare and List Your Home')) }}">
          <div class="sr3-content">
            <div class="sr3-step">Step 2</div>
            <div class="sr3-heading">Prepare & List Your Home</div>
            <div class="sr3-text">
              We position your home to achieve maximum value.
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="sr3-card">
          <img src="https://serik.ca/storage/step3.jpg" class="sr3-img" alt="{{ img_alt(null, 'step3.jpg', __('Upsizing guide: Secure Your Upgrade')) }}">
          <div class="sr3-content">
            <div class="sr3-step">Step 3</div>
            <div class="sr3-heading">Secure Your Upgrade</div>
            <div class="sr3-text">
              Find and negotiate your ideal upsized home.
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="sr3-card">
          <img src="https://serik.ca/storage/step4.jpg" class="sr3-img" alt="{{ img_alt(null, 'step4.jpg', __('Upsizing guide: Smooth Transition and Closing')) }}">
          <div class="sr3-content">
            <div class="sr3-step">Step 4</div>
            <div class="sr3-heading">Smooth Transition & Closing</div>
            <div class="sr3-text">
              We coordinate everything for a seamless move.
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- CTA -->
    <div class="sr3-cta">
     <a href="#appointment-schedule"> <button class="sr3-btn">Book Your Free Consultation</button></a>
    </div>

  </div>
</section>

 <style>
    .sr4-offer-section {
      padding: 50px 20px;
      background: linear-gradient(135deg, #2563eb, #1e40af);
      color: #fff;
      border-radius: 20px;
      margin: 40px auto;
    }

    .sr4-offer-wrapper {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 25px;
      flex-wrap: wrap;
    }

    .sr4-offer-left {
      display: flex;
      align-items: center;
      gap: 15px;
      max-width: 600px;
    }

    .sr4-icon-box {
      width: 60px;
      height: 60px;
      background: rgba(255,255,255,0.15);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .sr4-icon-box i {
      font-size: 28px;
      color: #fff;
    }

    .sr4-title {
      font-size: 1.6rem;
      font-weight: 700;
      margin: 0;
    }

    .sr4-text {
      margin: 5px 0 0;
      font-size: 0.95rem;
      color: #e0e7ff;
    }

    .sr4-btn {
      background: #fff;
      color: #1e40af;
      padding: 12px 26px;
      border-radius: 30px;
      font-weight: 600;
      border: none;
      transition: 0.3s ease;
      white-space: nowrap;
    }

    .sr4-btn:hover {
      background: #e0e7ff;
    }

    /* Mobile */
    @media (max-width: 768px) {
      .sr4-offer-wrapper {
        flex-direction: column;
        text-align: center;
      }

      .sr4-offer-left {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>


<div class="container">
  <div class="sr4-offer-section">

    <div class="sr4-offer-wrapper">

      <!-- LEFT CONTENT -->
      <div class="sr4-offer-left">
        <div class="sr4-icon-box">
          <i class="ti ti-map"></i>
        </div>

        <div>
          <h4 class="sr4-title">Limited Time Offer!</h4>
          <p class="sr4-text">
            Up-size with Serik Realty and save up to <strong>1.5% cash back</strong> on your next home.
          </p>
        </div>
      </div>

      <!-- CTA -->
      <div>
       <a href="{{ url('/contact-us') }}"> <button class="sr4-btn">Claim Your Discount Now</button></a>
      </div>

    </div>

  </div>
</div>

<section class="py-5">
  <div class="container">
    <h2 class="srk-section-title">Frequently Asked Questions</h2>
    <div class="accordion mt-4" id="srkFaq">

      <div class="accordion-item">
        <button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#q1">
          What are the essential services Serik Realty provides when helping me move to a larger home?
        </button>
        <div id="q1" class="accordion-collapse collapse show">
          <div class="accordion-body">
            Serik Realty assists the entire upsizing process, from pricing and selling your current home to finding, negotiating, and securing your next home. Besides that, we connect you with trusted mortgage and legal professionals to ensure stress-free closing.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q2">
          Should I sell my current home before purchasing a bigger one? What does Serik Realty recommend?
        </button>
        <div id="q2" class="accordion-collapse collapse">
          <div class="accordion-body">
            Serik Realty will assess your current situation and market conditions to determine the best approach. Many clients sell first to access equity, while others buy first and use bridge financing for a smooth transition.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q3">
          Can Serik Realty assist me in buying a new home while selling my current one?
        </button>
        <div id="q3" class="accordion-collapse collapse">
          <div class="accordion-body">
            Yes. We specialize in efficiently coordinating both transactions to minimize risk, avoid duplicate moves, and align closing dates effectively.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q4">
          How does Serik Realty determine the right price for my current home?
        </button>
        <div id="q4" class="accordion-collapse collapse">
          <div class="accordion-body">
            Serik Realty uses in-depth market analysis, local expertise, and current buyer trends to position your home competitively. This ensures the maximum value of your home and timely results.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q5">
          What makes Serik Realty different when it comes to upsizing services?
        </button>
        <div id="q5" class="accordion-collapse collapse">
          <div class="accordion-body">
            At Serik Realty, our approach is not just transactional; rather, we focus on clarity, honest guidance, and structured processes so that you can make long-term decisions with confidence that align with your lifestyle goals.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q6">
          Does Serik Realty assist with financing or mortgage guidance?
        </button>
        <div id="q6" class="accordion-collapse collapse">
          <div class="accordion-body">
            While Serik Realty does not directly provide loans, we can connect you with trusted mortgage professionals so that you can understand your financial options and move forward with confidence.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>



