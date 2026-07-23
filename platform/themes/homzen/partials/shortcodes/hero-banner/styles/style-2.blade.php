<style>
    
    .cashback-calculator{
    margin-top:-20px;
    z-index:200;
    background:#f3f3f3;
    padding:30px 0;
}

.calculator-box{
    box-shadow:5px 5px 15px 2px #ccc;
    border-radius:12px;
    padding:16px 18px;
    background:#fff;
}


.primary-outline{
    background:#fff;
    border:1px solid #ddd;
    color:#333;
}

.primary-outline:hover{
    background:#f5f5f5;
}

.calculator-result{
    padding:10px;
    box-shadow:5px 5px 15px 2px #ccc;
    display:none;
    border-radius:12px;
    margin-top:20px;
    max-width:400px;
}



.calculator-buttons{
    display:flex;
    gap:8px;
    align-items:center;
    flex-shrink:0;
}

.calculator-buttons .tf-btn{
    flex:1;                 /* all buttons equal width */
    display:flex;
    justify-content:center;
    align-items:center;
    padding:5px 3px;
    text-align:center;
    height:50px;
}

.calculator-buttons .tf-btn:last-child{
    flex:1.2;
    padding:0;
    border:none;
    background:none;
}

/* Image fit */
.calculator-buttons .tf-btn:last-child img{
    width:100%;
    height:100%;
    object-fit:contain;
}



/* Desktop: field + buttons in one row */
.cashback-calculator .wd-find-select.calculator-box{
    display:flex;
    flex-direction:row;
    align-items:flex-end;
    gap:12px;
}

.cashback-calculator .wd-find-select .inner-group{
    flex:1 1 46%;
    min-width:160px;
    max-width:46%;
    padding:0 !important;
    display:block !important;
}

.cashback-calculator .wd-find-select .form-style,
.cashback-calculator .wd-find-select .form-group-1{
    width:100%;
    border-inline-end:none !important;
    margin:0;
}

.cashback-calculator .wd-find-select .form-style label,
.cashback-calculator .wd-find-select .form-group-1 label{
    display:block;
    margin-bottom:6px;
    font-size:13px;
    font-weight:600;
    color:#334155;
    line-height:1.2;
}

.cashback-calculator .wd-find-select .position-relative{
    width:100%;
}

.cashback-calculator .wd-find-select .form-control#amount{
    display:block;
    width:100% !important;
    min-width:0;
    min-height:50px;
    height:50px;
    padding:12px 14px !important;
    padding-inline-end:14px !important;
    border:1px solid #cbd5e1 !important;
    border-radius:8px;
    background:#fff !important;
    font-size:15px !important;
    font-weight:600 !important;
    line-height:1.35 !important;
    color:#0f172a !important;
    box-sizing:border-box;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.cashback-calculator .wd-find-select .form-control#amount:focus{
    border-color:#0255a1 !important;
    outline:none;
    box-shadow:0 0 0 3px rgba(2,85,161,0.12);
    white-space:normal;
}

.cashback-calculator .wd-find-select .form-control#amount::placeholder{
    color:#64748b !important;
    opacity:1 !important;
    font-size:14px !important;
    font-weight:500 !important;
}

.cashback-calculator .calculator-buttons{
    flex:0 0 54%;
    max-width:54%;
    width:54%;
    min-width:0;
    padding-bottom:2px;
    align-items:stretch;
}

.cashback-calculator .calculator-buttons button,
.cashback-calculator .calculator-buttons a{
    flex:1 1 0;
    min-width:0;
    min-height:54px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.cashback-calculator .calculator-buttons img{
    display:block;
    width:100%;
    height:54px;
    min-height:54px;
    object-fit:contain;
    object-position:center;
}

@media (min-width: 992px) {
    .cashback-calculator .wd-find-select .inner-group{
        flex:1 1 38%;
        max-width:38%;
    }

    .cashback-calculator .calculator-buttons{
        flex:0 0 62%;
        max-width:62%;
        width:62%;
        gap:10px;
    }

    .cashback-calculator .calculator-buttons button,
    .cashback-calculator .calculator-buttons a{
        min-height:72px;
    }

    .cashback-calculator .calculator-buttons img{
        height:72px;
        min-height:72px;
    }
}

@media (min-width: 1200px) {
    .cashback-calculator .calculator-buttons button,
    .cashback-calculator .calculator-buttons a{
        min-height:80px;
    }

    .cashback-calculator .calculator-buttons img{
        height:80px;
        min-height:80px;
    }
}

/* Mobile: field and buttons on separate lines */
@media (max-width:768px){

    .cashback-calculator{
        padding:20px 10px;
    }

    .calculator-box{
        padding:14px 14px;
    }

    .cashback-calculator .wd-find-select.calculator-box{
        flex-direction:column;
        align-items:stretch;
        gap:12px;
    }

    .cashback-calculator .wd-find-select .inner-group{
        width:100%;
        max-width:100%;
        flex:none;
    }

    .cashback-calculator .wd-find-select .form-control#amount{
        font-size:16px !important;
        min-height:48px;
        height:48px;
        white-space:normal;
    }

    .cashback-calculator .wd-find-select .form-control#amount::placeholder{
        font-size:15px !important;
    }

    .cashback-calculator .calculator-buttons{
        width:100%;
        max-width:100%;
        flex:none;
        flex-direction:row;
        gap:6px;
        padding-bottom:0;
    }

    .cashback-calculator .calculator-buttons img{
        height:50px;
        min-height:50px;
    }
}

