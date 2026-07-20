
<style>
.property-page-nav {
  position: sticky;
  top: 80px;
  z-index: 90;
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
}

.property-nav-wrapper{
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.property-nav-list {
  display: flex;
  gap: 28px;
  list-style: none;
  padding: 10px 0;
  margin: 0 0 0 50px;
}

.property-nav-list li a {
  font-size: 15px;
  font-weight: 700;
  color: #111827;
  text-decoration: none;
  padding-bottom: 6px;
}

.property-nav-list li a.active {
  color: #1f6ed4;
  border-bottom: 2px solid #1f6ed4;
}

/* RIGHT SIDE BUTTONS */

.property-actions{
  display:flex;
  gap:15px;
  margin-right:40px;
}

.property-actions button{
  border:none;
  background:#f3f4f6;
  padding:8px 12px;
  border-radius:6px;
  cursor:pointer;
  font-size:14px;
  display:flex;
  align-items:center;
  gap:6px;
}

.property-actions button:hover{
  background:#e5e7eb;
}

/* Anchor offset */
#overview,
#description,
#location,
#reviews-section {
  scroll-margin-top: 130px;
}

/* Mobile */
@media (max-width: 768px) {

  /* ONLY target property page nav */
  #propertyNav .property-nav-wrapper {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 10px;
  }

  /* NAV LINKS */
  #propertyNav .property-nav-list {
    width: 100%;
    overflow-x: auto;
    white-space: nowrap;
    gap: 18px;
    margin: 0;
    padding-bottom: 6px;
  }

  #propertyNav .property-nav-list li {
    flex: 0 0 auto;
  }

  #propertyNav .property-nav-list li a {
    font-size: 14px;
    padding-bottom: 4px;
  }

  #propertyNav .property-nav-list::-webkit-scrollbar {
    display: none;
  }

  /* ACTION BUTTONS */
  #propertyNav .property-actions {
    width: 100%;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 0;
  }

  #propertyNav .property-actions button {
    flex: 1;
    justify-content: center;
    font-size: 13px;
    padding: 8px;
  }

  #propertyNav .property-actions .icon-box {
    display: flex;
    align-items: center;
  }

  /* Sticky offset only for this nav */
  #propertyNav {
    top: 70px;
  }

  .flat-property-detail .container {
    padding-left: 12px;
    padding-right: 12px;
  }

  .flat-property-detail .single-property-element {
    padding-bottom: 24px;
    margin-bottom: 24px;
  }

  .single-property-overview,
  .single-property-desc {
    padding-left: 0 !important;
    padding-right: 0 !important;
    overflow: visible !important;
  }
}

/* Desktop: keep the contact / schedule-viewing form pinned while the
   left column (details) scrolls. */
@media (min-width: 992px) {
  .flat-property-detail .row > .col-lg-4 {
    align-self: flex-start;
    position: sticky;
    top: 100px;
  }
  .flat-property-detail .row > .col-lg-4 .widget-sidebar {
    max-height: calc(100vh - 120px);
    overflow-y: auto;
  }
  /* Hide the inner scrollbar for a clean look */
  .flat-property-detail .row > .col-lg-4 .widget-sidebar {
    scrollbar-width: thin;
  }
}
.share-popup{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.5);
display:none;
align-items:center;
justify-content:center;
z-index:999;
}

.share-box{
background:#fff;
padding:25px;
border-radius:10px;
width:320px;
position:relative;
text-align:center;
}

.close-share{
position:absolute;
right:15px;
top:10px;
cursor:pointer;
font-size:18px;
}

.share-links{
display:flex;
flex-direction:column;
gap:12px;
margin-top:15px;
}

.share-links a,
.share-links button{
padding:10px;
border:none;
background:#f3f4f6;
cursor:pointer;
text-decoration:none;
border-radius:6px;
font-size:14px;
}

.share-links a:hover,
.share-links button:hover{
background:#e5e7eb;
}
</style>
<style>
.property-item {
    position: relative;
    overflow: hidden;
}

.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

.property-login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 99;

    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    padding: 20px;
}

.property-login-overlay-content {
    max-width: 480px;
}

.property-login-overlay-caption {
    color: #fff;
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 16px;
}

.property-login-overlay-caption a {
    color: #fff;
    font-weight: 600;
    text-decoration: underline;
}
</style>


 @if ($model->isSoldHistory() && !(auth('account')->check() || auth()->check()))
    {!! Theme::partial('sold-property-login-gate') !!}
