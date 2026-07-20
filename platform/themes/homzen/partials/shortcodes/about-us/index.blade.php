

<style>
.flat-section{
    padding:30px 0px !important;
    
}
.who-we-are{
    padding:80px 0;
    background:#f7f7f7;
}

.who-container{
    max-width:1200px;
    margin:auto;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:50px;
    align-items:center;
}

.who-small{
    text-transform:uppercase;
    letter-spacing:2px;
    font-size:13px;
    color:#0255a1;
    font-weight:600;
}

.who-title{
    font-size:42px;
    font-weight:700;
    line-height:1.2;
    margin:10px 0 20px;
}

.who-text{
    font-size:16px;
    color:#666;
    line-height:1.7;
}

.mission-box{
    margin-top:30px;
    padding-left:20px;
    border-left:3px solid #0255a1;
    font-style:italic;
    color:#444;
}

.mission-author{
    margin-top:10px;
    font-size:13px;
    letter-spacing:1px;
    color: #0255a1;
    text-transform:uppercase;
}

.who-image-wrapper{
    position:relative;
}

.who-image-wrapper img{
    width:100%;
    border-radius:6px;
}

.years-box{
    position:absolute;
    bottom:-30px;
    left:-30px;
    background:#fff;
    padding:25px 35px;
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
    border-radius:6px;
}

.years-number{
    font-size:28px;
    font-weight:700;
    color: #0255a1;
}

.years-text{
    font-size:12px;
    letter-spacing:1px;
    color:#666;
}

/* Tablet */
@media (max-width:992px){
    .who-container{
        grid-template-columns:1fr;
        gap:40px;
    }

    .years-box{
        left:20px;
        bottom:-20px;
    }

    .who-title{
        font-size:32px;
    }
}

/* Mobile */
@media (max-width:576px){
    .who-we-are{
        padding:60px 15px;
    }

    .who-title{
        font-size:26px;
    }

    .years-box{
        position:relative;
        left:0;
        bottom:0;
        margin-top:20px;
        display:inline-block;
    }
}




.trust-title{
    font-size:24px;
    font-weight:700;
    margin-bottom:15px;
    padding:30px 0px;
}

.trust-text{
    color:#666;
    line-height:1.7;
    margin-bottom:25px;
}

/* Bullet Grid */
.trust-points{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px 40px;
    list-style:none;
    padding:0;
}

.trust-points li{
    position:relative;
    padding-left:22px;
    font-size:16px;
    color:#333;
}

.trust-points li::before{
    content:"•";
    position:absolute;
    left:0;
    top:0;
    font-size:22px;
    color:#0255a1;
}

/* Mobile */
@media(max-width:768px){
    .trust-points{
        grid-template-columns:1fr;
    }
    .who-container-div{
        padding:0px 10px;
    }
    .foundation-div{
        padding:0px 10px;
    }
}
</style>


<section class="flat-section flat-banner-about who-we-are">
    <div class="who-container">

        <!-- LEFT CONTENT -->
        <div class="who-container-div">
            <div class="who-small">Who We Are</div>

            <h2 class="who-title">
                A trusted real estate partner dedicated to your goals.
            </h2>

            <p class="who-text">
                At Serik Realty, we understand that real estate is about more than just
                transactions — it’s about important life decisions and transitions.
                We make the process easier by offering clear communication, honest advice,
                and a professional approach so that every client can move forward with
                confidence and clarity.
            </p>

            <div class="mission-box">
                “To deliver real estate services built on transparency,
                professionalism, and client confidence.”
                <div class="mission-author">— Our Mission</div>
            </div>
        </div>


        <!-- RIGHT IMAGE -->
        <div class="who-image-wrapper">
            <img src="storage/deb5731634efc4bfedb339edde9e025d7701e885-2000x1308.jpg" alt="Serik Realty Inc">

            <div class="years-box">
                <div class="years-number">15+</div>
                <div class="years-text">YEARS OF EXCELLENCE</div>
            </div>
        </div>

    </div>
</section>




<section class="flat-section flat-banner-about ">
    <div class="container">
        <div class="row foundation-div">
            <div class="col-md-12" style="text-align: center;">
                @if ($shortcode->title)
                    <h3>{!! BaseHelper::clean($shortcode->title) !!}</h3>
                @endif
            </div>
            <h3 class="trust-title" style="text-align: center;">
            Built on trust, transparency, and ethical practice.
            </h3>
    
            <p class="trust-text" style="text-align: center;">
                Serik Realty was founded with a simple goal to transform the real estate
                experience for our clients. In an industry often flooded with overwhelming
                information and unclear advice, we recognised the need for a brokerage
                that takes a different approach. We’re committed to:
            </p>
    
            <ul class="trust-points">
                <li>Clear communication</li>
                <li>Full transparency in process and pricing</li>
                <li>Ethical representation</li>
                <li>No hidden agendas</li>
            </ul>
        </div>
        <div class="banner-video">
            @if ($shortcode->image)
                {{ RvMedia::image($shortcode->image, $shortcode->title) }}
            @endif

            @if ($shortcode->video_url)
                <a
                    href="{{ $shortcode->video_url }}"
                    data-fancybox="gallery2"
                    class="btn-video"
                >
                    <span class="icon icon-play"></span>
                </a>
            @endif
        </div>
    </div>