.title1 {
    font-size: 80px;
}


    
.flat-section{padding:10px 0 !important;}
.flat-section-v2{padding-top:20px !important;}
.flat-section-v3{padding:20px 0 !important;}
.flat-section-v4{padding:15px 0 !important;}
.flat-section-v5{padding:30px 0 20px !important;}
.flat-section-v6{padding:20px 0 20px !important;}


.btn-wrapper {
    position: relative;
    width:100%;
    display: inline-block;
}
@media (max-width: 768px) {
    .title1 {
        font-size: 28px !important;
        line-height: 1.2;
    }
    .flat-section{padding:10px 0 !important;}
.flat-section-v2{padding-top:10px !important;}
.flat-section-v3{padding:10px 0 !important;}
.flat-section-v4{padding:10px 0 !important;}
.flat-section-v5{padding:20px 0 10px !important;}
.flat-section-v6{padding:10px 0 20px !important;}
}

@media (max-width: 768px) {
   
    .btn-wrapper {
    width:100%;
   }
   .flat-slider.home-2 .slider-content {
        padding: 0px 0;
    }
   .flat-slider.home-2 .slider-content .heading .subtitle{
       margin-bottom: 10px;
   }
   .flat-slider.home-2 .slider-content .heading h1.hero-banner-headline {
       margin: 0;
   }
   .calculator-box{
       padding: 14px 14px;
   }
   .swiper{
           overflow: visible !important;
   }
}

.calculator-buttons {
    display: flex;
    gap: 5px; /* space between buttons */
    align-items: stretch;
}

.calculator-buttons button,
.calculator-buttons a {
    flex: 1 1 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
}

.calculator-buttons img {
    width: 100%;
    max-width: 100%;
    height: 54px;
    min-height: 54px;
    object-fit: contain;
    object-position: center;
    border-radius: 6px;
}
    
</style>

@php
    $titleColor = $shortcode->title_color ?: '#000000';
    $descriptionColor = $shortcode->description_color ?: '#000000';
@endphp

@php
    use App\Support\ImageAlt;

    $heroAltContext = trim(strip_tags((string) ($shortcode->title ?: $shortcode->subtitle ?: __('Ontario homes for sale'))));
@endphp

