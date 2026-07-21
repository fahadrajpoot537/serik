@php
    $backgroundImage = $shortcode->background_image ? RvMedia::getImageUrl($shortcode->background_image) : null;
@endphp

<style>
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





#thirdMain{
    zoom:0.8;
   
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



#address-suggestions {
    position: absolute;
    width: 800px;
    z-index: 9999;
    max-height: 250px;
    overflow-y: auto;
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

<section id="contactMain"
    class="flat-section-v3 flat-slider-contact"
   style="background-image: url('https://serik.ca/storage/f27763e877bd758b84a315e1.jpg') !important"
>
    <div class="container">
        <h1 class="srk-privacy-title text-center mb-4">{{ __('Frequently Asked Questions') }}</h1>
        <div class="row content-wrap">
            <div class="col-lg-7">
                <div class="content-left" style="padding-right: 100px;">
                    <img src="https://serik.ca/storage/faqs.jpg" alt="{{ __('Serik Realty FAQs') }}" style="height:100%">
                    <div class="tf-faq" style="zoom:0.8;">
                        <ul class="box-faq" id="wrapper-faq">
                            @foreach($faqs as $index => $faq)
                                @php $faqId = "faq-{$index}" @endphp
                                <li class="faq-item" style="background-color: #fff;">
                                    <a href="#{{ $faqId }}" class="faq-header collapsed" data-bs-toggle="collapse" aria-expanded="false" aria-controls="{{ $faqId }}">
                                        {!! BaseHelper::clean($faq->question) !!}
                                    </a>
                                    <div id="{{ $faqId }}" class="collapse" data-bs-parent="#wrapper-faq">
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





<section id="thirdMain">
        <div class="container py-5">
  {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode]) !!}
            <div class="row">
            
                <!-- LEFT FORM -->
                <div class="col-lg-8">
                
                    <div class="section-box">
                    
                        <h3 class="mb-3">Home Details</h3>
              <form id="prop-name" >     
                        <label class="mb-2">Please enter your property address</label>
                        <input type="text" name="prop-address" id="prop-address" class="form-control mb-2">
                        <div id="address-suggestions" class="list-group"></div>
                        
                        <div class="row g-3 mb-4">
                        
                        <div class="col-md-3">
                            <label>Bedroom</label>
                            <div class="counter">
                            <button>-</button>
                            <input type="text" id="bedrooms" value="4">
                            <button>+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Partial Bedroom</label>
                            <div class="counter">
                            <button>-</button>
                            <input type="text" id="bedrooms-below" value="0">
                            <button>+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Bathroom</label>
                            <div class="counter">
                            <button>-</button>
                            <input type="text" id="bathrooms" value="4">
                            <button>+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Garage</label>
                            <div class="counter">
                            <button>-</button>
                            <input type="text" id="garage" value="2">
                            <button>+</button>
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
                                <option value="Detached">Detached</option>
                                <option value="Semi-Detached">Semi Detached</option>
                                <option value="Att/Row/Townhouse">Freehold Townhouse</option>
                                <option value="Condo Townhouse">Condo Townhouse</option>
                                <option value="Condo Apartment">Condo Apartment</option>
                                <option value="Duplex">Duplex</option>
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
                    <button class="btn btn-main w-100">Get Estimate</button>
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
            
            
            <!-- ================= HERO ================= -->
<section class="srk-hero py-5 text-center">
  <div class="container-fluid" style="padding:0px !important;">
   <img src="https://serik.ca/storage/house-worth.webp" style="width:100%;padding:0px !important;"/>
  </div>
</section>


            
</section>




<section id="formMain">
    <div class="container-fluid min-vh-100 d-flex flex-column">

    <!-- Header -->
    <header class="py-3 border-bottom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="https://serik.ca"> <img class="logo_form" src="https://serik.ca/storage/whatsapp-image-2025-12-09-at-120824-am.jpeg" alt="Serik Realty" height="40"></a>
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


<script>
const steps = document.querySelectorAll('.form-step');
const progressSteps = document.querySelectorAll('.progress-steps .step');
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');

let currentStep = 0;

function updateStep() {
    steps.forEach((step, index) => {
        step.classList.toggle('active', index === currentStep);
        progressSteps[index]?.classList.toggle('active', index <= currentStep);
    });

    prevBtn.disabled = currentStep === 0;
    nextBtn.textContent = currentStep === steps.length - 1 ? 'Submit →' : 'Next →';
   
    if(currentStep == steps.length-1){
        document.getElementById('nextBtn').style.display = 'none';
        document.getElementById('prevBtn').style.display = 'none';
    }
}

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




