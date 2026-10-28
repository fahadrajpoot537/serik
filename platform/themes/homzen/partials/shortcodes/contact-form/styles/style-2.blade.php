@php
    $backgroundImage = $shortcode->background_image ? RvMedia::getImageUrl($shortcode->background_image) : null;
    $isFreeHomeEvaluationPage = request()->is('free-home-evaluation') || request()->is('evaluation');
    // On the estimator page these blocks are hidden via JS anyway — skip SSR to cut HTML/forms/images.
    $renderContactMain = ! $isFreeHomeEvaluationPage;
    $renderSecondMain = ! $isFreeHomeEvaluationPage;
    $renderBoostFormMain = ! $isFreeHomeEvaluationPage;
@endphp

<style>
@if ($renderBoostFormMain)
.progress-steps {
    gap: 8px;
    font-size: 14px;
}
.progress-steps .step {
    flex: 1;
    padding-bottom: 6px;
    border-bottom: 4px solid #e5e5e5;
    color: #999;
}
.progress-steps .step.active {
    border-color: #0255a1;
    color: #000;
    font-weight: 600;
}

.form-step {
    display: none;
}
.form-step.active {
    display: block;
}

.option-group {
    max-width: 420px;
    margin: 0 auto;
    display: grid;
    gap: 14px;
}

.option-btn {
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
    font-weight: 500;
    transition: 0.2s;
}
.option-btn:hover,
.btn-hov:hover,
.option-btn.active.btn-hov,
.option-btn.active {
    border-color: #0255a1;
    background: #0255a1;
    color:white;
}

.btn-color-next{
    background-color: #0255a1;
}

@media (max-width: 576px) {
    .progress-steps {
        font-size: 11px;
    }
}

.logo_form{
    width:158px;
}
@endif





/* Keep evaluation content at 1:1 with header/logo — zoom breaks edge alignment */
#thirdMain {
    zoom: 1;
}
#thirdMain.serik-eval-layout > .container {
    max-width: 1140px;
}
#thirdMain.serik-eval-layout .section-box {
    padding: 8px 0 28px;
    background: transparent;
    border-radius: 0;
}
#thirdMain.serik-eval-layout #estimate-result.estimate-result {
    width: 100%;
    max-width: none;
    box-sizing: border-box;
}

.section-box{
    background:#fff;
    padding:0px 30px 30px 30px;
    border-radius:10px;
}

.counter{
       
    display:flex;
     gap: 16px;
    border:1px solid #ddd;
    border-radius:6px;
    overflow:hidden;
}

.counter button{
    width:40px;
    border:none;
    background:#f3f3f3;
    font-size:18px;
}

.counter input{
    border:none;
    text-align:center;
    width:60px;
}

.note-box{
    background:#dff2f4;
    padding:15px;
    border-radius:8px;
    font-size:14px;
}

.btn-main{
    background:#0255a1;
    color:#fff;
    border-radius:8px;
    padding:12px;
    font-weight:500;
}

.contact-box{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 0 10px rgba(0,0,0,0.05);
}

.contact-box input,
.contact-box textarea{
    margin-bottom:12px;
}