<section class="flat-slider home-2">
    <div class="container relative">
        <div class="row">
            <div class="col-xl-10">
                <div class="slider-content">
                    <div class="heading">
                        <h1 class="subtitle body-1 hero-banner-headline wow fadeIn" style="color: {{ $descriptionColor }} !important; font-weight:700;" data-wow-delay=".8s" data-wow-duration="2000ms">
                            Top Realtor in Ontario - Buy or Sell Homes and Get
                        </h1>
                        <div class="title title1 wow fadeIn animationtext clip" style="color: {{ $titleColor }} !important; font-weight:700; font-size: 35px;" data-wow-delay=".2s" data-wow-duration="2000ms">
                           <div>  {!! Theme::partial('shortcodes.hero-banner.partials.animation-text', compact('shortcode')) !!}
						   </div>
                            <div style="margin-top: 15px;">
                           
                           <p style="color:red;">*Terms and Conditions Apply</p>
							</div>
                        </div>
                        @if ($shortcode->description)
                            <p class="subtitle body-1 wow fadeIn" style="color: {{ $descriptionColor }} !important; font-weight:700;" data-wow-delay=".8s" data-wow-duration="2000ms">
                                {!! BaseHelper::clean($shortcode->description) !!}
                            </p>
                        @endif
                    </div>
                    <br>
                    {!! Theme::partial('shortcodes.hero-banner.partials.action-button', ['shortcode' => $shortcode, 'class' => 'mb-5']) !!}
                   
                </div>
            </div>
        </div>
    </div>

    @if ($shortcode->background_image)
        <div class="img-banner-left">
            {{ RvMedia::image(
                $shortcode->background_image,
                ImageAlt::resolve($shortcode->title, $shortcode->background_image, $heroAltContext),
                lazy: false,
                attributes: ['fetchpriority' => 'high', 'loading' => 'eager']
            ) }}
        </div>
    @endif

    <div class="img-banner-right">
        <div class="swiper slider-sw-home2">
            <div class="swiper-wrapper">
                @php $heroSlideIndex = 0; @endphp
                @foreach (range(1, 4) as $i)
                    @continue(! $shortcode->{"slider_image_$i"})
                    @php $heroSlideIndex++; @endphp

                    <div class="swiper-slide">
                        <div class="slider-home2 img-animation wow">
                            {{ RvMedia::image(
                                $shortcode->{"slider_image_$i"},
                                ImageAlt::resolve($shortcode->title, $shortcode->{"slider_image_$i"}, $heroAltContext),
                                lazy: $heroSlideIndex > 1,
                                attributes: $heroSlideIndex === 1
                                    ? ['fetchpriority' => 'high', 'loading' => 'eager']
                                    : ['loading' => 'lazy']
                            ) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    
</section >
 @if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
					 <div class="flat-tab flat-tab-form cashback-calculator"  id="calculator-buttons" style=" scroll-margin-top: 150px;">
    <div class="tab-content">
        <div class="container relative">
            <div class="row justify-content-center">

                <div class="col-lg-8 col-xl-7 col-md-10 col-12">
                    <div class="tab-pane fade active show" role="tabpanel">

                        <form id="myForm">

                            <div class="wd-find-select calculator-box">

                                <div class="inner-group" >

                                    <div class="form-group-1 form-search-form form-style form-search-keyword-input"
                                         data-bb-toggle="search-suggestion">

                                        <label>{{ __('Purchase Price') }}</label>

                                        <div class="position-relative">
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="{{ __('Enter Home Price') }}"
                                                value="{{ BaseHelper::stringify(request()->query('k')) }}"
                                                id="amount"
                                                required
                                            />

                                            <div data-bb-toggle="data-suggestion"></div>
                                        </div>

                                    </div>

                                </div>

                                <div class="calculator-buttons">

                                    <button type="submit" style="border: none; background: none;"
                                        
                                        onclick="calculatePercentage()">
                                       <img src="https://serik.ca/storage/button-calculate-cashback-1.png" alt="{{ __('Calculate cash back') }}"/>
                                    </button>
                                
                                    <a href="{{ url('/mortgage-calculator') }}">
                                        <img src="https://serik.ca/storage/button-mortgage-calculator-blue-1.png" alt="{{ __('Mortgage Calculator') }}"/>
                                    </a>
                                    
                                    <a href="{{ url('/appointment-scheduler') }}">
                                        <img src="https://serik.ca/storage/button-copy1-2.png" alt="{{ __('Schedule an appointment') }}"/>
                                    </a>
                                
                                </div>

                            </div>

                        </form>

                        <center>
                            <div id="result" class="calculator-result"></div>
                        </center>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

     @endif
     
     
     <script>

var form = document.getElementById("myForm");
function handleForm(event) { event.preventDefault(); } 
form.addEventListener('submit', handleForm);



document.addEventListener('DOMContentLoaded', () => {
    const currencyInput = document.getElementById('amount');

    function formatCurrency(value) {
        let number = value.replace(/[^0-9.]/g, '');
        number = parseFloat(number);

        if (!isNaN(number)) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        }
        return '';
    }

    currencyInput.addEventListener('input', (e) => {
        e.target.value = formatCurrency(e.target.value);
    });
});




  function calculatePercentage() {
    // 🔥 strip $, commas, spaces
    const rawAmount = document
        .getElementById("amount")
        .value
        .replace(/[^0-9.]/g, '');

    const amount = parseFloat(rawAmount);
   
    if (!rawAmount || isNaN(amount)) {
        document.getElementById("result").textContent =
            "Please enter a valid number.";
        return;
    }

   

    const percentage = 1.5;
    const result = (amount * percentage) / 100;

    document.getElementById("result").style.display = 'block';
   document.getElementById("result").innerHTML =
    "Your Cash Back is Upto $" + result.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + "<br> (<span style='color:red;'>*Terms and Conditions Apply</span>)";

    hideResultAfterDelay();
}

  
  
   function hideResultAfterDelay() {
    setTimeout(function () {
        document.getElementById("amount").value=null;
         document.getElementById("location").value =null;
      document.getElementById("result").style.display = "none";
    }, 30000); // 30 seconds
  }
  
  
  
  

  
</script>