function isHomePage() {
    return window.location.pathname === '/faqs';
}

function isAboutUsPage() {
    return window.location.pathname === '/about-us';
}

function isEstimatePage() {
    return window.location.pathname === '/free-home-evaluation' || window.location.pathname === '/evaluation';
}

function isFeedbackePage() {
    return window.location.pathname === '/feedback';
}


if (isAboutUsPage() || isFeedbackePage() ) {
    document.getElementById("formMain").style.display = "none";
    document.getElementById("thirdMain").style.display = "none";
    document.getElementById("contactMain").style.display = "none";
} else if(isHomePage()) {
    document.getElementById("secondMain").style.display = "none";
    document.getElementById("formMain").style.display = "none";
     document.getElementById("thirdMain").style.display = "none";
}else if(isEstimatePage()) {
   // alert("hello");
    document.getElementById("secondMain").style.display = "none";
    document.getElementById("formMain").style.display = "none";
    document.getElementById("contactMain").style.display = "none";
}else{
    document.getElementById("secondMain").style.display = "none";
    document.getElementById("thirdMain").style.display = "none";
     document.getElementById("contactMain").style.display = "none";
    
}


document.querySelectorAll('.counter').forEach(function(counter) {
    const input = counter.querySelector('input');
    const btnMinus = counter.querySelector('button:first-child');
    const btnPlus = counter.querySelector('button:last-child');

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






document.addEventListener("DOMContentLoaded", function () {

    const input = document.getElementById("prop-address");
    const suggestionBox = document.getElementById("address-suggestions");

    let debounceTimer;
    let results = [];

    input.addEventListener("keyup", function () {

        clearTimeout(debounceTimer);

        const keyword = this.value.trim();

        if (keyword.length < 3) {
            suggestionBox.innerHTML = "";
            return;
        }

        debounceTimer = setTimeout(() => {

            fetch(`/api/v1/propertiesName?keyword=${encodeURIComponent(keyword)}`)
                .then(res => res.json())
                .then(data => {

                    results = data;
                    suggestionBox.innerHTML = "";

                    if (!data.length) {
                        suggestionBox.innerHTML = `<div class="list-group-item">No results</div>`;
                        return;
                    }

                    data.forEach((item, index) => {

                        const div = document.createElement("div");
                        div.className = "list-group-item suggestion-item";
                        div.textContent = item.UnparsedAddress;
                        div.dataset.index = index;

                        suggestionBox.appendChild(div);
                    });

                });

        }, 300);

    });

    // CLICK EVENT (event delegation)

suggestionBox.addEventListener("click", function (e) {

    if (!e.target.classList.contains("suggestion-item")) return;

    const index = e.target.dataset.index;
    const data = results[index];

    console.log("Selected Data:", data); // debug

    // ✅ Address
    document.getElementById("prop-address").value = data.UnparsedAddress || '';

    // ✅ Bedrooms
    document.getElementById("bedrooms").value = data.BedroomsAboveGrade ?? 0;
    document.getElementById("bedrooms-below").value = data.BedroomsBelowGrade ?? 0;

    // ✅ Bathrooms
    document.getElementById("bathrooms").value = data.BathroomsTotalInteger ?? 0;

    // ✅ Garage
    document.getElementById("garage").value = data.ParkingTotal ?? 0;

    // ✅ Square Footage
    document.getElementById("sqft").value = data.LivingAreaRange ?? '0';

    // ✅ Tax
    document.getElementById("tax").value = data.TaxAnnualAmount ?? '0';

    // ✅ Property Type (match option)
    const propertyType = document.getElementById("property-type");
    if (propertyType) {
        propertyType.value = data.PropertySubType || '';
    }

    // ✅ Lot sizes
    document.getElementById("lot-width").value = data.LotWidth ?? '0';
    document.getElementById("lot-depth").value = data.LotDepth ?? '0';

    // ✅ Clear dropdown
    suggestionBox.innerHTML = "";

});


// FAQ toggle (namespaced)
document.querySelectorAll(".srk-faq-q").forEach(q => {
  q.addEventListener("click", () => {
    const a = q.nextElementSibling;
    a.style.display = (a.style.display === "block") ? "none" : "block";
  });
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