.prop-address-wrap {
    position: relative;
    z-index: 30;
}
#address-suggestions {
    position: absolute;
    top: calc(100% - 0.35rem);
    left: 0;
    right: 0;
    width: auto;
    z-index: 9999;
    max-height: 360px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    display: none;
}
#address-suggestions:not(:empty) {
    display: block;
}
#address-suggestions .suggestion-item {
    cursor: pointer;
}
#address-suggestions .suggestion-item:hover {
    background: #f3f7fc;
    color: #0255a1;
}
.estimate-result {
    display: none;
    margin-top: 20px;
    padding: 22px 20px 18px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #d7e3f0;
    box-shadow: 0 8px 24px rgba(2, 85, 161, 0.06);
}
.estimate-result.is-visible {
    display: block;
}
.estimate-result.is-error {
    background: #fff6f6;
    border-color: #f0c7c7;
}
.estimate-result .estimate-note {
    font-size: 13px;
    color: #666;
    margin: 0;
}
.estimate-result .estimate-pick-list {
    list-style: none;
    margin: 0.75rem 0 0;
    padding: 0;
    display: grid;
    gap: 0.4rem;
}
.estimate-result .estimate-pick-btn {
    display: block;
    width: 100%;
    text-align: left;
    border: 1px solid #c5d8ef;
    background: #fff;
    color: #0b4c9f;
    border-radius: 8px;
    padding: 0.55rem 0.75rem;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.estimate-result .estimate-pick-btn:hover,
.estimate-result .estimate-pick-btn:focus-visible {
    background: #eef5fc;
    border-color: #0b4c9f;
    outline: none;
}
.estimate-scale {
    margin: 0.5rem 0 1.25rem;
    width: 100%;
}
.estimate-scale__rail {
    height: 2px;
    background: #cfd8e3;
    border-radius: 2px;
    margin: 0 0.35rem 0.85rem;
}
.estimate-scale__marks {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.5rem;
    width: 100%;
    align-items: start;
}
.estimate-scale__mark {
    position: relative;
    text-align: center;
    min-width: 0;
    padding-top: 0.15rem;
}
.estimate-scale__mark.is-low {
    text-align: left;
}
.estimate-scale__mark.is-high {
    text-align: right;
}
.estimate-scale__mark::before {
    content: "";
    display: block;
    width: 10px;
    height: 10px;
    margin: 0 auto 0.45rem;
    border-radius: 50%;
    background: #94a3b8;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #cbd5e1;
}
.estimate-scale__mark.is-low::before {
    margin-left: 0;
    margin-right: auto;
}
.estimate-scale__mark.is-high::before {
    margin-left: auto;
    margin-right: 0;
}
.estimate-scale__mark.is-mid {
    color: #0d9488;
    font-weight: 700;
}
.estimate-scale__mark.is-mid::before {
    background: #0d9488;
    box-shadow: 0 0 0 1px #0d9488;
}
.estimate-scale__label {
    display: block;
    font-size: 11px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.15rem;
    line-height: 1.2;
}
.estimate-scale__value {
    display: block;
    font-size: clamp(13px, 1.45vw, 16px);
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.estimate-scale__mark.is-mid .estimate-scale__value {
    color: #0d9488;
    font-size: clamp(15px, 1.7vw, 19px);
}
.estimate-dom {
    margin: 0.25rem 0 1rem;
}
.estimate-dom__title {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.55rem;
    font-size: 14px;
    color: #334155;
    margin-bottom: 0.85rem;
    line-height: 1.35;
}
.estimate-dom__title > span:first-child {
    display: block;
    max-width: 100%;
    margin-top: 5% !important;
}
.estimate-dom__controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem 0.75rem;
    width: 100%;
}
.estimate-dom__input {
    width: 9.5rem;
    max-width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.4rem 0.6rem;
    font-weight: 700;
    color: #0f172a;
    background: #fff;
    font-size: 14px;
}
.estimate-dom__track-wrap {
    position: relative;
    padding: 1.1rem 0 0.15rem;
}
.estimate-dom__pin {
    position: absolute;
    top: 0;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 12px solid #0d9488;
    transition: left 0.25s ease;
}
.estimate-dom__track {
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(90deg, #ef4444 0%, #f59e0b 45%, #3b82f6 100%);
}
.estimate-dom__labels {
    display: flex;
    justify-content: space-between;
    margin-top: 0.45rem;
    font-size: 12px;
    color: #64748b;
}
.estimate-dom__labels span:first-child { color: #dc2626; font-weight: 600; }
.estimate-dom__labels span:last-child { color: #2563eb; font-weight: 600; }
.estimate-beta {
    margin: 0.85rem 0 1rem;
    padding: 0.75rem 0.9rem;
    border-radius: 8px;
    background: #e8f4ff;
    color: #1e3a5f;
    font-size: 12.5px;
    line-height: 1.45;
}
.estimate-history {
    margin-top: 0.35rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.9rem;
}
.estimate-history h4 {
    margin: 0 0 0.35rem;
    font-size: 15px;
    color: #0f172a;
}
.estimate-history__sub {
    margin: 0 0 0.75rem;
    font-size: 12.5px;
    color: #64748b;
}
.estimate-history__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.estimate-history__table th,
.estimate-history__table td {
    padding: 0.5rem 0.4rem;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: top;
}
.estimate-history__table th {
    color: #64748b;
    font-weight: 600;
    white-space: nowrap;
}
.estimate-history__table .eh-price {
    color: #0d9488;
    font-weight: 700;
    white-space: nowrap;
}
.estimate-history__table .eh-event {
    font-weight: 600;
    color: #0f172a;
}
.estimate-history__empty {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}
#estimate-forsale-section.estimate-forsale {
    display: none;
    width: 100%;
    max-width: none;
    margin: 0 0 2rem;
    padding: 0.5rem 16px 1rem;
    box-sizing: border-box;
}
#thirdMain.serik-eval-layout > #estimate-forsale-section.estimate-forsale {
    width: 100%;
    max-width: none;
}
#estimate-forsale-section.estimate-forsale.is-visible {
    display: block;
}
@media (min-width: 576px) {
    #estimate-forsale-section.estimate-forsale {
        padding-left: 24px;
        padding-right: 24px;
    }
}
@media (min-width: 1200px) {
    #estimate-forsale-section.estimate-forsale {
        padding-left: 32px;
        padding-right: 32px;
    }
}
.estimate-forsale__title {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
}
.estimate-forsale__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
    padding: 0 2px;
}
.estimate-forsale__nav {
    display: flex;
    gap: 0.4rem;
}
.estimate-forsale__nav-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #c9d7e8;
    border-radius: 999px;
    background: #fff;
    color: #0255a1;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.estimate-forsale__nav-btn:hover {
    border-color: #0255a1;
    background: #f3f7fc;
}
.estimate-forsale__nav-btn.swiper-button-disabled {
    opacity: 0.35;
    cursor: default;
}
.estimate-forsale__swiper {
    width: 100%;
    overflow: hidden;
    padding-bottom: 0.25rem;
}
.estimate-forsale__swiper .swiper-slide {
    height: auto;
}
.estimate-forsale__card {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #fff;
    border: 1px solid #d7e3f0;
    border-radius: 10px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.estimate-forsale__card:hover {
    border-color: #0255a1;
    box-shadow: 0 8px 20px rgba(2, 85, 161, 0.12);
    color: inherit;
    text-decoration: none;
}
.estimate-forsale__media {
    position: relative;
    aspect-ratio: 16 / 10;
    background: #e8eef5;
    overflow: hidden;
}
.estimate-forsale__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.estimate-forsale__badge {
    position: absolute;
    left: 8px;
    top: 8px;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    background: #0255a1;
    color: #fff;
}
.estimate-forsale__body {
    padding: 0.65rem 0.75rem 0.8rem;
}
.estimate-forsale__price {
    margin: 0 0 0.25rem;
    font-size: 15px;
    font-weight: 700;
    color: #0b4c9f;
}
.estimate-forsale__addr {
    margin: 0 0 0.35rem;
    font-size: 12.5px;
    line-height: 1.35;
    color: #1e293b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.estimate-forsale__meta {
    margin: 0;
    font-size: 11.5px;
    color: #64748b;
}
.estimate-meta {
    font-size: 12.5px;
    color: #475569;
    margin: 0.55rem 0 0;
}
.estimate-basis {
    display: inline-block;
    margin: 0 0 0.85rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    background: #dbeafe;
    color: #0b4c9f;
}
.estimate-basis.is-asking {
    background: #ffedd5;
    color: #9a3412;
}
@media (max-width: 767.98px) {
    .estimate-scale__marks { gap: 0.25rem; }
    .estimate-scale__value { font-size: 12px; }
    .estimate-scale__mark.is-mid .estimate-scale__value { font-size: 14px; }
    .estimate-history__table { display: block; overflow-x: auto; }
}














.srk-how-wrapper {
  position: relative;
  overflow: hidden;
}

/* Top rounded background */
.srk-top-bg {
  
  position: absolute;
  top: 0;
  left: 5%;
  width: 90%;
  height: 200px;
  background: #e9ecef;
  border-radius: 120px;
  z-index: -1;
}

/* Icon row spacing */
.srk-icon-row {
  position: relative;
  z-index: 2;
  margin-top: 110px;
}

/* Icon circle */
.srk-icon-circle {
  width: 140px;
  height: 140px;
  background: #0d2c7d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: auto;
  box-shadow: 0 10px 20px rgba(0,0,0,0.15);
  border: 10px solid #f1f1f1;
}

.srk-icon-circle img {
  width: 160px;
  max-width:160px !important;
}

/* Color variations */
.srk-icon-blue {
  background: #1f5bd8;
}

.srk-icon-light {
  background: #5fa0e0;
}

/* Vertical line */
.srk-line {
  width: 4px;
  height: 50px;
  background: #000;
  margin: 10px auto 0;
  position: relative;
}

.srk-line::before,
.srk-line::after {
  content: '';
  width: 14px;
  height: 14px;
  background: #000;
  border-radius: 50%;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.srk-line::before {
  top: -7px;
}

.srk-line::after {
  bottom: -7px;
}

/* Cards */
.srk-card {
  border-radius: 10px;
}

/* Responsive Fix */
@media (max-width: 768px) {
  .srk-top-bg {
    height: 150px;
    border-radius: 80px;
  }

  .srk-icon-circle {
    width: 110px;
    height: 110px;
  }

  .srk-icon-circle img {
    width: 45px;
  }
}












.srk-privacy-section {
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

/* Card */
.srk-card-privacy {
  background: #ffffff;
  border-radius: 16px;
  transition: all 0.35s ease;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  position: relative;
  overflow: hidden;
}

.srk-card-privacy:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

/* Icon Box */
.srk-icon-box {
  width: 70px;
  height: 70px;
  margin: 0 auto;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: #fff;
  position: relative;
}


/* Heading */
.srk-privacy-title {
  font-size: 28px;
  letter-spacing: 0.5px;
}

/* Subtext */
.srk-privacy-sub {
  max-width: 500px;
  margin: 0 auto;
}





</style>






@php
$faqs = collect([
    (object)[
        'question' => 'How does the 1.5% cash‑back program work when buying a home?',
        'answer' => 'When you purchase a property with our team, we share part of the buyer-side commission with you. This means you can receive up to 1.5% cash back, which can help cover closing costs or increase your down payment.'
    ],
    (object)[
        'question' => 'Can I get a free evaluation of my home before selling?',
        'answer' => 'Yes. We provide a free home evaluation to help you understand the current market value of your property. Our experts analyze comparable sales, market trends, and property features to determine the best listing price.'
    ],
    (object)[
        'question' => 'Do you help buyers find homes in different cities across Ontario?',
        'answer' => 'Absolutely. We assist buyers in multiple cities across Ontario and help you search properties based on location, budget, and lifestyle preferences to find your ideal home.'
    ],
    (object)[
        'question' => 'What services do you provide for people who want to sell their property?',
        'answer' => 'We offer complete selling services including pricing strategy, property marketing, listing on MLS, negotiations, and closing support to ensure you get the best value for your home.'
    ],
    (object)[
        'question' => 'Can I schedule a consultation before starting the buying or selling process?',
        'answer' => 'Yes. You can book a confidential consultation with our real estate experts to discuss your goals, budget, and timeline before you start buying or selling property.'
    ]
]);
@endphp

@php
    $faqSideImage = is_file(public_path('storage/faqs.jpg'))
        ? asset('storage/faqs.jpg')
        : (is_file(public_path('pictures/real-estate-investing-tips-ontario.webp'))
            ? asset('pictures/real-estate-investing-tips-ontario.webp')
            : '');
    $faqSideAlt = $faqSideImage !== '' ? __('Serik Realty team answering frequently asked questions') : '';
@endphp

@if ($renderContactMain)
<section id="contactMain"
    class="flat-section-v3 flat-slider-contact serik-faq-panel"
>
    <div class="container">
        <div class="row content-wrap">
            <div class="col-lg-7">
                <div class="content-left serik-faq-panel__media">
                    @if($faqSideImage !== '')
                        <img src="{{ $faqSideImage }}"
                             alt="{{ $faqSideAlt }}"
                             width="640"
                             height="283"
                             decoding="async"
                             loading="lazy"
                             class="serik-faq-panel__image">
                    @endif
                    <div class="tf-faq serik-faq-list">
                        <ul class="box-faq" id="wrapper-faq">
                            @foreach($faqs as $index => $faq)
                                @php $faqId = "faq-{$index}" @endphp
                                <li class="faq-item">
                                    <a href="#{{ $faqId }}" class="faq-header collapsed" data-bs-toggle="collapse" aria-expanded="false" aria-controls="{{ $faqId }}" id="{{ $faqId }}-q">
                                        {!! BaseHelper::clean($faq->question) !!}
                                    </a>
                                    <div id="{{ $faqId }}" class="collapse" data-bs-parent="#wrapper-faq" role="region" aria-labelledby="{{ $faqId }}-q">
                                        <p class="faq-body">
                                            {!! BaseHelper::clean($faq->answer) !!}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="box-contact-v2">
                    <h3>Contact Us</h3>
                    {!! $form->renderForm() !!}
                </div>
            </div>
        </div>
    </div>
   
</section>
@endif

@if ($renderSecondMain)
<section id="secondMain"
    class="flat-section-v3 flat-slider-contact"
    @style(["background-image: url('$backgroundImage') !important" => $backgroundImage])
>
    <div class="container">
        <div class="row content-wrap">
            <div class="col-lg-7">
                <div class="content-left">
                    <div class="box-title">
                        @if($shortcode->title)
                            <h2 class="section-title mt-4 fw-6 text-white">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                        @endif
                        @if($shortcode->subtitle)
                            <div class="text-subtitle text-white">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
                        @endif
                    </div>
                    @if($shortcode->description)
                        <p class="body-body-2 text-white">{!! BaseHelper::clean($shortcode->description) !!}</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-5">
                <div class="box-contact-v2">
                    {!! $form->renderForm() !!}
                </div>
            </div>
        </div>

    </div>
    <div class="overlay"></div>
</section>
@endif

<section id="thirdMain" @class(['serik-eval-layout' => $isFreeHomeEvaluationPage])>
@if ($isFreeHomeEvaluationPage)
<style>
.serik-sell-offer-wrap {
    padding: 1.75rem 0 0.25rem;
}
.serik-sell-offer {
    position: relative;
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 1.5rem;
    background: linear-gradient(105deg, #06152b 0%, #0a1f3d 55%, #0d2a52 100%);
    color: #fff;
    border-radius: 18px;
    padding: 1.75rem 1.75rem 1.35rem;
    box-shadow: 0 18px 40px rgba(6, 21, 43, 0.28);
    overflow: visible;
}
.serik-sell-offer__badge {
    position: absolute;
    top: -12px;
    left: 22px;
    z-index: 2;
    background: #f5c518;
    color: #111;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.42rem 0.85rem;
    border-radius: 8px 8px 6px 6px;
    line-height: 1.1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
}
.serik-sell-offer__copy {
    flex: 1 1 auto;
    min-width: 0;
    padding-top: 0.35rem;
}
.serik-sell-offer__title {
    margin: 0 0 0.55rem;
    font-size: clamp(1.15rem, 2.2vw, 1.55rem);
    font-weight: 700;
    line-height: 1.35;
    color: #fff;
}
.serik-sell-offer__title .serik-sell-offer__price {
    color: #f5c518;
    white-space: nowrap;
}
.serik-sell-offer__text {
    margin: 0 0 1rem;
    font-size: 0.98rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.88);
    max-width: 40rem;
}
.serik-sell-offer__note {
    margin: 0;
    font-size: 0.8rem;
    font-style: italic;
    color: rgba(255, 255, 255, 0.55);
}
.serik-sell-offer__card {
    flex: 0 0 auto;
    align-self: center;
    min-width: 148px;
    text-align: center;
    border: 1.5px solid rgba(245, 197, 24, 0.85);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    background: rgba(255, 255, 255, 0.03);
    box-shadow: 0 0 0 1px rgba(245, 197, 24, 0.15), inset 0 0 24px rgba(245, 197, 24, 0.06);
}
.serik-sell-offer__card-label {
    display: block;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.2rem;
}
.serik-sell-offer__card-price {
    display: block;
    font-size: clamp(1.55rem, 2.5vw, 1.9rem);
    font-weight: 800;
    color: #f5c518;
    line-height: 1.15;
    letter-spacing: -0.01em;
}
.serik-sell-offer__card-price small {
    font-size: 0.72em;
    font-weight: 700;
    margin-left: 0.15rem;
}
.serik-sell-offer__card-sub {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.92);
}
@media (max-width: 767px) {
    .serik-sell-offer {
        flex-direction: column;
        padding: 1.5rem 1.15rem 1.2rem;
        gap: 1.1rem;
    }
    .serik-sell-offer__badge {
        left: 16px;
    }
    .serik-sell-offer__card {
        width: 100%;
        max-width: 220px;
        align-self: flex-start;
    }
}
</style>
<div class="serik-sell-offer-wrap">
    <div class="container">
        <aside class="serik-sell-offer" aria-label="{{ __('Limited-time fixed commission offer') }}">
            <div class="serik-sell-offer__badge">{{ __('LIMITED-TIME OFFER') }}</div>
            <div class="serik-sell-offer__copy">
                <h2 class="serik-sell-offer__title">
                    {!! __('Sell your home with us for just :price — fixed commission', [
                        'price' => '<span class="serik-sell-offer__price">$5,999 CAD</span>',
                    ]) !!}
                </h2>
                <p class="serik-sell-offer__text">
                    {{ __('Get expert support to sell your home with a simple, transparent fixed commission of $5,999 CAD.') }}
                </p>
                <p class="serik-sell-offer__note">*{{ __('Terms and conditions apply.') }}</p>
            </div>
            <div class="serik-sell-offer__card" aria-hidden="true">
                <span class="serik-sell-offer__card-label">{{ __('Pay only') }}</span>
                <span class="serik-sell-offer__card-price">$5,999<small>CAD</small></span>
                <span class="serik-sell-offer__card-sub">{{ __('FLAT COMMISSION') }}</span>
            </div>
        </aside>
    </div>
</div>
@endif
        <div class="container py-5">
  {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode]) !!}
            <div class="row g-4 align-items-start">
            
                <!-- LEFT FORM -->
                <div class="col-lg-8">
                
                    <div class="section-box">
                    
                        <h3 class="mb-3">Home Details</h3>
              <form id="prop-name" >     
                        <label class="mb-2">Please enter your property address</label>
                        <div class="prop-address-wrap">
                        <input type="text" name="prop-address" id="prop-address" class="form-control mb-2" autocomplete="off">
                        <div id="address-suggestions" class="list-group"></div>
                        </div>
                        
                        <div class="row g-3 mb-4">
                        
                        <div class="col-md-3">
                            <label>Bedroom</label>
                            <div class="counter">
                            <button type="button">-</button>
                            <input type="text" id="bedrooms" value="" placeholder="—" inputmode="numeric">
                            <button type="button">+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Partial Bedroom</label>
                            <div class="counter">
                            <button type="button">-</button>
                            <input type="text" id="bedrooms-below" value="" placeholder="—" inputmode="numeric">
                            <button type="button">+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Bathroom</label>
                            <div class="counter">
                            <button type="button">-</button>
                            <input type="text" id="bathrooms" value="" placeholder="—" inputmode="numeric">
                            <button type="button">+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Garage</label>
                            <div class="counter">
                            <button type="button">-</button>
                            <input type="text" id="garage" value="" placeholder="—" inputmode="numeric">
                            <button type="button">+</button>
                            </div>
                        </div>
                        
                        </div>
                    
                    <div class="row g-3 mb-4">
                    
                        <div class="col-md-4">
                            <label>Square Footage</label>
                            <div class="input-group">
                            <input type="text" id="sqft" class="form-control" value="">
                            <span class="input-group-text">sqft</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label>Property Tax</label>
                            <div class="input-group">
                            <input type="text" id="tax" class="form-control" value="">
                            <span class="input-group-text">per year</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label>Property Type</label>
                            <select class="form-select" id="property-type">
                                <option value="">Select type</option>
                                <option value="Detached">Detached</option>
                                <option value="Semi-Detached">Semi Detached</option>
                                <option value="Att/Row/Townhouse">Freehold Townhouse</option>
                                <option value="Condo Townhouse">Condo Townhouse</option>
                                <option value="Condo Apartment">Condo Apartment</option>
                                <option value="Duplex">Duplex</option>
                                <option value="Land">Land</option>
                                <option value="Farm">Farm</option>
                                <option value="Vacant Land">Vacant Land</option>
                            </select>
                        </div>
                    
                    </div>
                    
                    <div class="row g-3 mb-4">
                    
                    <div class="col-md-6">
                    <label>Lot Width (Front)</label>
                    <div class="input-group">
                    <input type="text" class="form-control" id="lot-width" value="">
                    <span class="input-group-text">feet</span>
                    </div>
                    </div>
                    
                    <div class="col-md-6">
                    <label>Lot Depth</label>
                    <div class="input-group">
                    <input type="text" class="form-control" id="lot-depth" value="">
                    <span class="input-group-text">feet</span>
                    </div>
                    </div>
                    
                    </div>
                    
                    <!--div class="note-box mb-4">
                    Note that Sigma Estimate is still under beta testing. There might be inaccuracy or inconsistency in our estimated value. Please use this information only as a starting point for property valuation.
                    </div-->
                    <br>
                    <button type="submit" class="btn btn-main w-100" id="get-estimate-btn">Get Estimate</button>
                    <div id="estimate-result" class="estimate-result" role="status" aria-live="polite"></div>
                 </form>    
                    </div>
                </div>
                
                <!-- RIGHT CONTACT -->
                <div class="col-lg-4">
                <h6>How much is my home worth?</h6>
                   {!! $form->renderForm() !!}
               
  </div>
                
                </div>
            </div>

            <div id="estimate-forsale-section" class="estimate-forsale container-fluid" aria-live="polite" hidden>
                <div class="estimate-forsale__head">
                    <h3 class="estimate-forsale__title">Similar homes for sale nearby</h3>
                    <div class="estimate-forsale__nav">
                        <button type="button" class="estimate-forsale__nav-btn estimate-forsale-prev" aria-label="Previous homes">‹</button>
                        <button type="button" class="estimate-forsale__nav-btn estimate-forsale-next" aria-label="Next homes">›</button>
                    </div>
                </div>
                <div class="swiper estimate-forsale__swiper" id="estimate-forsale-swiper">
                    <div class="swiper-wrapper" id="estimate-forsale-track"></div>
                </div>
            </div>
            
            <!-- ================= HERO ================= -->