@endif       
        
        <nav class="property-page-nav @if ($model->isSoldHistory() && !(auth('account')->check() || auth()->check())) blurred-content @endif" id="propertyNav" >

            <div class="property-nav-wrapper">
            
                <ul class="property-nav-list">
                
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#description">Description</a></li>
                    <li><a href="#location">Location</a></li>
                    <li><a href="#reviews-section">Reviews</a></li>
                
                </ul>
                
                
                <div class="property-actions">
                
                     @if (RealEstateHelper::isEnabledWishlist())
                        <ul class="icon-box">
                            <li>
                                <button type="button" class="item" data-type="{{ $model instanceof \Botble\RealEstate\Models\Property ? 'property' : 'project' }}"
                                        data-bb-toggle="add-to-wishlist"
                                        data-id="{{ $model->getKey() }}"
                                        data-add-message="{{ __('Added ":name" to wishlist successfully!', ['name' => $model->name]) }}"
                                        data-remove-message="{{ __('Removed ":name" from wishlist successfully!', ['name' => $model->name]) }}"
                                >
                                    <x-core::icon name="ti ti-heart" />
                                </button>
                            </li>
                        </ul>
                    @endif
                    
                    <button id="shareBtn">🔗 Share</button>
                    
                    <button id="fullscreenBtn">⛶ Fullscreen</button>
                
                </div>
                
            </div>
        
        </nav>
        <div class="share-popup @if ($model->isSoldHistory() && !(auth('account')->check() || auth()->check())) blurred-content @endif" id="sharePopup">

        <div class="share-box">
        
        <span class="close-share" id="closeShare">✕</span>
        
        <h4>Share this property</h4>
        
        <div class="share-links">
        
        <a id="shareFacebook" target="_blank">Facebook</a>
        <a id="shareTwitter" target="_blank">Twitter</a>
        <a id="shareWhatsapp" target="_blank">WhatsApp</a>
        
        <button id="copyLinkBtn">Copy Link</button>
        
        </div>
        
        </div>
        
        </div>
        
<div id="galleryContainer" class="@if ($model->isSoldHistory() && !(auth('account')->check() || auth()->check())) blurred-content @endif">
    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.gallery-slider'), ['model' => $model])
</div>
<section class="flat-section pt-0 flat-property-detail @if ($model->isSoldHistory() && !(auth('account')->check() || auth()->check())) blurred-content @endif" >
    <div class="container">
        
        {!! apply_filters('ads_render', null, 'detail_page_before') !!}

        @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.header'), ['model' => $model])
       
        <div class="row">
            <div class="col-lg-8">
                {!! apply_filters('before_single_content_detail', null, $model) !!}

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.description'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.video'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.features'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.facilities'), ['class' => 'single-property-element', 'model' => $model])

                @if (!($model instanceof \Botble\RealEstate\Models\Project))
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.project'), ['class' => 'single-property-element', 'model' => $model])
                @endif

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.map'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.floor-plans'), ['class' => 'single-property-element', 'model' => $model])

                {!! apply_filters('after_single_content_detail', null, $model) !!}

                <div class="wrapper-onepage">
                    @include(Theme::getThemeNamespace('views.real-estate.partials.social-sharing'), ['model' => $model])
                </div>

                {!! apply_filters(
                    BASE_FILTER_PUBLIC_COMMENT_AREA,
                    null,
                    $model
                ) !!}

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.reviews'), ['model' => $model, 'class' => 'single-property-element'])
            </div>
            <div class="col-lg-4">
                <div class="widget-sidebar wrapper-sidebar-right">
                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_before') !!}

                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.contact'), ['class' => 'bg-surface', 'model' => $model])

                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_after') !!}
                </div>
            </div>
        </div>

        {!! apply_filters('ads_render', null, 'detail_page_after') !!}
    </div>
</section>

@include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.related-properties'), ['model' => $model])


<script>

window.addEventListener("load", function () {
    document.getElementById("galleryContainer").style.display = "block";
});



// FULLSCREEN
document.getElementById("fullscreenBtn").onclick = function(){

if (!document.fullscreenElement) {
document.documentElement.requestFullscreen();
} else {
document.exitFullscreen();
}

}

const shareBtn = document.getElementById("shareBtn");
const sharePopup = document.getElementById("sharePopup");
const closeShare = document.getElementById("closeShare");

shareBtn.onclick = function(){
sharePopup.style.display = "flex";

let url = encodeURIComponent(window.location.href);

document.getElementById("shareFacebook").href =
"https://www.facebook.com/sharer/sharer.php?u="+url;

document.getElementById("shareTwitter").href =
"https://twitter.com/intent/tweet?url="+url;

document.getElementById("shareWhatsapp").href =
"https://wa.me/?text="+url;
}

closeShare.onclick = function(){
sharePopup.style.display="none";
}


// COPY LINK
document.getElementById("copyLinkBtn").onclick = function(){

navigator.clipboard.writeText(window.location.href);
alert("Link copied!");

}








</script>

