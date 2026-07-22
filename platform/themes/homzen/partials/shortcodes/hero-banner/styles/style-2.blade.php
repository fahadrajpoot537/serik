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
    padding:0px 20px;
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
    gap:5px;
    align-items:center;
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



/* make sure container doesn't force vertical stack */
.wd-find-select{
    display:flex;
    align-items:center;
    gap:20px;
}

.wd-find-select .inner-group{
    padding: 10px 30px 10px 20px;
    flex:1;
}

/* Mobile */
@media (max-width:768px){

    .cashback-calculator{
        padding:20px 10px;
    }

    .wd-find-select{
        flex-direction:column;
        align-items:stretch;
    }
.wd-find-select{
       gap:0px;
    }
    .wd-find-select .inner-group{
        padding: 0px 30px 00px 20px;
        width:100%;
    }

    .calculator-buttons{
        display:flex;
        flex-direction:column;
        width:100%;
        gap:10px;
    }

    .calculator-buttons .tf-btn{
        width:100%;
        padding:15px 0px !important;
        display:block;
        text-align:center;
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
   .calculator-box{
       padding: 20px 0px;
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
    flex: 1; /* equal width */
    display: flex;
    align-items: center;
    justify-content: center;
}


.calculator-buttons img {
   
    height: 50px;
    object-fit: cover;
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
					<p class="subtitle body-1 wow fadeIn" style="color: {{ $descriptionColor }} !important; font-weight:700;" data-wow-delay=".8s" data-wow-duration="2000ms">
                                Top Realtor in Ontario - Buy or Sell Homes and Get
                            </p>
                        <h1 class="title title1 wow fadeIn animationtext clip" style="color: {{ $titleColor }} !important; font-weight:700; font-size: 35px;" data-wow-delay=".2s" data-wow-duration="2000ms">
                           <div>  {!! Theme::partial('shortcodes.hero-banner.partials.animation-text', compact('shortcode')) !!}
						   </div>
                            <div style="margin-top: 15px;">
                           
                           <p style="color:red;">*Terms and Conditions Apply</p>
							</div>
                        </h1>
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
            {{ RvMedia::image($shortcode->background_image, ImageAlt::resolve($shortcode->title, $shortcode->background_image, $heroAltContext)) }}
        </div>
    @endif

    <div class="img-banner-right">
        <div class="swiper slider-sw-home2">
            <div class="swiper-wrapper">
                @foreach (range(1, 4) as $i)
                    @continue(! $shortcode->{"slider_image_$i"})

                    <div class="swiper-slide">
                        <div class="slider-home2 img-animation wow">
                            {{ RvMedia::image($shortcode->{"slider_image_$i"}, ImageAlt::resolve($shortcode->title, $shortcode->{"slider_image_$i"}, $heroAltContext)) }}
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

                <div class="col-lg-6 col-md-8 col-12">
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

                                <div class="calculator-buttons" style="zoom:0.8">

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