<section class="srk-hero py-5 text-center">
  <div class="container-fluid" style="padding:0px !important;">
   <img src="{{ asset('storage/house-worth.webp') }}" style="width:100%;padding:0px !important;" alt="{{ img_alt(null, 'house-worth.webp', __('Free Home Evaluation')) }}" width="1200" height="675" decoding="async" loading="lazy"/>
  </div>
</section>


            
</section>




@if ($renderBoostFormMain)
<section id="formMain">
    <div class="container-fluid min-vh-100 d-flex flex-column">

    <!-- Header -->
    <header class="py-3 border-bottom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ url('/') }}"> <img class="logo_form" src="{{ asset('storage/whatsapp-image-2025-12-09-at-120824-am.jpeg') }}" alt="Serik Realty" height="40" width="158" decoding="async" loading="lazy"></a>
            <a href="tel:16475789400" class="text-decoration-none fw-semibold">
                <i class="bi bi-telephone"></i> 1-(647) 578-9400
            </a>
        </div>
    </header>

    <!-- Progress -->
    <div class="container mt-4">
        <div class="progress-steps d-flex justify-content-between text-center">
            <span class="step active">Plan To Buy</span>
            <span class="step">Journey</span>
            <span class="step">Location</span>
            <span class="step">Income</span>
            <span class="step">Saving</span>
            <span class="step">Credit Score</span>
            <span class="step">Personal Details</span>
        </div>
    </div>

    <!-- Form -->
    <main class="flex-grow-1 d-flex align-items-center">
        <div class="container text-center">

            <!-- STEP 1 -->
            <div class="form-step active">
                <h2 class="mb-4 fw-bold">When do you plan to buy?</h2>

                <div class="option-group">
                    <button class="option-btn">Within next 30 days</button>
                    <button class="option-btn">Within 1–3 months</button>
                    <button class="option-btn">Within 3–6 months</button>
                    <button class="option-btn">Within 6–12 months</button>
                    <button class="option-btn">Over 12 months</button>
                </div>
            </div>

            <!-- STEP 2 -->
            <div class="form-step">
                <br>
                <h2 class="mb-4 fw-bold">Where are you in your journey?</h2>
                <p>This way we can give you the right guidance, whether you're browsing or ready to buy</p>
                <div class="option-group">
                    <button class="option-btn">Just getting started
                    <p class="btn-hov">Getting familiar with the market</p>
                    </button>
                    <button class="option-btn">Ready for showings
                    <p class="btn-hov">I want to start viewing homes</p>
                    </button>
                    <button class="option-btn">Pre-approved
                    <p class="btn-hov">I am ready to view and make offers</p>
                    </button>
                    <button class="option-btn">I'm ready to buy
                    <p class="btn-hov">Found homes, ready to close with a boost.</p>
                    </button>
                    
                </div>
            </div>
            
             <div class="form-step">
                <h2 class="mb-4 fw-bold">Where are you looking to buy a home?</h2>
                <p>Select city to apply</p>
                <div class="option-group">
                    <select class="form-control">
                         <option value="">Select city...</option>
                         <option value="27">Banff</option>
                         <option value="30">Calgory</option>
                         <option value="29">Edmonton</option>
                         <option value="31">Ottawa</option>
                         <option value="8" selected="selected">Toronto</option>
                         <option value="28">Winnipeg</option>
                    </select>
                   
                    
                </div>
            </div>
            
            <div class="form-step">
                <h2 class="mb-4 fw-bold">What is your annual household income?</h2>
                <p>Before taxes, just an estimate, no documents needed to calculate your boost.</p>
                <div class="option-group">
                    <input type="number" placeholder="Annual Income ($)" class="form-control" name="annual-income"/>
                    
                    
                    
                </div>
            </div>
            
            <div class="form-step">
                <h2 class="mb-4 fw-bold">How much have you saved for down payment?</h2>
                <p>Include RRSP, TFSA, FHSA or any savings you plan to use.</p>
                <div class="option-group">
                    <input type="number" placeholder="Household savings ($)" class="form-control" name="annual-income"/>
                    
                    
                    
                </div>
            </div>
            
            
            <div class="form-step">
                <h2 class="mb-4 fw-bold">What range is your credit score in?</h2>
                <p>No credit check, just pick the range that fits you best</p>
                
                <div class="option-group">
                    <select class="form-control">
                        <option>Over 700</option>
                        <option>Between 620 to 700</option>
                        <option>Under 620</option>
                    </select>
                   
                    
                </div>
            </div>
            
            
            <div class="form-step">
                <br>
                <h2 class="mb-4 fw-bold">Finish your profile to unlock your boost</h2>
                <p>We add to your down payment at no cost to you.</p>
               <center>
                   <div style="width:50%;">
                    {!! $form->renderForm() !!}
                </div>
               </center> 
                 
            </div>
            

            <!-- Navigation -->
            <div class="d-flex justify-content-center gap-3 mt-5">
                <button class="btn btn-light px-4" id="prevBtn" disabled>
                    ← Back
                </button>
                <button class="btn btn-primary px-5 btn-color-next" id="nextBtn">
                    Next →
                </button>
            </div>

            <p class="mt-4">
                <a href="#" class="text-decoration-underline fw-semibold">
                    Stuck on the form? Let's call you!
                </a>
            </p>
        </div>
    </main>