</section>

<style>

.services-section{
    padding:80px 0;
    background:#f7f7f7;
}

.services-container{
    max-width:1200px;
    margin:auto;
    padding:0 20px;
}

.services-header{
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    gap:15px;
    margin-bottom:40px;
}


.services-title{
    font-size:40px;
    font-weight:700;
}

.services-sub{
    color:#666;
    margin-top:10px;
}

.services-btn{
    color:white;
    background:#0255a1;
    color:#fff;
    padding:12px 22px;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
}

.services-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:30px;
}

.service-card{
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 10px 20px rgba(0,0,0,0.05);
    transition:0.3s;
}

.service-card:hover{
    transform:translateY(-5px);
}

.service-icon{
    font-size:34px;
    color:#0255a1;
    margin-bottom:15px;
}

.service-card h3{
    font-size:20px;
    margin-bottom:10px;
}

.service-card p{
    color:#666;
    line-height:1.6;
    margin-bottom:15px;
}

.service-list{
    list-style:none;
    padding:0;
}

.service-list li{
    margin-bottom:8px;
    display:flex;
    align-items:center;
    gap:8px;
    font-size:15px;
}

.service-list i{
    color:#0255a1;
}

/* Tablet */
@media(max-width:992px){

.services-grid{
    grid-template-columns:repeat(2,1fr);
}

.services-title{
    font-size:32px;
}

}

/* Mobile */
@media(max-width:600px){

.services-grid{
    grid-template-columns:1fr;
}

.services-header{
    gap:15px;
}

.services-title{
    font-size:26px;
}

}

@media(min-width:992px){

.services-grid{
    grid-template-columns:repeat(6,1fr);
}

.services-grid .service-card{
    grid-column:span 2;
}

.services-grid .service-card:nth-child(4){
    grid-column:2 / span 2;
}

.services-grid .service-card:nth-child(5){
    grid-column:4 / span 2;
}

}

</style>



<section class="services-section">

<div class="services-container">

<div class="services-header">

<div>
<h2 class="services-title">
Comprehensive solutions for buyers, sellers, and investors.
</h2>

<p class="services-sub">
Professional real estate services designed to guide you through every stage of your property journey with confidence.
</p>
</div>

<a href="contact-us" class="services-btn" style="color:white">
Book Your Free Consultation
</a>

</div>


<div class="services-grid">

<!-- Service 1 -->

<div class="service-card">

<div class="service-icon">
<i class="ti ti-home-search"></i>
</div>

<h3>Buyer Representation & Property Search</h3>

<p>
We offer personalized support to help buyers find the perfect property and confidently navigate every step of the process.
</p>

<ul class="service-list">
<li><i class="ti ti-user"></i> Customized property recommendations</li>
<li><i class="ti ti-user"></i> Guided property tours</li>
<li><i class="ti ti-user"></i> Support with offer strategies</li>
</ul>

</div>


<!-- Service 2 -->

<div class="service-card">

<div class="service-icon">
<i class="ti ti-home"></i>
</div>

<h3>Seller Strategy & Home Marketing</h3>

<p>
Our seller services are designed to showcase your home, attract the right buyers, and deliver great results.
</p>

<ul class="service-list">
<li><i class="ti ti-user"></i> Strategic home presentation</li>
<li><i class="ti ti-user"></i> Targeted marketing to reach the right audience</li>
<li><i class="ti ti-user"></i> Positioning your home to match buyer demand</li>
</ul>

</div>


<!-- Service 3 -->

<div class="service-card">

<div class="service-icon">
<i class="ti ti-chart-line"></i>
</div>

<h3>Market Analysis & Pricing Strategy</h3>

<p>
We provide data-driven pricing guidance so you understand market trends and position your property competitively.
</p>

<ul class="service-list">
<li><i class="ti ti-user"></i> Comparative market analysis</li>
<li><i class="ti ti-user"></i> Accurate valuation insights</li>
<li><i class="ti ti-user"></i> Pricing optimization strategy</li>
</ul>

</div>


<!-- Service 4 -->

<div class="service-card">

<div class="service-icon">
<i class="ti ti-home-hand"></i>
</div>

<h3>Transaction Coordination & Negotiation</h3>

<p>
We handle showings, offers, negotiations, and closing so the process remains smooth and stress-free.
</p>

<ul class="service-list">
<li><i class="ti ti-user"></i> Offer management</li>
<li><i class="ti ti-user"></i> Expert negotiation</li>
<li><i class="ti ti-user"></i> Closing coordination</li>
</ul>

</div>


<!-- Service 5 -->

<div class="service-card">

<div class="service-icon">
<i class="ti ti-network"></i>
</div>

<h3>Professional Network & Client Support</h3>

<p>
Our network of brokers, lawyers, and inspectors ensures every stage of your real estate journey is supported.
</p>

<ul class="service-list">
<li><i class="ti ti-user"></i> Mortgage referrals</li>
<li><i class="ti ti-user"></i> Legal coordination</li>
<li><i class="ti ti-user"></i> Inspection support</li>
</ul>

</div>


</div>

</div>

</section>

