@php
    $topFooterSidebar = dynamic_sidebar('top_footer_sidebar');
    $innerFooterSidebar = dynamic_sidebar('inner_footer_sidebar');
    $bottomFooterSidebar = dynamic_sidebar('bottom_footer_sidebar');
    $footerBackgroundColor = theme_option('footer_background_color', '#161e2d');
    $footerBackgroundImage = RvMedia::getImageUrl(theme_option('footer_background_image'));
@endphp

@if($topFooterSidebar || $innerFooterSidebar || $bottomFooterSidebar)
 <style>
     .icon-bar {
  position: fixed;
  z-index:10000000;
  top: 85%;
  right: 12px;
  transform: translateY(-50%);
}

.icon-bar a {
  display: block;
  padding: 16px;
  transition: all 0.3s ease;
 
}

.icon-bar a:hover {
  transform: Scale(1.2);
}
@media (max-width: 768px) {
    .footer-main{
        padding-left:10px;
        padding-right:10px;
    }
}

 </style>  
 
   <!--div class="icon-bar">
      <a href="https://api.whatsapp.com/send?phone=16475789400" class="whatsapp" target="_blank"><img src="https://vke.899.mytemp.website/storage/whatsapp-icon-free-png.png" width="50"/></a> 
     
    </div-->
    <footer class="footer footer-main" @style(["background-color: $footerBackgroundColor" => $footerBackgroundColor, "background-image: url('$footerBackgroundImage') !important" => theme_option('footer_background_image')])>
        @if($topFooterSidebar)
            <div class="top-footer">
                <div class="container">
                    <div class="content-footer-top">
                        {!! $topFooterSidebar !!}
                    </div>
                </div>
            </div>
        @endif

        @if($innerFooterSidebar)
            <div class="inner-footer" >
                <div class="container">
                    <div class="row">
                        {!! $innerFooterSidebar !!}
                    </div>
                    
                </div>
            </div>
        @endif

        @if($bottomFooterSidebar)
            <div class="bottom-footer">
                <div class="container">
                    <div class="content-footer-bottom">
                        {!! $bottomFooterSidebar !!}
                    </div>
                    
                </div>
            </div>
        @endif
    </footer>
@endif



<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script>




window.addEventListener("load", function () {
    



    let currentStep = 1;
    const steps = document.querySelectorAll(".form-step[data-step]");
    const totalSteps = steps.length;
    
    function showStep(step) {
        steps.forEach(el => el.classList.add("d-none"));
        const active = document.querySelector(`.form-step[data-step="${step}"]`);
        if (active) active.classList.remove("d-none");
    }
    
 //   console.log("Total steps:", totalSteps);
//console.log("Current step:", currentStep);

    document.addEventListener("click", function (e) {

        const nextBtn = e.target.closest(".next-step");
        const prevBtn = e.target.closest(".prev-step");

        if (nextBtn) {
            e.preventDefault();
            console.log("Next clicked"); // debug
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }

        if (prevBtn) {
            e.preventDefault();
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

    });

    showStep(currentStep);
});

document.addEventListener("DOMContentLoaded", function () {

    let emailTimer = null;

    function updateNextButton(emailInput) {

        const nextBtn = document.querySelector('div[data-step="1"] .next-step');
        if (!nextBtn) return;

        const isInvalid = emailInput.classList.contains("is-invalid");
        const hasValue = emailInput.value.trim().length > 0;

        if (!isInvalid && hasValue) {
            nextBtn.disabled = false;
            nextBtn.classList.remove("disabled");
        } else {
            nextBtn.disabled = true;
            nextBtn.classList.add("disabled");
        }
    }

    document.addEventListener("input", function (e) {

        if (e.target && e.target.name === "email") {

            const emailInput = e.target;

            // debounce (wait user stops typing)
            clearTimeout(emailTimer);

            emailTimer = setTimeout(() => {
                updateNextButton(emailInput);
            }, 600); // ⏱ user stops typing for 500ms
        }
    });

    // still keep blur as backup (optional)
    document.addEventListener("focusout", function (e) {

        if (e.target && e.target.name === "email") {
            setTimeout(() => updateNextButton(e.target), 200);
        }
    });

});
document.addEventListener("DOMContentLoaded", function () {

    function updateStep2Button() {
        const nameInput = document.getElementById("first_name");
        const phoneInput = document.querySelector('input[name="phone"]');
        const nextBtn = document.querySelector('div[data-step="2"] .next-step');

        if (!nameInput || !nextBtn) return;

        const nameValid = nameInput.value.trim().length > 0;

        // If phone is required → validate it, otherwise ignore
        let phoneValid = true;

        if (phoneInput && phoneInput.hasAttribute("required")) {
            const iti = window.intlTelInputGlobals.getInstance(phoneInput);
            phoneValid = iti ? iti.isValidNumber() : phoneInput.value.trim().length > 0;
        }

        if (nameValid && phoneValid) {
            nextBtn.disabled = false;
            nextBtn.classList.remove("disabled");
        } else {
            nextBtn.disabled = true;
            nextBtn.classList.add("disabled");
        }
    }

    // Listen to input changes
    document.addEventListener("input", function (e) {
        if (
            e.target.id === "first_name" ||
            e.target.name === "phone"
        ) {
            updateStep2Button();
        }
    });

    // Also run once (important if prefilled)
    setTimeout(updateStep2Button, 300);
});


document.addEventListener("DOMContentLoaded", function () {

    setTimeout(function () {

         const phoneInput = document.getElementById('register-phone');
        if (!phoneInput) {
            console.error("Phone input not found");
            return;
        }

        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "ca",
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
        });

        console.log("intl-tel-input initialized", iti);

    }, 300); // 👈 IMPORTANT for Botble

});




  function scrollToHash() {
    const hash = window.location.hash;
    if (!hash) return;

    const el = document.querySelector(hash);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    } else {
        // Retry after a short delay if element is not yet rendered
        setTimeout(scrollToHash, 100);
    }
}

document.addEventListener('DOMContentLoaded', scrollToHash);
</script>