</div>

</section>
@endif


<script>
const steps = document.querySelectorAll('.form-step');
const progressSteps = document.querySelectorAll('.progress-steps .step');
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');

let currentStep = 0;

function updateStep() {
    if (!nextBtn || !prevBtn) {
        return;
    }
    steps.forEach((step, index) => {
        step.classList.toggle('active', index === currentStep);
        progressSteps[index]?.classList.toggle('active', index <= currentStep);
    });

    prevBtn.disabled = currentStep === 0;
    nextBtn.textContent = currentStep === steps.length - 1 ? 'Submit →' : 'Next →';
   
    if(currentStep == steps.length-1){
        nextBtn.style.display = 'none';
        prevBtn.style.display = 'none';
    }
}

if (nextBtn && prevBtn) {
    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateStep();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateStep();
        }
    });

    // Option selection
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.option-group')
                .querySelectorAll('.option-btn')
                .forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
}




function isHomePage() {
    return window.location.pathname === '/faqs';
}

function isAboutUsPage() {
    return window.location.pathname === '/about-us';
}

function isEstimatePage() {
    const path = (window.location.pathname || '').replace(/\/+$/, '') || '/';
    return path === '/free-home-evaluation' || path === '/evaluation';
}

function isFeedbackePage() {
    return window.location.pathname === '/feedback';
}

