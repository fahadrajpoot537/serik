<div class="my-40 flat-quote" id="formMain">
    <blockquote class="quote">
        “{{ BaseHelper::clean($shortcode->message) }}”
    </blockquote>

    @if($shortcode->author)
        <span class="author">{{ $shortcode->author }}</span>
    @endif
</div>
 <style>
      

        .seminar-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }

        /* Center iframe content */
        .form-column {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .iframe-wrapper {
            width: 100%;
            max-width: 650px;
            background: #ffffff;
             align-items: center;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .iframe-wrapper iframe {
            width: 100%;
            height: 860px;
             align-items: center;
            border: none;
            display: block;
        }

        .image-column {
            display: flex;
            
        }

        .banner-image {
            width: 100%;
            max-width: 600px;
            height: auto;
        
            /* FIX */
            object-fit: cover;
            object-position: top;
        
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        
            /* optional */
            display: block;
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {

            .seminar-section {
                padding: 15px;
            }

            .banner-image {
                height: auto;
                margin-top: 20px;
            }

            .iframe-wrapper iframe {
                height: 900px;
            }
        }
    </style>

<div id="secondMain">
   <section class="seminar-section">
        <div class="container-fluid">
            <div class="row justify-content-center align-items-start">
                
                <!-- Right Side - Image -->
                <div class="col-lg-6 col-12 image-column">
                    <img
                        src="https://serik.ca/storage/serik-seminar-2.png"
                        alt="Free First Time Home Buyer Seminar"
                        class="banner-image">
                </div>
    
                <!-- Left Side - Form -->
                <div class="col-lg-6 col-12 form-column mb-4 mb-lg-0">
                    <div class="iframe-wrapper">
    
                        <iframe
                            id="seminarIframe"
                            src="https://api.leadconnectorhq.com/widget/form/kEaYUyoDEoePbzwCMYlR?notrack=true"
                            scrolling="no">
                        </iframe>
    
                    </div>
                </div>
    
                
    
            </div>
        </div>
    </section>
</div>



<script>
    
    
    function isHomePage() {
    return window.location.pathname === '/fthb';
}



if (isHomePage()) {
    document.getElementById("formMain").style.display = "none";
    document.getElementById("secondMain").style.display = "block";
   
} else{
    document.getElementById("formMain").style.display = "block";
    document.getElementById("secondMain").style.display = "none";
    
    
}
    
    
    
    // Auto adjust iframe height to content
    window.addEventListener('message', function(event) {

        // Security check
        if (event.origin.includes("leadconnectorhq.com")) {

            if (event.data && event.data.height) {
                document.getElementById('seminarIframe').style.height =
                    event.data.height + 'px';
            }

        }

    });

    
</script>