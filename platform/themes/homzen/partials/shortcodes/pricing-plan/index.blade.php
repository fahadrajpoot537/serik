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






/* make sure container doesn't force vertical stack */
.wd-find-select{
    display:flex;
    align-items:center;
    gap:20px;
}

.wd-find-select .inner-group{
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

    .wd-find-select .inner-group{
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



   
.mascot-box {
  position: fixed;
  bottom: 20px;
  right: 0px;
  width: 400px;
  z-index: 9999;
}

.mascot-box img {
  width: 100%;
  animation: floaty 1.2s infinite ease-in-out;
}

/* TEXT OVERLAY */
.srk-mascot-text {
  position: absolute;
  top: 60%;   /* adjust this */
  left: 30%;
  transform: translate(-50%, -50%);
  color:#fff;
  width: 30%;
  text-align: center;

  font-size: 12px;
  font-weight: 600;
  color: #000;

  pointer-events: none; /* so it doesn't block clicks */
}

/* Animation 
@keyframes floaty {
  0%   { transform: translateY(0px) scale(1); }
  50%  { transform: translateY(-8px) scale(1.03); }
  100% { transform: translateY(0px) scale(1); }
}
*/
.hidden {
  display: none;
}


</style>

<section class="flat-section flat-pricing">
    
    	 <div class="flat-tab flat-tab-form cashback-calculator"  id="calculator-buttons" style=" scroll-margin-top: 150px;">
    <div class="tab-content">
        <div class="container relative">
            <div class="row justify-content-center">

                <div class="col-lg-10 col-md-10 col-12">
                    <div class="tab-pane fade active show" role="tabpanel" style="width: 80%;">

                        <form id="myForm">

                            <div class="wd-find-select calculator-box">

                                <div class="inner-group" style="padding: 10px 30px 10px 20px;">

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
                                <div id="mascotBox" class="mascot-box hidden">
  
                                  <img src="https://serik.ca/storage/avatars/serikgif400.gif" alt="Mascot" />
                                
                                  <!-- Text on banner -->
                                  <div class="srk-mascot-text" style="color:white !important;" id="result" class="calculator-result">
                                   
                                  </div>
                                
                                </div>
                                    <div id="serikTrigger"></div>

                                <div class="calculator-buttons">

                                    <button type="submit" 
                                        class="tf-btn primary"
                                        onclick="calculatePercentage()">
                                        {{ __('Calculate Cash Back') }}
                                    </button>
                                
                                   
                                
                                </div>

                            </div>

                        </form>

                        <center>
                            <!--div id="result" class="calculator-result"></div-->
                        </center>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</section>


<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
const mascot = document.getElementById("mascotBox");

let confettiInterval = null;
let hideTimeout = null;

// 🎉 START
function startCelebration() {

  stopCelebration(); // reset if already running

  mascot.classList.remove("hidden");

  confettiInterval = setInterval(() => {
    confetti({
      particleCount: 60,
      spread: 80,
      origin: { y: 0.6 },
      colors: ['#4f46e5', '#22c55e', '#f59e0b', '#ef4444']
    });
  }, 700);

  hideTimeout = setTimeout(() => {
    stopCelebration();
  }, 30000); // 10 sec
}

// 🛑 STOP
function stopCelebration() {
  if (confettiInterval) clearInterval(confettiInterval);
  if (hideTimeout) clearTimeout(hideTimeout);

  confettiInterval = null;
  hideTimeout = null;

  mascot.classList.add("hidden");
}

// 🚫 prevent form reload
document.getElementById("myForm").addEventListener("submit", function(e) {
  e.preventDefault();
  calculatePercentage();
});

// 💰 currency formatting
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

// 🧮 CALCULATOR
function calculatePercentage() {

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

  // 🎉 trigger ONLY when valid
  startCelebration();

  const percentage = 1.5;
  const result = (amount * percentage) / 100;

  document.getElementById("result").style.display = 'block';
  document.getElementById("result").innerHTML =
    "Your Cash Back is Upto<br> <label style='font-weight:600;font-size:18px;'> $" + result.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) +
    "</label><br> (<span style='color:white;'>*Terms and Conditions Apply</span>)";

  hideResultAfterDelay();
}

// ⏳ reset
function hideResultAfterDelay() {
  setTimeout(function () {
    document.getElementById("amount").value = null;
    document.getElementById("result").style.display = "none";
  }, 30000);
}
</script>

<!--section class="flat-section flat-pricing">
    <div class="container">
        {!! Theme::partial('shortcode-heading', compact('shortcode')) !!}

        <div class="row">
            @foreach ($packages as $package)
                <div class="box col-lg-{{ max(round(12 / $packages->count()), 3) }} col-md-6 g-4">
                    <div @class(['box-pricing', 'active' => $package->is_default])>
                        <div class="price d-flex align-items-end">
                            <h4>{{ $package->price == 0 ? __('Free') : format_price($package->price) }}</h4>
                            <span class="body-2 text-variant-1">
                                /
                                @if ($package->number_of_listings === 1)
                                    {{ __('1 post') }}
                                @else
                                    {{ __(':number posts', ['number' => number_format($package->number_of_listings)]) }}
                                @endif
                            </span>
                        </div>
                        <div class="box-title-price">
                            <h6 class="title">{!! BaseHelper::clean($package->name) !!}</h6>
                            @if ($package->description)
                                <p class="desc">{{ $package->description }}</p>
                            @endif
                        </div>
                        @if ($package->formatted_features)
                            <ul class="list-price">
                                @foreach ($package->formatted_features as $feature)
                                    <li class="item">
                                        <span class="check-icon icon-tick"></span>
                                        {!! BaseHelper::clean($feature) !!}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a
                            href="{{ route('public.account.packages') }}"
                            class="tf-btn"
                        >
                            {{ __('Choose The Package') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section-->