function serikHideEl(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'none';
    }
}

if (isAboutUsPage() || isFeedbackePage() ) {
    serikHideEl('formMain');
    serikHideEl('thirdMain');
    serikHideEl('contactMain');
} else if (isHomePage()) {
    serikHideEl('secondMain');
    serikHideEl('formMain');
    serikHideEl('thirdMain');
} else if (isEstimatePage()) {
    serikHideEl('secondMain');
    serikHideEl('formMain');
    serikHideEl('contactMain');
} else {
    serikHideEl('secondMain');
    serikHideEl('thirdMain');
    serikHideEl('contactMain');
}

// After showing/hiding contact sections, load CAPTCHA once then render visible widgets.
if (typeof window.loadRecaptcha === 'function') {
    window.loadRecaptcha().then(function () {
        if (typeof window.initSerikRecaptcha === 'function') {
            window.initSerikRecaptcha();
        }
    }).catch(function () {});
} else if (typeof window.initSerikRecaptcha === 'function') {
    window.initSerikRecaptcha();
}


document.querySelectorAll('.counter').forEach(function(counter) {
    const input = counter.querySelector('input');
    const btnMinus = counter.querySelector('button:first-child');
    const btnPlus = counter.querySelector('button:last-child');
    if (!input || !btnMinus || !btnPlus) {
        return;
    }

    btnMinus.addEventListener('click', function(e) {
        e.preventDefault();
        let value = parseInt(input.value) || 0;
        if (value > 0) { // Prevent negative numbers
            input.value = value - 1;
        }
    });

    btnPlus.addEventListener('click', function(e) {
        e.preventDefault();
        let value = parseInt(input.value) || 0;
        input.value = value + 1;
    });
});

function serikInitHomeEvalAddressSuggest() {

    /**
     * Format MLS living-area ranges for display only.
     * "1100-1500" → "1,100 - 1,500 sq ft"
     */
    function formatSquareFootageDisplay(value) {
        const raw = String(value ?? '').trim();
        if (raw === '' || raw === '0') {
            return raw;
        }
        const withoutUnit = raw.replace(/\s*sq\.?\s*ft\.?/gi, '').trim();
        const parts = withoutUnit.split(/\s*[-–—]\s*/).map((part) => part.replace(/,/g, '').trim()).filter(Boolean);
        if (parts.length === 0) {
            return raw;
        }
        const formatted = parts.map((part) => {
            const n = Number(part);
            return Number.isFinite(n) ? n.toLocaleString('en-US') : part;
        });
        if (formatted.length >= 2) {
            return formatted[0] + ' - ' + formatted[1] + ' sq ft';
        }
        return Number.isFinite(Number(parts[0])) ? (formatted[0] + ' sq ft') : raw;
    }
    window.serikFormatSquareFootage = formatSquareFootageDisplay;

    const input = document.getElementById("prop-address");
    const suggestionBox = document.getElementById("address-suggestions");
    const form = document.getElementById("prop-name");
    const resultBox = document.getElementById("estimate-result");
    const estimateBtn = document.getElementById("get-estimate-btn");
    const sqftInput = document.getElementById("sqft");
    const forSaleSection = document.getElementById("estimate-forsale-section");
    const forSaleTrack = document.getElementById("estimate-forsale-track");
    let forSaleSwiper = null;

    if (!input || !suggestionBox) {
        return;
    }

    function clearForSaleCarousel() {
        if (forSaleSwiper) {
            try { forSaleSwiper.destroy(true, true); } catch (e) {}
            forSaleSwiper = null;
        }
        if (forSaleTrack) {
            forSaleTrack.innerHTML = '';
        }
        if (forSaleSection) {
            forSaleSection.classList.remove('is-visible');
            forSaleSection.hidden = true;
        }
    }

    function renderForSaleCarousel(listings) {
        clearForSaleCarousel();
        const forSale = Array.isArray(listings) ? listings : [];
        if (!forSaleSection || !forSaleTrack || !forSale.length) {
            return;
        }

        forSaleTrack.innerHTML = forSale.slice(0, 12).map((p) => {
            const href = escapeHtml(p.url || '#');
            const img = escapeHtml(p.image || '');
            const addr = escapeHtml(p.address || '');
            const price = p.price != null ? money(p.price) : '—';
            const beds = p.beds != null ? escapeHtml(p.beds) : '—';
            const baths = p.baths != null ? escapeHtml(p.baths) : '—';
            const sqft = p.sqft != null ? escapeHtml(p.sqft) + ' sqft' : '';
            const dist = p.distance_km != null ? escapeHtml(p.distance_km) + ' km' : '';
            const meta = [beds + ' bed', baths + ' bath', sqft, dist].filter(Boolean).join(' · ');
            const status = escapeHtml(p.status || 'For Sale');
            return `<div class="swiper-slide">
                <a class="estimate-forsale__card js-property-modal-link" href="${href}">
                    <div class="estimate-forsale__media">
                        ${img ? `<img src="${img}" alt="" loading="lazy" width="400" height="250">` : ''}
                        <span class="estimate-forsale__badge">${status}</span>
                    </div>
                    <div class="estimate-forsale__body">
                        <p class="estimate-forsale__price">${price}</p>
                        <p class="estimate-forsale__addr">${addr}</p>
                        <p class="estimate-forsale__meta">${meta}</p>
                    </div>
                </a>
            </div>`;
        }).join('');

        forSaleSection.hidden = false;
        forSaleSection.classList.add('is-visible');

        if (typeof Swiper === 'function') {
            forSaleSwiper = new Swiper('#estimate-forsale-swiper', {
                slidesPerView: 1.15,
                spaceBetween: 14,
                watchOverflow: true,
                navigation: {
                    nextEl: '#estimate-forsale-section .estimate-forsale-next',
                    prevEl: '#estimate-forsale-section .estimate-forsale-prev',
                },
                breakpoints: {
                    576: { slidesPerView: 2.2, spaceBetween: 16 },
                    768: { slidesPerView: 3, spaceBetween: 18 },
                    1200: { slidesPerView: 3, spaceBetween: 20 },
                    1400: { slidesPerView: 4, spaceBetween: 20 },
                },
            });
        }
    }

    if (sqftInput && sqftInput.value) {
        sqftInput.value = formatSquareFootageDisplay(sqftInput.value);
    }
    if (sqftInput) {
        sqftInput.addEventListener('blur', function () {
            this.value = formatSquareFootageDisplay(this.value);
        });
    }

    let debounceTimer;
    let results = [];
    let selectedListing = null;
    let suggestAbort = null;
    let suggestSeq = 0;
    let ambiguousSuggestions = [];
    let enrichAbort = null;

    function money(value) {
        return new Intl.NumberFormat('en-CA', {
            style: 'currency',
            currency: 'CAD',
            maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function matchPropertyType(value) {
        const propertyType = document.getElementById("property-type");
        if (!propertyType) {
            return;
        }
        const want = String(value || '').trim().toLowerCase();
        if (!want) {
            propertyType.value = '';
            return;
        }
        for (const option of propertyType.options) {
            const opt = option.value.trim().toLowerCase();
            if (!opt) continue;
            if (opt === want || opt.replace(/\s+/g, '') === want.replace(/\s+/g, '') || want.indexOf(opt) === 0 || opt.indexOf(want) === 0) {
                propertyType.value = option.value;
                return;
            }
        }
        // Unknown MLS subtype — keep blank rather than a wrong default.
        propertyType.value = '';
    }

    function setFieldValue(id, value, { allowZero = true } = {}) {
        const el = document.getElementById(id);
        if (!el) return;
        if (value === null || value === undefined || value === '') {
            el.value = '';
            return;
        }
        if (!allowZero && (value === 0 || value === '0')) {
            el.value = '';
            return;
        }
        el.value = String(value);
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[ch]));
    }

    let lastDomBaseline = 45;
    let lastDomEstimate = 0;

    function renderListingHistory(history, address) {
        const rows = Array.isArray(history) ? history : [];
        if (!rows.length) {
            return `<div class="estimate-history"><h4>Listing History</h4><p class="estimate-history__empty">No prior MLS events found for this address yet.</p></div>`;
        }
        const sub = address
            ? `<p class="estimate-history__sub">Buy/sell/lease history for <strong>${escapeHtml(address)}</strong> only (this unit)</p>`
            : '';
        const body = rows.map((h) => {
            const start = h.date_start || h.date || '—';
            const end = h.date_end || '—';
            const event = h.event || h.status || 'Update';
            const listingId = h.listing_id || h.mls || '—';
            const priceLabel = h.price != null ? money(h.price) : '—';
            return `<tr>
                <td>${escapeHtml(start)}</td>
                <td>${escapeHtml(end)}</td>
                <td class="eh-price">${priceLabel}</td>
                <td class="eh-event">${escapeHtml(event)}</td>
                <td>${escapeHtml(listingId)}</td>
            </tr>`;
        }).join('');
        return `<div class="estimate-history">
            <h4>Listing History</h4>
            ${sub}
            <table class="estimate-history__table">
                <thead><tr><th>Date Start</th><th>Date End</th><th>Price</th><th>Event</th><th>Listing ID</th></tr></thead>
                <tbody>${body}</tbody>
            </table>
        </div>`;
    }

    function predictDomDays(listPrice) {
        const base = Number(lastDomBaseline) || 45;
        const mid = Number(lastDomEstimate) || 0;
        if (!mid || !listPrice || listPrice <= 0) {
            return base;
        }
        const ratio = listPrice / mid;
        const days = Math.round(base * Math.pow(ratio, 2.4));
        return Math.max(5, Math.min(180, days));
    }

    function domPinPercent(days) {
        // Map days onto Fast(7) → 20 → 60 → Very Slow(120)
        const marks = [7, 20, 60, 120];
        const d = Math.max(marks[0], Math.min(marks[3], Number(days) || marks[1]));
        if (d <= marks[1]) {
            return ((d - marks[0]) / (marks[1] - marks[0])) * 33.3;
        }
        if (d <= marks[2]) {
            return 33.3 + ((d - marks[1]) / (marks[2] - marks[1])) * 33.3;
        }
        return 66.6 + ((d - marks[2]) / (marks[3] - marks[2])) * 33.4;
    }

    function updateDomGauge(listPrice) {
        const pin = resultBox && resultBox.querySelector('.estimate-dom__pin');
        const daysLabel = resultBox && resultBox.querySelector('[data-dom-days]');
        if (!pin) {
            return;
        }
        const days = predictDomDays(listPrice);
        pin.style.left = `${domPinPercent(days).toFixed(2)}%`;
        if (daysLabel) {
            daysLabel.textContent = `${days} days`;
        }
    }

    function showEstimate(payload) {
        if (!resultBox) {
            return;
        }
        resultBox.classList.add('is-visible');
        if (!payload || !payload.ok) {
            clearForSaleCarousel();
            resultBox.classList.add('is-error');
            ambiguousSuggestions = Array.isArray(payload && payload.suggestions) ? payload.suggestions : [];
            const suggestionHtml = ambiguousSuggestions.length
                ? `<ul class="estimate-pick-list">${ambiguousSuggestions.map((s, idx) => {
                    const addr = String(s.address || '');
                    return `<li><button type="button" class="estimate-pick-btn" data-pick-index="${idx}">${escapeHtml(addr)}</button></li>`;
                }).join('')}</ul>`
                : '';
            const history = Array.isArray(payload && payload.listing_history) ? payload.listing_history : [];
            const historyHtml = renderListingHistory(history, payload && payload.address);
            resultBox.innerHTML = `<p class="estimate-note">${escapeHtml((payload && payload.message) || 'No estimate available yet.')}</p>${suggestionHtml}${historyHtml}`;
            return;
        }
        ambiguousSuggestions = [];
        resultBox.classList.remove('is-error');
        const isAsking = payload.basis === 'asking_price_indication';
        const history = Array.isArray(payload.listing_history) ? payload.listing_history : [];
        const basisLabel = isAsking ? 'Active asking prices (not sold)' : 'Verified sold comparables';
        const mid = Number(payload.estimate) || 0;
        const low = Number(payload.low) || mid;
        const high = Number(payload.high) || mid;
        const dom = payload.days_on_market || {};
        lastDomEstimate = mid;
        lastDomBaseline = Number(dom.baseline_days || dom.predicted_days || 45);
        const predicted = Number(dom.predicted_days || lastDomBaseline);
        const pinLeft = domPinPercent(predicted).toFixed(2);

        const historyHtml = renderListingHistory(history, payload.address || (payload.subject && payload.subject.address));

        resultBox.innerHTML = `
            <span class="estimate-basis ${isAsking ? 'is-asking' : ''}">${escapeHtml(basisLabel)}</span>
            <div class="estimate-scale" aria-label="Estimated value range">
                <div class="estimate-scale__rail" aria-hidden="true"></div>
                <div class="estimate-scale__marks">
                    <div class="estimate-scale__mark is-low">
                        <span class="estimate-scale__label">Low</span>
                        <span class="estimate-scale__value">${money(low)}</span>
                    </div>
                    <div class="estimate-scale__mark is-mid">
                        <span class="estimate-scale__label">Mid</span>
                        <span class="estimate-scale__value">${money(mid)}</span>
                    </div>
                    <div class="estimate-scale__mark is-high">
                        <span class="estimate-scale__label">High</span>
                        <span class="estimate-scale__value">${money(high)}</span>
                    </div>
                </div>
            </div>
            <div class="estimate-dom">
                <div class="estimate-dom__title">
                    <span>Predicted Days on Market if Listed at</span>
                    <div class="estimate-dom__controls">
                        <input type="text" class="estimate-dom__input" id="estimate-dom-price" value="${escapeHtml(money(mid))}" inputmode="numeric" aria-label="List price for days on market">
                        <strong data-dom-days>${predicted} days</strong>
                    </div>
                </div>
                <div class="estimate-dom__track-wrap">
                    <div class="estimate-dom__pin" style="left:${pinLeft}%;" aria-hidden="true"></div>
                    <div class="estimate-dom__track"></div>
                    <div class="estimate-dom__labels">
                        <span>Fast</span>
                        <span>20 days</span>
                        <span>60 days</span>
                        <span>Very Slow</span>
                    </div>
                </div>
            </div>
            <div class="estimate-beta">${escapeHtml(payload.disclaimer || 'This automated estimate is a starting point only — not a certified appraisal.')}</div>
            ${historyHtml}
        `;

        const domInput = resultBox.querySelector('#estimate-dom-price');
        if (domInput) {
            domInput.addEventListener('input', function () {
                const raw = Number(String(this.value || '').replace(/[^0-9.]/g, '')) || 0;
                updateDomGauge(raw);
            });
            domInput.addEventListener('blur', function () {
                const raw = Number(String(this.value || '').replace(/[^0-9.]/g, '')) || mid;
                this.value = money(Math.round(raw));
                updateDomGauge(raw);
            });
        }

        renderForSaleCarousel(payload.for_sale);
        resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    let lastSuggestKeyword = '';

    function normalizeAddr(value) {
        return String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function applySuggestionToForm(data, { keepAddress = true } = {}) {
        if (!data) {
            return;
        }
        selectedListing = data;
        if (keepAddress) {
            setFieldValue('prop-address', data.UnparsedAddress || '');
        }
        setFieldValue('bedrooms', data.BedroomsAboveGrade, { allowZero: true });
        setFieldValue('bedrooms-below', data.BedroomsBelowGrade, { allowZero: true });
        setFieldValue('bathrooms', data.BathroomsTotalInteger, { allowZero: true });
        setFieldValue('garage', data.ParkingTotal, { allowZero: true });
        const sqftRaw = data.LivingAreaRange;
        setFieldValue('sqft', sqftRaw ? formatSquareFootageDisplay(sqftRaw) : '');
        setFieldValue('tax', data.TaxAnnualAmount, { allowZero: false });
        matchPropertyType(data.PropertySubType);
        setFieldValue('lot-width', data.LotWidth, { allowZero: false });
        setFieldValue('lot-depth', data.LotDepth, { allowZero: false });
    }

    function enrichSelectedListing(data) {
        const listingKey = String((data && data.ListingKey) || '').trim();
        if (!listingKey) {
            return;
        }
        if (enrichAbort) {
            enrichAbort.abort();
        }
        enrichAbort = new AbortController();
        const selectedKey = listingKey;
        fetch(`/api/v1/propertiesName?keyword=${encodeURIComponent(listingKey)}`, { signal: enrichAbort.signal })
            .then((res) => res.json())
            .then((list) => {
                if (!selectedListing || String(selectedListing.ListingKey || '') !== selectedKey) {
                    return;
                }
                const rich = Array.isArray(list) && list[0] ? list[0] : null;
                if (!rich) {
                    return;
                }
                applySuggestionToForm({
                    ...data,
                    ...rich,
                    // Prefer non-empty facts; never wipe local beds/sqft with AMP nulls.
                    BedroomsAboveGrade: (rich.BedroomsAboveGrade != null && rich.BedroomsAboveGrade !== '')
                        ? rich.BedroomsAboveGrade : data.BedroomsAboveGrade,
                    BedroomsBelowGrade: rich.BedroomsBelowGrade ?? data.BedroomsBelowGrade,
                    BathroomsTotalInteger: (rich.BathroomsTotalInteger != null && rich.BathroomsTotalInteger !== '')
                        ? rich.BathroomsTotalInteger : data.BathroomsTotalInteger,
                    ParkingTotal: (rich.ParkingTotal != null && rich.ParkingTotal !== '')
                        ? rich.ParkingTotal : data.ParkingTotal,
                    LivingAreaRange: rich.LivingAreaRange || data.LivingAreaRange,
                    PropertySubType: rich.PropertySubType || data.PropertySubType,
                    TaxAnnualAmount: rich.TaxAnnualAmount ?? data.TaxAnnualAmount,
                    LotWidth: rich.LotWidth ?? data.LotWidth,
                    LotDepth: rich.LotDepth ?? data.LotDepth,
                    UnparsedAddress: data.UnparsedAddress || rich.UnparsedAddress,
                });
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') {
                    return;
                }
            });
    }

    function selectSuggestion(data) {
        if (!data) {
            return;
        }
        clearTimeout(debounceTimer);
        if (suggestAbort) {
            suggestAbort.abort();
        }
        suggestSeq += 1;
        applySuggestionToForm(data);
        suggestionBox.innerHTML = "";
        results = [data];
        lastSuggestKeyword = String(data.UnparsedAddress || '').trim();
        enrichSelectedListing(data);
    }

    function tryAutofillExactMatch(list) {
        const typed = normalizeAddr(input.value);
        if (!typed || !Array.isArray(list) || !list.length) {
            return;
        }
        const exact = list.filter((item) => normalizeAddr(item.UnparsedAddress) === typed);
        if (exact.length === 1) {
            selectSuggestion(exact[0]);
        }
    }

    input.addEventListener("input", function () {
        const keyword = this.value.trim();
        // Keep autofilled facts while the selected address text is unchanged.
        if (selectedListing && normalizeAddr(keyword) === normalizeAddr(selectedListing.UnparsedAddress)) {
            return;
        }
        selectedListing = null;
        clearTimeout(debounceTimer);
        if (keyword.length < 1) {
            suggestionBox.innerHTML = "";
            results = [];
            lastSuggestKeyword = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            if (keyword === lastSuggestKeyword && suggestionBox.childElementCount > 0) {
                return;
            }
            lastSuggestKeyword = keyword;
            if (suggestAbort) {
                suggestAbort.abort();
            }
            suggestAbort = new AbortController();
            const seq = ++suggestSeq;
            suggestionBox.innerHTML = '<div class="list-group-item text-muted">Searching addresses…</div>';
            fetch(`/api/v1/propertiesName?keyword=${encodeURIComponent(keyword)}`, { signal: suggestAbort.signal })
                .then(async (res) => {
                    const data = await res.json().catch(() => null);
                    if (!res.ok) {
                        throw new Error(res.status === 429 ? 'Too many searches — wait a moment.' : 'Address search failed.');
                    }
                    return data;
                })
                .then(data => {
                    if (seq !== suggestSeq) {
                        return;
                    }
                    results = Array.isArray(data) ? data : [];
                    suggestionBox.innerHTML = "";
                    if (!results.length) {
                        suggestionBox.innerHTML = '<div class="list-group-item">No matching listings</div>';
                        return;
                    }
                    results.forEach((item, index) => {
                        const div = document.createElement("div");
                        div.className = "list-group-item suggestion-item";
                        div.textContent = item.UnparsedAddress || '';
                        div.dataset.index = String(index);
                        suggestionBox.appendChild(div);
                    });
                    // Exact single match (paste / full typed address) — fill form immediately.
                    tryAutofillExactMatch(results);
                })
                .catch((err) => {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    if (seq !== suggestSeq) {
                        return;
                    }
                    results = [];
                    suggestionBox.innerHTML = '<div class="list-group-item text-danger">'
                        + (err && err.message ? err.message : 'Address search failed. Try again.')
                        + '</div>';
                });
        }, 120);
    });

    // mousedown fires before blur/input races that used to drop the click selection.
    suggestionBox.addEventListener("mousedown", function (e) {
        const item = e.target.closest('.suggestion-item');
        if (!item) {
            return;
        }
        e.preventDefault();
        const data = results[item.dataset.index];
        selectSuggestion(data);
    });

    input.addEventListener('blur', function () {
        setTimeout(() => {
            if (selectedListing) {
                return;
            }
            tryAutofillExactMatch(results);
        }, 180);
    });

    function runEstimate(addressOverride) {
        const address = String(addressOverride || input.value || '').trim();
        if (address.length < 3) {
            showEstimate({ ok: false, message: 'Please enter a property address.' });
            return;
        }
        if (addressOverride) {
            input.value = address;
        }
        if (estimateBtn) {
            estimateBtn.disabled = true;
            estimateBtn.textContent = 'Calculating...';
        }
        clearForSaleCarousel();
        if (resultBox) {
            resultBox.classList.add('is-visible');
            resultBox.classList.remove('is-error');
            resultBox.innerHTML = '<p class="estimate-note">Calculating your home value...</p>';
        }
        const params = new URLSearchParams({
            address: address,
            bedrooms: document.getElementById("bedrooms")?.value || '0',
            bathrooms: document.getElementById("bathrooms")?.value || '0',
            sqft: document.getElementById("sqft")?.value || '',
            property_type: document.getElementById("property-type")?.value || '',
            // Skip Groq on the critical path — numbers render faster; explanation is optional.
            include_ai: '0'
        });
        fetch(`/api/v1/home-evaluation?${params.toString()}`)
            .then(async (res) => {
                const data = await res.json().catch(() => null);
                if (res.status === 429) {
                    return { ok: false, message: 'Too many evaluation requests. Please wait a minute and try again.' };
                }
                if (res.status === 422) {
                    const first = data && data.errors ? Object.values(data.errors)[0] : null;
                    return { ok: false, message: Array.isArray(first) ? first[0] : 'Please check the address and details.' };
                }
                return data || { ok: false, message: 'We could not calculate an estimate right now. Please try again.' };
            })
            .then(showEstimate)
            .catch(() => showEstimate({ ok: false, message: 'We could not calculate an estimate right now. Please try again.' }))
            .finally(() => {
                if (estimateBtn) {
                    estimateBtn.disabled = false;
                    estimateBtn.textContent = 'Get Estimate';
                }
            });
    }

    if (resultBox) {
        resultBox.addEventListener('click', function (e) {
            const btn = e.target.closest('.estimate-pick-btn');
            if (!btn) {
                return;
            }
            const idx = Number(btn.getAttribute('data-pick-index'));
            const picked = ambiguousSuggestions[idx];
            const addr = picked && picked.address ? String(picked.address).trim() : btn.textContent.trim();
            if (!addr) {
                return;
            }
            selectedListing = null;
            suggestionBox.innerHTML = '';
            runEstimate(addr);
        });
    }

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            runEstimate();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', serikInitHomeEvalAddressSuggest);
} else {
    serikInitHomeEvalAddressSuggest();
}

// FAQ toggle (namespaced)
document.querySelectorAll(".srk-faq-q").forEach(q => {
  q.addEventListener("click", () => {
    const a = q.nextElementSibling;
    a.style.display = (a.style.display === "block") ? "none" : "block";
  });
});
</script>
<script>
function cleanNbsp() {
    const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );

    let node;
    while (node = walker.nextNode()) {
        node.nodeValue = node.nodeValue
            .replace(/\u00a0/g, ' ')
            .replace(/ +/g, ' ');
    }
}

window.addEventListener("load", function () {
    setTimeout(cleanNbsp, 500);
});
</script>