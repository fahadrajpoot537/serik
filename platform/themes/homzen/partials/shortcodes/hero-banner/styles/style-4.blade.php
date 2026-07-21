<link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet">
<link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.css" rel="stylesheet" />
@include(Theme::getThemeNamespace('partials.property-photo-lightbox'))
@if(request()->has('city'))
<link rel="canonical" href="{{ \App\Support\CanonicalUrl::normalize(url(request()->path())) }}">
@endif
<style>
.map-housesigma{
    position:relative;
    display:flex;
    flex-direction:column;
    min-height:0;
}

.map-housesigma #map {
    background: #f4f2ef;
}

@media (min-width: 992px) {
    .map-housesigma {
        /* Fill viewport below site header + map filter bars */
        min-height: calc(100dvh - 132px);
    }

    .map-housesigma .map-search-wrapper {
        flex: 1 1 auto;
        min-height: 480px;
        height: calc(100dvh - 168px) !important;
        max-height: none;
    }

    .map-housesigma .map-search-wrapper #map {
        height: 100% !important;
        min-height: 0 !important;
    }
}

/* ===== TOP BAR ===== */
.hs-topbar{
    position: relative;
    left: 0;
    transform: none;
    width:100%;
    background:#ffffff;
    z-index:100;
    box-shadow:0 4px 12px rgba(0,0,0,.15);
    overflow: visible;
}

.hs-row-1{
    display:flex;
    align-items:center;
    gap:15px;
    background:#9dbdfd;
    color:#000;
    position: relative;
    z-index: 1300; /* above filter row so search never hides under Active */
    overflow: visible;
}

.hs-row-2{
   /* display:flex;*/
     background:#ffffff;
    align-items:center;
    width: 100%;
    gap:15px;
    position: relative;
    z-index: 1100;
    overflow: visible;
}

.hs-brand{
    color:#000;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:6px;
    max-width:150px;
}
.maplibregl-canvas{
    width:100% !important;
}
/* ===== SEARCH FIX FOR BOTBLE ===== */
.hs-search{
    flex:1 1 auto;
    min-width: 220px;
    position: relative;
    z-index: 1301;
}

.hs-search .wrap-search-form{
    margin:0!important;
}

/* ===== TOP MENU ===== */
.hs-menu{
    display:flex;
    gap:28px;
}

.hs-menu a{
    color:#000 !important;
    font-size:14px;
    text-decoration:none;
}

/* ===== RIGHT FLOATING BUTTONS ===== */
.hs-actions{
    position:absolute;
    right:15px;
    bottom:20px;
    z-index:999;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.btn-circle{
    width:44px;
    height:44px;
    background:#fff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
    cursor:pointer;
}

.btn-circle.red{
    background:#e53935;
    color:#fff;
}

/* ===== SMART SEARCH BUTTONS ===== */
 .smart-search {
    position: relative;
    max-width: 560px;
    min-width: 220px;
    width: 100%;
    z-index: 1302;
}

.search-box {
    background:#f3f6f8;
    border-radius:8px;
    padding:6px 10px;
    display:flex;
    align-items:center;
    position: relative;
    z-index: 1303;
}

.search-box input {
    border:none;
    background:transparent;
    outline:none;
    padding: 4px 6px;
    width:100%;
    font-size:16px;
}
.clear-btn {
    cursor:pointer;
    opacity:.6;
}

.search-dropdown {
    position:absolute;
    top:calc(100% + 6px);
    left:0;
    width:100%;
    background:#fff;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    max-height:450px;
    overflow-y:auto;
    display:none;
    z-index:1400; /* above Active split (1200) and filter row */
}

.dropdown-section {
    padding:15px;
}

.section-title {
    font-weight:600;
    color:#666;
    margin-bottom:10px;
}

.location-item {
    padding:10px;
    border-radius:8px;
    cursor:pointer;
}

.location-item:hover {
    background:#f4f7f9;
}

.listing-item {
    display:flex;
    gap:12px;
    padding:10px;
    border-radius:10px;
    cursor:pointer;
}

.listing-item:hover {
    background:#f4f7f9;
}

.listing-item img {
    width:70px;
    height:55px;
    border-radius:8px;
    object-fit:cover;
}

.price {
    color:#0255a1;
    font-weight:700;
}

    
    
    
    

 
    
    
    
   .dropdown-loader{
    display:flex;
    align-items:center;
    gap:10px;
    padding:14px;
    color:#777;
    font-size:14px;
}

.loader-spinner{
    width:18px;
    height:18px;
    border:3px solid #ddd;
    border-top:3px solid #1aa3a8;
    border-radius:50%;
    animation:spin .8s linear infinite;
}

@keyframes spin{
    100%{ transform:rotate(360deg); }
}
 
    
 @media (max-width: 768px) {

    .smart-search {
        position: relative;
        width: 100%;
    }

    .search-dropdown {
        position: fixed !important;   /* important for full screen */
        top: 60px !important;
        left: 0 !important;

        width: 100% !important;
        height: 90vh !important;

        border-radius: 0 !important;
        max-height: 90vh !important;

        z-index: 999999 !important;
        overflow-y: auto;

        background: #fff;
    }

    /* optional: keep search box visible at top */
    .search-box {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f3f6f8;
    }
}   
    
    
    
    
    
    
    
    
    
    
    
   .filter-bar {
        padding: 6px 14px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;

    display: flex;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: flex-start;
    overflow: visible;             /* IMPORTANT: don't clip dropdown menus */
}

/* Every control fills the bar evenly (full width, equal small gaps, no left/right split) */
.filter-group > .filter-btn,
.filter-group > .dropdown,
.filter-group > .hs-split-filter {
    flex: 1 1 0;
    min-width: 0;
}

/* IMPORTANT PART */
.filter-btn {
    width: auto;
    min-width: 88px;
    max-width: 150px;
    height: 34px;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #fff;
    cursor: pointer;
    font-size: 12px;
    line-height: 1.2;
    transition: 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-sizing: border-box;
}

.filter-btn:hover {
    background: #f1f1f1;
}

/* ===== Bigger, fuller filter buttons on desktop ===== */
@media (min-width: 992px) {
    .filter-bar {
        padding: 8px 16px;
    }
    .filter-group {
        gap: 10px;
    }
    .filter-group .filter-btn {
        height: 44px;
        min-height: 44px;
        min-width: 0;
        max-width: none;
        padding: 0 14px;
        font-size: 14px;
        border-radius: 10px;
    }
    .filter-group .hs-split-filter,
    .filter-group .hs-split-filter .hs-split-value {
        height: 44px;
        min-height: 44px;
    }
    .filter-group .hs-split-filter .hs-split-label {
        border-radius: 9px 0 0 9px;
        font-size: 14px;
        padding: 0 16px;
    }
    .filter-group .hs-split-filter .hs-split-value {
        font-size: 14px;
        padding: 0 16px;
        border-radius: 0 9px 9px 0;
    }
    /* Dropdown toggles fill their (flex-grown) wrapper so no inner gaps */
    .filter-group > .dropdown {
        display: flex;
    }
    .filter-group > .dropdown > .dropdown-toggle,
    .filter-group > .dropdown > .filter-btn {
        width: 100%;
    }
    /* Split filters grow evenly too */
    .filter-group > .hs-split-filter {
        min-width: 0;
    }
    .filter-group > .hs-split-filter .hs-split-value {
        flex: 1 1 auto;
        min-width: 0;
    }
}

.filter-btn.active {
    background: #0255a1;
    color: white;
    border-color: #0255a1;
}
#transactionDropdown::after {
    display: none !important;
}

/* Dropdown */
.dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 110%;
    left: 0;
    background: white;
    min-width: 180px;
    border-radius: 10px;
    border: 1px solid #ddd;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    z-index: 999;
     box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dropdown-item {
    padding: 10px 14px;
    cursor: pointer;
}

.dropdown-item:hover {
    background: #f3f3f3;
}

.clear-btn-1 {
    background: transparent;
    border: 1px solid #0255a1;
    color: #0255a1;
}

    
    
    
    
    .dropdown-card {
  width: 300px;
  padding: 20px;
  border-radius: 12px;
 
}

.dropdown-card h3 {
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: 600;
}

.checkbox-item {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
  font-size: 15px;
  cursor: pointer;
  position: relative;
}

.checkbox-item input {
  display: none;
}

.custom-checkbox {
  width: 18px;
  height: 18px;
  border: 2px solid #0255a1;
  border-radius: 3px;
  margin-right: 12px;
  position: relative;
  transition: 0.2s;
}

/* Checked state */
.checkbox-item input:checked + .custom-checkbox {
  background-color: #0255a1;
}

.checkbox-item input:checked + .custom-checkbox::after {
  content: "✓";
  position: absolute;
  color: white;
  font-size: 13px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.actions {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

button {
  padding: 8px 18px;
  border-radius: 10px;
  font-size: 14px;
  cursor: pointer;
  border: none;
}

.btn-cancel {
  background: transparent;
  border: 2px solid #0255a1;
  color: #0255a1;
}

.btn-save {
  background: #0255a1;
  color: white;
}

.btn-save:hover {
  background: #0255b2;
}

.btn-cancel:hover {
  background: rgba(42, 157, 163, 0.1);
}
    
    
    .price-display {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #222;
  text-align: center;
}

.filter-btn-price {
    min-width: 92px;
    max-width: 128px;
    padding: 0 8px;
}

.filter-btn-price .price-filter-btn-label {
    display: inline-block;
    font-size: 12px;
    font-weight: 500;
    margin: 0;
    padding: 0;
    color: inherit;
    line-height: 1.2;
    white-space: nowrap;
}

.filter-btn-price.filter-active {
    background: #0255a1;
    color: #fff;
    border-color: #0255a1;
}

.hs-m-price-label {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

/* RANGE SLIDER */
.range-wrapper {
  position: relative;
  margin-bottom: 18px;
}

.slider {
  width: 100%;
  appearance: none;
  height: 4px;
  border-radius: 5px;
  background: #0255a1;
  outline: none;
}

/* Chrome Thumb */
.slider::-webkit-slider-thumb {
  appearance: none;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #d9d9d9;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* Firefox Thumb */
.slider::-moz-range-thumb {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #d9d9d9;
  border: none;
  cursor: pointer;
}

/* Price scale labels */
.price-scale {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #9b9b9b;
  margin-bottom: 24px;
}

.actions {
  display: flex;
  justify-content: space-between;
}
/* Radio item layout */
.radio-item {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  font-size: 16px;
  cursor: pointer;
  color: #333;
  position: relative;
}

/* Hide default radio */
.radio-item input {
  display: none;
}

/* Custom radio circle */
.custom-radio {
  width: 18px;
  height: 18px;
  border: 2px solid #cfcfcf;
  border-radius: 50%;
  margin-right: 12px;
  position: relative;
  transition: 0.2s;
}

/* Checked state */
.radio-item input:checked + .custom-radio,
.radio-item.selected .custom-radio {
  border-color: #0255a1;
}

.radio-item input:checked + .custom-radio::after,
.radio-item.selected .custom-radio::after {
  content: "";
  width: 10px;
  height: 10px;
  background: #0255a1;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

/* Change text color when selected */
.radio-item input:checked ~ *,
.radio-item.selected {
  color: #0255a1;
}
    
    
    
    .filters-container {
      max-width: 100%;
      margin: 0 auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    .filter-section {
      padding: 20px 24px;
      border-bottom: 1px solid #eee;
    }

    .filter-section:last-child {
      border-bottom: none;
    }

    .filter-title {
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 12px;
      color: #1a1a1a;
    }

    /* Keyword input */
    .keyword-input {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
    }

    .keyword-input::placeholder {
      color: #aaa;
    }

    /* Chips / small tags style buttons */
    .chip-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 14px;
    }

    .chip {
      padding: 6px 14px;
      background: #f1f3f5;
      border-radius: 999px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.13s;
      user-select: none;
    }

    .chip:hover {
      background: #e0e4e8;
    }

    .chip.active {
      background: #0255a1;
      color: white;
    }

    /* Range slider */
    .range-container {
      margin: 16px 0 8px;
    }

    input[type="range"] {
      width: 100%;
      height: 6px;
      background: #e0e0e0;
      border-radius: 3px;
      outline: none;
      -webkit-appearance: none;
    }

    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      background: #0255a1;
      border-radius: 50%;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    input[type="range"]::-moz-range-thumb {
      width: 18px;
      height: 18px;
      background: #0255a1;
      border: none;
      border-radius: 50%;
      cursor: pointer;
    }

    .range-values {
      display: flex;
      justify-content: space-between;
      font-size: 0.85rem;
      color: #555;
      margin-top: 6px;
    }

    /* Two-column layout for bedrooms/bathrooms/etc */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px 24px;
    }

    /* Buttons at bottom */
    .actions {
      padding: 0px 10px;
      display: flex;
      gap: 12px;
      background: #f8f9fa;
    }

    button {

      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 0.98rem;
      font-weight: 500;
      cursor: pointer;
      transition: 0.15s;
    }

    .btn-primary {
      background: #0255a1;
      color: white;
    }

    .btn-primary:hover {
      background: #0055dd;
    }

    .btn-secondary {
      background: #e9ecef;
      color: #333;
    }

    .btn-secondary:hover {
      background: #dee2e6;
    }

    .btn-clear {
      background: transparent;
      color: #666;
      border: 1px solid #ccc;
    }

    .btn-clear:hover {
      background: #f1f3f5;
    }
    
    
    .map-popup {
    width:260px;
    font-family:Arial;
    }
    
    .map-popup img {
        width:100%;
        height:140px;
        object-fit:cover;
        border-radius:8px;
    }
    
    .map-badge {
        position:absolute;
        bottom:10px;
        left:10px;
        background:#e6f4f1;
        color:#0f766e;
        padding:6px 12px;
        border-radius:20px;
        font-size:13px;
        font-weight:600;
    }
    
    .map-price {
        color:#0f766e;
        font-weight:bold;
        font-size:16px;
    }
        .custom-cluster {
            background: transparent;
        }
        
        .cluster-marker {
            background: #e7292a;
            color: #fff;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 1px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

 
    .modal-content {
        position: absolute;
        top: 5%;
        left: 50%;
        transform: translateX(-50%);
        width: 90%;
        height: 90%;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .modal-content iframe {
        width: 100%;
        height: 100%;
    }
    
    .close-modal {
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 28px;
        cursor: pointer;
        z-index: 10;
    }

    .property-modal {
        z-index: 1000;
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
}

.modal-content {
    position: absolute;
    top: 2%;
    left: 50%;
    transform: translateX(-50%);
    width: 96%;
    max-width: 1420px;
    height: 96%;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

/* Loader overlay */
.iframe-loader {
    position: absolute;
    inset: 0;
    background: #ffffff;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
    pointer-events: auto;
}

.iframe-loader.is-hidden {
    display: none !important;
    pointer-events: none !important;
}

/* Spinner animation */
.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #eee;
    border-top: 5px solid #2c7be5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

.modal-content iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 28px;
    cursor: pointer;
    z-index: 10;
}



.top-footer-bar {
    position: fixed;
   left: 0;
   bottom: 0;
   width: 100%;
    background-color: #9dbdfd; /* teal color */
    color: #ffffff;
    font-size: 14px;
    padding: 8px 0;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}

.footer-left {
    font-weight: 500;
}

.footer-center {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    justify-content: center;
}

.footer-center a {
    color: #ffffff;
    text-decoration: none;
    transition: opacity 0.2s ease;
}

.footer-center a:hover {
    opacity: 0.8;
}

.footer-right {
    white-space: nowrap;
}

@media (max-width: 768px) {
    .footer-container {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }

    .footer-center {
        gap: 14px;
    }
}
.property-item {
    position: relative;
    overflow: hidden;
}

.blurred-content {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

.map-sold-login-gate {
    padding: 10px;
    text-align: center;
    background: rgba(255,255,255,0.95);
}

.map-sold-login-gate .js-map-auth-open {
    width: 100%;
    max-width: 280px;
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
}




.property-list-panel {
    position: absolute;
    top: 20px;
    left: 20px;
    width: 420px;
    max-height: 85vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    padding: 15px;
    z-index: 1000;
    display: none;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-bottom: 10px;
}

.property-card {
    display: flex;
    gap: 12px;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 12px;
    cursor: pointer;
}

.property-card img {
    width: 130px;
    height: 90px;
    object-fit: cover;
    border-radius: 8px;
}

.property-card .info {
    flex: 1;
}

.property-card .price {
    font-weight: bold;
    color: #1e7e34;
    font-size: 16px;
}



.cluster-popup .leaflet-popup-content-wrapper {
    border-radius: 12px;
}

.cluster-property-card {
    cursor: pointer;
}

.cluster-property-card:hover {
    background: #f8f9fa;
}


.custom-cluster-wrapper {
    background: transparent;
}

.custom-cluster {
    background: #ee2128;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    border:3px solid white;
}
.houseSigma-cluster {
    background: #ee2128;
    border: 3px solid #ee2128;
    color: #fff;
    border-radius: 50%;
    text-align: center;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    font-family: Arial, sans-serif;
}

.houseSigma-cluster-small {
    width: 30px;
    height: 30px;
    font-size: 14px;
}

.houseSigma-cluster-medium {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.houseSigma-cluster-large {
    width: 50px;
    height: 50px;
    font-size: 18px;
}


.my-cluster-small {
    background: #198754;
    border-radius: 50%;
    color: white;
    font-weight: bold;
}
.maplibregl-popup {
    max-width: 1040px !important;
}
@media (max-width: 991px) {
    .maplibregl-popup,
    .maplibregl-popup.hs-map-mobile-popup {
        max-width: 100vw !important;
    }
    .maplibregl-popup-content {
        max-width: 100vw !important;
    }
}

.maplibregl-popup-content {
    max-width: 1180px !important;
    padding: 0 !important;
    border-radius: 14px !important;
    overflow: hidden;
}

.property-popup.hs-map-popup-full {
    zoom: 1;
    width: 1140px;
    max-width: 100%;
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 0;
    margin: 0;
    padding: 0;
    border-radius: 14px;
    box-shadow: none;
    background: #fff;
    min-height: 460px;
    max-height: 88vh;
}
.hs-map-popup-full .hs-map-gallery-col {
    flex: 0 0 34%;
    min-width: 0;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e2e8f0;
}
.hs-map-popup-full .hs-map-gallery-col .popup-img-div {
    width: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.hs-map-popup-full .hs-map-gallery-main {
    min-height: 280px;
    flex: 1;
}
.hs-map-popup-full .hs-map-gallery-main img,
.hs-map-popup-full .hs-map-gallery-main .property-popup-img {
    height: 280px;
    min-height: 280px;
}
.hs-map-popup-full .hs-map-details-col {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    max-height: 88vh;
}
.hs-map-popup-full .hs-map-inquiry-col {
    flex: 0 0 300px;
    min-width: 260px;
    background: #f7f9fc;
    padding: 12px;
    overflow-y: auto;
    max-height: 88vh;
}
.hs-map-popup-full .map-popup-detail-header {
    font-size: 16px;
    font-weight: 700;
    color: #0255a1;
    line-height: 1.25;
    margin: 0;
}
.hs-map-popup-full .map-popup-detail-location {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
    line-height: 1.3;
}
.hs-map-popup-full .map-popup-detail-type {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-top: 2px;
}
.hs-map-popup-full .map-popup-price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
}
.hs-map-popup-full .map-popup-price {
    font-size: 20px;
    font-weight: 700;
    color: #0255a1;
}
.hs-map-popup-full .map-popup-date {
    font-size: 13px;
    color: #6c757d;
    white-space: nowrap;
}
.hs-map-popup-full .hs-map-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    margin: 6px 0 8px;
    font-size: 12px;
    color: #475569;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}
.hs-map-popup-full .hs-map-stats-row strong { font-weight: 700; }
.hs-map-popup-full .hs-map-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #161e2d;
    margin: 8px 0 4px;
}
.hs-map-popup-full .hs-map-section-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
    line-height: 1.4;
}
.hs-map-popup-full .hs-map-description {
    font-size: 13px;
    line-height: 1.55;
    color: #334155;
    max-height: 120px;
    overflow-y: auto;
    margin-bottom: 10px;
    white-space: pre-line;
}
.hs-map-popup-full .hs-map-tabs-scroll {
    overflow-x: auto;
    scrollbar-width: none;
}
.hs-map-popup-full .hs-map-tabs-scroll::-webkit-scrollbar { display: none; }
.hs-map-popup-full .hs-map-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e2e8f0;
    min-width: max-content;
}
.hs-map-popup-full .hs-map-tab-btn {
    border: none;
    background: transparent;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 12px;
    color: #64748b;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    white-space: nowrap;
}
.hs-map-popup-full .hs-map-tab-btn.active {
    color: #0255a1;
    border-bottom-color: #0255a1;
}
.hs-map-popup-full .hs-map-tab-panel {
    display: none;
    padding: 10px 0;
    font-size: 12px;
}
.hs-map-popup-full .hs-map-tab-panel.active { display: block; }
@media (min-width: 992px) {
    .map-housesigma .property-popup.hs-map-popup-full {
        width: 960px;
        max-width: calc(100vw - 40px);
        min-height: 420px;
        max-height: 86vh;
    }
    .map-housesigma .hs-map-popup-full .hs-map-details-col {
        max-height: 86vh;
    }
    .map-housesigma .hs-map-popup-full .hs-map-inquiry-col {
        max-height: 86vh;
    }
    .map-housesigma .hs-map-popup-full .hs-map-tab-panel.active {
        max-height: 240px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .map-housesigma .hs-map-popup-full .hs-map-description {
        max-height: 96px;
    }
}
.hs-map-popup-full .hs-map-facts-grid,
.hs-map-popup-full .hs-map-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px 12px;
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px;
}
.hs-map-popup-full .fact-label {
    display: block;
    font-size: 11px;
    color: #64748b;
}
.hs-map-popup-full .fact-value {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
}
.hs-map-popup-full .hs-map-group-title {
    grid-column: 1 / -1;
    font-size: 12px;
    font-weight: 700;
    color: #0255a1;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid #e2e8f0;
}
.hs-map-popup-full .hs-map-group-title:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}
.hs-map-popup-full .hs-map-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}
.hs-map-popup-full .hs-map-table th,
.hs-map-popup-full .hs-map-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.hs-map-popup-full .hs-map-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
}
.hs-map-inquiry-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
    border: 1px solid #e5eaf1;
}
.hs-map-consult-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.hs-map-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.hs-map-form-row .hs-map-form-input {
    margin: 0;
}
.hs-map-form-title {
    font-size: 16px;
    font-weight: 700;
    color: #0255a1;
    margin: 0;
}
.hs-map-form-subtitle {
    font-size: 11px;
    color: #64748b;
    margin: 0 0 6px;
    line-height: 1.4;
}
.hs-map-form-input {
    width: 100%;
    padding: 9px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 12px;
    box-sizing: border-box;
}
.hs-map-form-input:focus {
    outline: none;
    border-color: #0255a1;
    box-shadow: 0 0 0 3px rgba(2, 85, 161, 0.12);
}
.hs-map-form-submit {
    background: #0255a1;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
}
.hs-map-form-submit:hover { background: #013d74; }
.hs-map-form-msg { font-size: 11px; padding: 6px; border-radius: 6px; }
.hs-map-form-msg.success { background: #dcfce7; color: #166534; }
.hs-map-form-msg.error { background: #fee2e2; color: #991b1b; }

/* ===== Popup top action buttons (share / full screen / wishlist) ===== */
.hs-map-popup-full .hs-map-actions {
    position: sticky;
    top: 0;
    z-index: 6;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding: 4px 0 8px;
    background: #fff;
}
.hs-map-popup-full .hs-map-action-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dbe2ea;
    border-radius: 50%;
    background: #fff;
    color: #0255a1;
    cursor: pointer;
    padding: 0;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.hs-map-popup-full .hs-map-action-btn:hover {
    background: #0255a1;
    color: #fff;
    border-color: #0255a1;
}
.hs-map-popup-full .hs-map-action-btn.active,
.hs-map-popup-full .hs-map-wishlist-btn.active {
    color: #e63946;
    border-color: #f2b8bd;
}
.hs-map-popup-full .hs-map-action-btn.active:hover {
    background: #e63946;
    color: #fff;
    border-color: #e63946;
}

/* Pin the inquiry form so property details scroll independently (desktop) */
@media (min-width: 992px) {
    .hs-map-popup-full .hs-map-inquiry-col {
        overflow: visible;
    }
    .hs-map-popup-full .hs-map-inquiry-card {
        position: sticky;
        top: 10px;
    }
}
.hs-map-popup-full .map-popup-details-btn {
    display: block;
    margin-top: 12px;
    text-align: center;
    background: #0255a1;
    color: #fff !important;
    padding: 10px 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none !important;
}
.hs-map-popup-loading {
    font-size: 12px;
    color: #64748b;
    padding: 20px 0;
    text-align: center;
}
.hs-map-locked-box {
    padding: 16px;
    background: rgba(0,0,0,0.02);
    border: 1px dashed rgba(0,0,0,0.15);
    border-radius: 8px;
    text-align: center;
    font-size: 12px;
}
@media (max-width: 991px) {
    .maplibregl-popup.hs-map-mobile-sheet,
    .maplibregl-popup.hs-map-mobile-popup,
    .maplibregl-popup.hs-map-cluster-mobile-popup,
    .map-housesigma .maplibregl-popup {
        position: fixed !important;
        top: auto !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        height: auto !important;
        max-height: 88vh !important;
        transform: none !important;
        z-index: 10050 !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .maplibregl-popup.hs-map-mobile-sheet .maplibregl-popup-content,
    .maplibregl-popup.hs-map-mobile-popup .maplibregl-popup-content,
    .maplibregl-popup.hs-map-cluster-mobile-popup .maplibregl-popup-content,
    .map-housesigma .maplibregl-popup-content {
        width: 100% !important;
        max-width: 100vw !important;
        border-radius: 16px 16px 0 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        max-height: 88vh !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
    }

    .map-housesigma .hs-map-cluster-mobile-popup .clusterpopup {
        max-height: none !important;
        height: 100%;
        min-height: 0;
    }

    .map-housesigma .hs-map-cluster-mobile-popup .hs-cluster-popup-list {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
    }

    @media (max-width: 768px) {
        .maplibregl-popup.hs-map-mobile-sheet,
        .maplibregl-popup.hs-map-mobile-popup,
        .maplibregl-popup.hs-map-cluster-mobile-popup,
        .map-housesigma .maplibregl-popup {
            bottom: calc(52px + env(safe-area-inset-bottom, 0px)) !important;
            max-height: calc(100dvh - 60px - 52px - env(safe-area-inset-bottom, 0px)) !important;
        }

        .maplibregl-popup.hs-map-mobile-sheet .maplibregl-popup-content,
        .maplibregl-popup.hs-map-mobile-popup .maplibregl-popup-content,
        .maplibregl-popup.hs-map-cluster-mobile-popup .maplibregl-popup-content,
        .map-housesigma .maplibregl-popup-content {
            max-height: calc(100dvh - 60px - 52px - env(safe-area-inset-bottom, 0px)) !important;
        }
    }

    .maplibregl-popup-tip {
        display: none !important;
    }

    .property-popup.hs-map-popup-full {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        max-height: none !important;
        flex-direction: column !important;
        overflow-x: hidden !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .hs-map-popup-full .hs-map-gallery-col,
    .hs-map-popup-full .hs-map-inquiry-col,
    .hs-map-popup-full .hs-map-details-col {
        flex: none !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        border-right: none !important;
        max-height: none !important;
        overflow: visible !important;
        box-sizing: border-box;
    }

    .hs-map-popup-full .hs-map-gallery-col .popup-img-div {
        width: 100% !important;
    }

    .hs-map-popup-full .hs-map-gallery-main img,
    .hs-map-popup-full .hs-map-gallery-main .property-popup-img {
        height: 210px !important;
        min-height: 210px !important;
        width: 100% !important;
        object-fit: cover;
    }

    .hs-map-popup-full .hs-map-details-col {
        padding: 12px 14px !important;
        overflow-y: visible !important;
    }

    .hs-map-popup-full .hs-map-inquiry-col {
        padding: 12px 14px 18px !important;
        border-top: 1px solid #e2e8f0;
    }

    .hs-map-popup-full .hs-map-facts-grid,
    .hs-map-popup-full .hs-map-details-grid {
        grid-template-columns: 1fr !important;
    }

    .hs-map-popup-full .hs-map-tabs-scroll {
        overflow-x: auto;
        max-width: 100%;
    }

    .hs-map-popup-full .hs-map-tab-panel {
        overflow-x: auto;
        max-width: 100%;
    }
    .hs-map-popup-full .hs-map-tab-panel.active {
        max-height: 42vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .hs-map-popup-full .hs-map-table {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .hs-map-popup-full .hs-map-table th,
    .hs-map-popup-full .hs-map-table td {
        white-space: nowrap;
    }

    .map-housesigma .maplibregl-popup-close-button {
        border: 0 !important;
        font-size: 22px;
        right: 8px;
        top: 8px;
        z-index: 20;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        line-height: 30px;
    }
}

.property-popup:not(.hs-map-popup-full) {
    margin-top:10px;
zoom:0.75;
                            width: 700px;
                            display: flex;
                            background: #fff;
                            box-shadow: 5px 5px 5px lightgray;
                            border-radius: 14px;
                            padding: 12px;
                            font-family: Arial, sans-serif;
                            align-items: stretch;
                            gap: 16px;
}

.property-popup-img{
  width: 100%;
                                    height: 170px;
                                    object-fit: cover;
                                    border-radius: 10px;
}
.hs-map-popup-gallery-wrap {
    position: relative;
    flex: 0 0 240px;
    min-width: 220px;
}
.hs-map-popup-gallery {
    display: flex;
    flex-direction: column;
    gap: 8px;
    height: 100%;
}
.hs-map-popup-gallery .hs-map-gallery-main {
    position: relative;
    flex: 1;
    min-height: 170px;
}
.hs-map-popup-gallery .hs-map-gallery-main img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 10px;
    display: block;
    cursor: pointer;
}
.hs-map-see-all-photos {
    position: absolute;
    bottom: 8px;
    left: 8px;
    border: none;
    background: var(--primary-color, #db1d23);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 16px;
    cursor: pointer;
    z-index: 3;
}
.hs-map-gallery-thumbs {
    display: flex;
    gap: 5px;
    overflow-x: auto;
    padding-bottom: 2px;
    scrollbar-width: thin;
}
.hs-map-gallery-thumbs img {
    width: 48px;
    height: 36px;
    object-fit: cover;
    border-radius: 5px;
    cursor: pointer;
    opacity: 0.55;
    border: 2px solid transparent;
    flex-shrink: 0;
}
.hs-map-gallery-thumbs img.active {
    opacity: 1;
    border-color: #9dbdfd;
}
.hs-map-gallery-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.92);
    color: #0255a1;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    z-index: 2;
}
.hs-map-gallery-nav.prev { left: 6px; }
.hs-map-gallery-nav.next { right: 6px; }
.hs-map-gallery-counter {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,0.65);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 12px;
    z-index: 2;
}
.property-popup-sale{
position: absolute;
                                bottom: 12px;
                                left: 12px;
                                background: #d1ecf1;
                                color: #0c5460;
                                padding: 6px 14px;
                                border-radius: 20px;
                                font-size: 16px;
                                font-weight: 500;
								}
								
	.property-popup-icon{							
								display: flex;
                                align-items: center;
                                gap: 18px;
                                margin-top: 10px;
                                font-size: 16px;
                                color: #495057;
								}
	.property-popup-footer{							
								
                                font-size: 16px;
                                color: #6c757d;
								}
								
								
			
			
			/* Dropdown Container */
.watched-dropdown .dropdown-menu {
    width: 380px;
    padding: 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    background: #fff;
    z-index: 200;
}

/* Header */
.watched-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
}
.watched-wrapper{
     max-height: 450px;
    overflow-y: scroll;
}

/* Card */
.watched-card {
    padding: 20px;
}

.watched-card.active {
    background: #f0f6ff;
    border-left: 4px solid #0255a1;
}

.watched-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 4px;
}

.watched-sub {
    color: #777;
    margin-bottom: 14px;
}

/* Map Preview */
.watched-map img {
    width: 100%;
    height: 180px;
    border-radius: 14px;
    object-fit: cover;
    margin-bottom: 16px;
}

/* Buttons */
.watched-actions {
    display: flex;
    gap: 12px;
}

.btn-outline {
    flex: 1;
    padding: 10px;
    border-radius: 12px;
    border: 2px solid #0255a1;
    background: transparent;
    color: #0255a1;
    font-weight: 600;
    cursor: pointer;
}

.btn-filled {
    flex: 1;
    padding: 10px;
    border-radius: 12px;
    border: none;
    background: #0255a1;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}

/* Bottom New Button */
.new-area {
    padding: 20px;
}

.btn-new {
    width: 100%;
    padding: 14px;
    border-radius: 14px;
    border: none;
    background: #0255a1;
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
}



.polygon-popup {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: none;
    z-index: 9999;
}

.popup-card {
    background: white;
    padding: 20px 30px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    text-align: center;
}

.popup-actions {
    margin-top: 15px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.map-mobile-view{
     height: 80vh;
}


@media (max-width: 991px) {
    .hs-menu{
        display:none;
    }
    .footer-center{
         display:none;
    }
    .map-mobile-view{
         height: 90vh;
    }
    
    
    .search-dropdown{
        width: 120%;
    left: -20%;
    }
    
    .hs-topbar{
  
}
}
.clusterpopup{
    max-height: min(520px, 80vh) !important;
    height: auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
}
.hs-cluster-popup-header {
    padding: 14px 14px 10px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    flex: 0 0 auto;
}
.hs-cluster-popup-header h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}
.hs-cluster-popup-header p {
    margin: 4px 0 0;
    font-size: 12px;
    color: #64748b;
}
.hs-cluster-popup-list {
    padding: 12px 12px 28px; /* extra bottom so last card is never clipped */
    flex: 1 1 auto;
    min-height: 0;
    max-height: none;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-gutter: stable;
}
.hs-map-cluster-popup .maplibregl-popup-content {
    padding: 0 !important;
    overflow: hidden;
    max-height: min(520px, 80vh);
}
.hs-cluster-list-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 14px rgba(2, 85, 161, 0.06);
    cursor: pointer;
    box-sizing: border-box;
}
.hs-cluster-list-item:last-child {
    margin-bottom: 4px;
}
.hs-cluster-card-img {
    position: relative;
    width: 96px;
    min-width: 96px;
    height: 76px;
    border-radius: 10px;
    overflow: hidden;
    background: #e2e8f0;
    flex: 0 0 auto;
}
.hs-cluster-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.hs-cluster-card-img.hs-img-empty,
.hs-img-empty-fill {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg,#e2e8f0 0%,#eef2f7 100%);
}
.hs-img-empty-fill::after {
    content: "\1F3E0";
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 26px;
    opacity: .35;
}
.hs-cluster-card-badge {
    position: absolute;
    left: 6px;
    bottom: 6px;
    background: rgba(255,255,255,0.92);
    color: #0255a1;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 999px;
}
.hs-cluster-card-body {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.hs-cluster-card-top {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    align-items: flex-start;
}
.hs-cluster-card-price {
    font-size: 15px;
    font-weight: 700;
    color: #0255a1;
    line-height: 1.2;
}
.hs-cluster-card-date {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
}
.hs-cluster-card-title {
    margin-top: 4px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.hs-cluster-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
    font-size: 11px;
    color: #475569;
}
.hs-cluster-card-footer {
    margin-top: 6px;
    font-size: 10px;
    color: #94a3b8;
    line-height: 1.3;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.property-popup:not(.hs-map-popup-full) .popupspace{
    flex: 1; display: flex !important;
}
.property-popup:not(.hs-map-popup-full) .popup-img-div{
    width: 240px; position: relative;
}

@media (max-width: 991px) {
    .property-popup:not(.hs-map-popup-full) .popupspace{
    
      gap:10px;
}

    .clusterpopup{
        max-height:100% !important;
    }

    /* Legacy compact popup (cluster list items) */
    .property-popup:not(.hs-map-popup-full) {
        border-radius: 0 !important;
        box-shadow: 0 25px 70px rgba(0,0,0,0.3) !important;
        padding: 0 !important;
    }

    .property-popup:not(.hs-map-popup-full) .property-popup-img {
        height: 220px !important;
        border-radius: 0 !important;
    }
}

@media (max-width: 768px) {
    .map-housesigma #map {
        height: 100% !important;
        min-height: 0 !important;
    }
}

@media (max-width: 768px) {

    .map-housesigma .property-popup:not(.hs-map-popup-full) {
        zoom: 1 !important;
        box-shadow: 0 25px 70px rgba(0,0,0,0.3) !important;
    }

    .property-popup:not(.hs-map-popup-full) .popup-img-div {
        width: 33%;
        position: relative;
    }

    .property-popup:not(.hs-map-popup-full) .property-popup-img {
        width: 100% !important;
        height: 180px !important;
        object-fit: cover;
    }

    .property-popup:not(.hs-map-popup-full) .popupspace {
        padding: 10px !important;
    }
}


@media (max-width: 991px) {

    #propertyModal {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 100000;
        background: #fff;
    }

    html.hs-property-modal-open,
    html.hs-property-modal-open body {
        overflow: hidden !important;
        height: 100%;
    }

    #propertyModal .modal-content {
        display: flex;
        flex-direction: column;
        position: fixed;
        inset: 0;
        top: 0;
        left: 0;
        transform: none;
        width: 100%;
        height: 100dvh;
        max-height: 100dvh;
        border-radius: 0;
        overflow: hidden;
        touch-action: manipulation;
    }

    #propertyFrame {
        flex: 1 1 auto;
        min-height: 0;
        width: 100%;
        height: 100% !important;
        border: none;
        display: block;
        touch-action: pan-y;
    }

    #propertyModal .close-modal {
        position: fixed;
        right: 10px;
        top: calc(env(safe-area-inset-top, 0px) + 8px);
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.92);
        color: #0f172a;
        font-size: 24px;
        line-height: 1;
        z-index: 100000;
    }

    #iframeLoader {
        position: absolute;
        inset: 0;
        z-index: 2;
    }
}

.logo-form-back{
    display:none;
    font-size: 30px;
}
.logo_form{
        display:block;
    }

@media (max-width: 768px) {
    .maplibregl-popup-content {
        animation: slideUp 0.25s ease;
    }
    .logo_form{
        display:none;
    }
    .logo-form-back{
    display:block;
    font-size: 30px;
}

    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
}



.map-count-box {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 10;

    background: #fff;
    padding: 8px 14px;
    border-radius: 8px;

    font-weight: 600;
    font-size: 14px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}




@media (max-width: 768px) {
    
    
    .map-count-box {
        zoom: 1;
    }
    
    
    .hs-topbar{
    position: relative;
    width: 100%;
    left: 0;
    transform: none !important; /* MUST */
}

    .dropdown-menu {
        position: fixed !important;

        top: 50% !important;
        left: 50% !important;

        transform: translate(-50%, -50%) !important;

        width: 92% !important;
        max-width: 420px; /* nice centered card */
        max-height: 80vh;

        background: #fff;
        border-radius: 16px;
        box-shadow: 0 25px 70px rgba(0,0,0,0.3);

        z-index: 999999 !important;
        display: none;
        overflow-y: auto;
        padding: 20px;
    }

    .dropdown-menu.active-mobile {
        display: block !important;
    }

#hsMobileSheetsRoot {
    position: relative;
    z-index: 1000002;
}

#hsMobileSheetsRoot .hs-m-sheet.open {
    z-index: 1000003;
}

    .mobile-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 1000001;
        display: none;
    }

    .mobile-overlay.active {
        display: block;
    }

    /* Header */
    .fullscreen-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .fullscreen-close {
        font-size: 22px;
        cursor: pointer;
    }
}

/* ===== HouseSigma split filter (Active + date) ===== */
.hs-split-dropdown-wrap {
    position: relative;
    z-index: 1200;
    flex: 0 1 auto;
    min-width: 140px;
    max-width: 220px;
}

.hs-split-dropdown-wrap .dropdown-menu {
    z-index: 1201;
}

/* Prevent filter chips from colliding with the search row on mid-size desktops */
@media (min-width: 992px) and (max-width: 1399px) {
    .filter-group {
        flex-wrap: wrap;
        row-gap: 8px;
    }

    .filter-group > .filter-btn,
    .filter-group > .dropdown,
    .filter-group > .hs-split-filter,
    .filter-group > .hs-split-dropdown-wrap {
        flex: 0 1 auto;
        min-width: 120px;
    }

    .hs-menu {
        gap: 14px;
        flex-shrink: 1;
    }

    .hs-menu a {
        font-size: 13px;
        white-space: nowrap;
    }

    .hs-search {
        min-width: 260px;
    }
}

@media (min-width: 1400px) {
    .hs-search {
        min-width: 320px;
    }

    .smart-search {
        max-width: 640px;
    }
}

/* Desktop: compact, clean date dropdown (Active / Sold / De-listed) */
@media (min-width: 992px) {
    .hs-split-dropdown-wrap .dropdown-menu {
        min-width: 230px;
        padding: 6px;
        border-radius: 12px;
        border: 1px solid #e6eaf0;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
    }

    .hs-split-dropdown-wrap .dropdown-card {
        width: 100%;
        min-width: 218px;
        padding: 2px;
    }

    .hs-split-dropdown-wrap .radio-item {
        margin-bottom: 2px;
        font-size: 13.5px;
        padding: 9px 10px;
        border-radius: 8px;
        transition: background .15s ease, color .15s ease;
    }

    .hs-split-dropdown-wrap .radio-item:last-child {
        margin-bottom: 0;
    }

    .hs-split-dropdown-wrap .radio-item:hover {
        background: #f4f7fc;
    }

    .hs-split-dropdown-wrap .radio-item.selected {
        background: #eef4ff;
        font-weight: 600;
    }

    .hs-split-dropdown-wrap .custom-radio {
        width: 16px;
        height: 16px;
        margin-right: 10px;
        flex: 0 0 auto;
    }
}

.hs-map-status-bar {
    position: relative;
    z-index: 1100;
}

.hs-split-filter {
    display: flex;
    align-items: stretch;
    height: 34px;
    min-width: 0;
}

.hs-split-filter .hs-split-label {
    display: flex;
    align-items: center;
    padding: 0 12px;
    background: var(--hs-primary);
    color: #fff;
    border: 1px solid var(--hs-primary);
    border-radius: 7px 0 0 7px;
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
}

.hs-split-filter .hs-split-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-width: 80px;
    height: 34px;
    min-height: 34px;
    padding: 0 10px;
    background: #e8f0fb;
    color: #333;
    border: 1px solid var(--hs-primary);
    border-left: none;
    border-radius: 0 7px 7px 0;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    box-sizing: border-box;
}

.hs-split-filter.actived .hs-split-value,
.hs-split-filter .hs-split-value.active {
    background: #e8f0fb;
    color: #333;
}

.hs-split-caret {
    color: #b3b3b3;
    font-size: 12px;
    line-height: 1;
}

.hs-split-filter--mobile {
    flex: 1.2;
    min-width: 0;
}

.hs-split-filter--mobile .hs-split-label {
    font-size: 13px;
    padding: 0 10px;
}

.hs-split-filter--mobile .hs-split-value {
    font-size: 13px;
    min-width: 0;
    flex: 1;
}

/* ===== List sidebar (desktop) + mobile list view ===== */
.hs-list-toggle-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 1000;
    display: none;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 13px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    cursor: pointer;
    pointer-events: auto;
}

.hs-list-bar-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.hs-list-bar-btn .hs-list-icon {
    font-size: 16px;
    line-height: 1;
}

.hs-list-sidebar {
    position: relative;
    order: 2;
    flex: 0 0 0;
    width: 0;
    min-width: 0;
    height: 100%;
    background: #fff;
    z-index: 20;
    display: none;
    flex-direction: column;
    overflow: hidden;
    transition: flex-basis 0.28s ease, width 0.28s ease, min-width 0.28s ease;
    box-shadow: -4px 0 24px rgba(0,0,0,.12);
}

.hs-list-sidebar.open,
.map-search-wrapper.list-open .hs-list-sidebar {
    display: flex;
    flex: 0 0 min(400px, 38vw);
    width: min(400px, 38vw);
    min-width: min(400px, 38vw);
}

.map-search-wrapper.list-open #map {
    flex: 1 1 auto;
    min-width: 0;
}

.hs-list-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    flex-shrink: 0;
}

.hs-list-sidebar-close {
    border: none;
    background: transparent;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}

.hs-list-sidebar-body,
.hs-mobile-list-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 8px;
}

.hs-list-item {
    position: relative;
    border-bottom: 1px solid #f0f0f0;
}

.hs-list-item.sold-locked {
    cursor: pointer;
}

.hs-list-item.sold-locked .map-sold-login-gate {
    pointer-events: auto;
}

.hs-list-item.sold-locked .hs-list-card {
    pointer-events: none;
}

.hs-list-card.blurred-content {
    pointer-events: none;
    user-select: none;
}

.hs-list-card {
    display: flex;
    gap: 10px;
    padding: 10px;
    cursor: pointer;
    position: relative;
    width: 100%;
    box-sizing: border-box;
    border-bottom: none;
}

.hs-list-card:hover {
    background: #f8fafc;
}

.hs-list-card img {
    width: 100px;
    min-width: 100px;
    height: 76px;
    object-fit: cover;
    border-radius: 8px;
}

.hs-list-card-body {
    flex: 1;
    min-width: 0;
}

.hs-list-card-price {
    color: var(--hs-primary);
    font-weight: 700;
    font-size: 16px;
}

.hs-list-card-addr {
    font-size: 14px;
    font-weight: 500;
    margin: 4px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hs-list-card-meta {
    font-size: 12px;
    color: #666;
}

.hs-list-empty {
    text-align: center;
    color: #888;
    padding: 24px 12px;
}

.hs-mobile-list-panel {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    background: #fff;
    width: 100%;
}

.hs-mobile-list-header {
    padding: 10px 14px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    flex-shrink: 0;
}
.hs-mobile-list-body {
    padding: 10px 10px 90px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.hs-mobile-view-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10050;
    display: flex;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    box-shadow: 0 -2px 12px rgba(0,0,0,.08);
    padding-bottom: env(safe-area-inset-bottom, 0);
}

.hs-view-bar-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 10px 8px;
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
}

.hs-view-bar-btn.active {
    color: var(--hs-primary);
}

.hs-view-bar-btn span:first-child {
    font-size: 18px;
    line-height: 1;
}

.map-housesigma.view-list .map-search-wrapper > #map,
.map-housesigma.view-list .map-search-wrapper > .hs-map-status-bar,
.map-housesigma.view-list .map-search-wrapper > .map-count-box,
.map-housesigma.view-list .hs-list-toggle-btn {
    display: none !important;
}

    .map-housesigma.view-list .hs-mobile-list-panel {
        display: flex !important;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    .map-housesigma.view-list .hs-mobile-list-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
    }

@media (min-width: 992px) {
    .hs-mobile-view-bar,
    .hs-mobile-list-panel {
        display: none !important;
    }

    .hs-list-bar-btn {
        display: inline-flex !important;
    }

    .map-housesigma .map-search-wrapper {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        overflow: hidden;
        height: calc(100dvh - 168px);
        min-height: 480px;
        position: relative;
    }

    .map-housesigma .map-search-wrapper #map {
        order: 1;
        flex: 1 1 auto;
        min-width: 0;
        height: 100% !important;
    }

    .hs-list-sidebar {
        display: none;
    }

    .map-search-wrapper.list-open .hs-list-sidebar,
    .hs-list-sidebar.open {
        display: flex !important;
    }

    .map-housesigma.view-list .map-search-wrapper > #map {
        display: block !important;
    }
}

@media (max-width: 991px) {
    .hs-list-bar-btn {
        display: none !important;
    }

    .hs-list-sidebar {
        display: none !important;
    }
}

/* ===== HouseSigma mobile layout ===== */
:root {
    --hs-primary: #0255a1;
    --hs-header: #9dbdfd;
    --hs-cluster: #ff5722;
}

@media (max-width: 991px) {
    .hs-topbar {
        background: var(--hs-header) !important;
        box-shadow: none;
        padding-bottom: 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    body:has(.map-housesigma) .mobile-bottom-nav {
        display: none !important;
    }

    body:has(.map-housesigma) {
        padding-bottom: calc(52px + env(safe-area-inset-bottom, 0px)) !important;
    }

    .map-housesigma {
        min-height: calc(100dvh - 60px);
    }

    .map-housesigma .map-mobile-view {
        height: calc(100dvh - 60px - 52px - env(safe-area-inset-bottom, 0px));
        min-height: 300px;
        flex-direction: column !important;
        overflow: hidden;
    }

    .map-housesigma .map-search-wrapper {
        position: relative;
        flex: 1;
        min-height: 0;
    }

    .map-housesigma #map {
        position: relative;
        flex: 1 1 auto;
        width: 100% !important;
        min-height: 0 !important;
        height: 100% !important;
        overflow: hidden;
    }

    .map-housesigma #property-list {
        display: none !important;
    }

    .map-housesigma .map-count-box {
        position: absolute;
        top: auto !important;
        bottom: 88px;
        left: 10px;
        zoom: 1;
        font-size: 12px;
        padding: 7px 12px;
        z-index: 6;
        max-width: calc(100% - 70px);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .map-housesigma .maplibregl-ctrl-bottom-right,
    .map-housesigma .maplibregl-ctrl-top-right {
        top: auto !important;
        bottom: 88px !important;
        right: 10px !important;
        z-index: 6 !important;
    }

    .map-housesigma .hs-map-status-bar {
        top: 8px;
        z-index: 6;
    }

    .map-housesigma .clusterpopup {
        width: 100% !important;
        max-width: 100% !important;
        max-height: 80vh !important;
        box-sizing: border-box;
        overflow-x: hidden;
        padding: 0;
        border-radius: 16px 16px 0 0;
        background: #fff;
        display: flex;
        flex-direction: column;
    }

    .map-housesigma .hs-map-cluster-mobile-popup .maplibregl-popup-content,
    .map-housesigma .hs-map-cluster-mobile-popup {
        width: 100vw !important;
        max-width: 100vw !important;
        padding: 0 !important;
        max-height: none;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
        overscroll-behavior: contain;
    }

    .map-housesigma .hs-map-cluster-mobile-popup .clusterpopup {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none !important;
        height: auto;
        overflow: hidden;
    }

    .map-housesigma .hs-cluster-popup-list {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none !important;
        overflow-y: auto !important;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
        padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px));
    }

    .map-housesigma .hs-cluster-list-item {
        flex-direction: column;
        padding: 12px;
        margin-bottom: 12px;
    }

    .map-housesigma .hs-cluster-card-img {
        width: 100%;
        min-width: 0;
        height: 150px;
    }

    .map-housesigma .hs-cluster-card-badge {
        font-size: 11px;
        padding: 4px 9px;
    }

    .map-housesigma .maplibregl-popup-content .property-popup {
        max-width: 100% !important;
        box-sizing: border-box;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-item {
        margin-bottom: 10px;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 20px rgba(2, 85, 161, 0.08);
        gap: 10px;
        padding: 10px;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-card img {
        width: 112px;
        min-width: 112px;
        height: 88px;
        border-radius: 10px;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-card-price {
        font-size: 15px;
        line-height: 1.25;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-card-addr {
        font-size: 13px;
        line-height: 1.35;
        white-space: normal;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .map-housesigma .hs-mobile-list-body .hs-list-card-meta {
        font-size: 12px;
        color: #475569;
        line-height: 1.3;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) {
        width: 100% !important;
        display: block !important;
        padding: 0 !important;
        margin-top: 0 !important;
        border-radius: 14px !important;
        overflow: hidden !important;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .popup-img-div {
        width: 100% !important;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .property-popup-img {
        width: 100% !important;
        height: 190px !important;
        object-fit: cover;
        border-radius: 0 !important;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .popupspace,
    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .hs-mobile-popup-content {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        padding: 12px !important;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .property-popup-icon {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        font-size: 12px;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .property-popup-footer {
        font-size: 11px;
        line-height: 1.35;
        word-break: break-word;
    }

    .map-housesigma .maplibregl-popup-content .property-popup:not(.hs-map-popup-full) .btn-view {
        width: 100%;
        text-align: center;
        border-radius: 9px;
        padding: 9px 12px;
        font-size: 13px;
    }

    .hs-row-1 {
        padding: 8px 10px !important;
        gap: 8px;
    }

    .hs-row-2 {
        display: none !important;
    }

    .hs-brand .logo_form {
        display: none;
    }

    .hs-brand .logo-form-back {
        display: block !important;
        color: #fff;
        font-size: 28px;
        line-height: 1;
        text-decoration: none;
        padding: 0 4px;
    }

    .hs-search {
        flex: 1;
        min-width: 0;
    }

    .smart-search {
        max-width: none;
        min-width: 0;
        width: 100%;
    }

    .search-box {
        background: #fff;
        border-radius: 8px;
        padding: 8px 10px;
    }

    .search-box input {
        font-size: 14px;
    }

    .search-box input::placeholder {
        color: #9ca3af;
    }

    .hs-mob-watch {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #000;
        font-size: 10px;
        padding: 2px 6px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .hs-mob-watch svg {
        width: 20px;
        height: 20px;
        margin-bottom: 2px;
    }

    .hs-mobile-filters {
        display: flex;
        gap: 8px;
        padding: 0 10px 10px;
        align-items: stretch;
    }

    .hs-mobile-filters .hs-m-btn {
        flex: 1 1 0;
        min-width: 0;              /* allow shrink so ellipsis works */
        background: rgba(255,255,255,0.96);
        border: none;
        border-radius: 8px;
        padding: 9px 8px;
        font-size: 13px;
        font-weight: 500;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
    }

    /* Middle status control: keep label fixed, truncate the value text */
    .hs-mobile-filters .hs-split-filter--mobile {
        flex: 1 1 0;
        min-width: 0;
    }

    .hs-mobile-filters .hs-split-filter--mobile .hs-split-label {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .hs-mobile-filters .hs-split-filter--mobile .hs-split-value {
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        line-height: 32px;
        text-align: center;
    }

    .hs-map-status-bar {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 6;
        display: flex;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,.12);
        padding: 3px;
        width: calc(100% - 20px);
        max-width: 340px;
    }

    .hs-map-status-bar .hs-m-status {
        flex: 1;
        border: none;
        background: transparent;
        padding: 8px 4px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .hs-map-status-bar .hs-m-status.active {
        background: var(--hs-primary);
        color: #fff;
    }

    .map-search-wrapper {
        position: relative;
    }

    .map-housesigma .map-count-box {
        pointer-events: none;
    }

    .hs-m-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        background: #fff;
        border-radius: 16px 16px 0 0;
        z-index: 1000001;
        max-height: 88vh;
        transform: translateY(110%);
        transition: transform 0.28s ease;
        display: flex;
        flex-direction: column;
        box-shadow: 0 -8px 30px rgba(0,0,0,0.15);
    }

    .hs-m-sheet.open {
        transform: translateY(0);
    }

    .hs-m-sheet-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        font-weight: 600;
        font-size: 16px;
    }

    .hs-m-sheet-close {
        border: none;
        background: transparent;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .hs-m-sheet-body {
        overflow-y: auto;
        padding: 12px 16px 20px;
        -webkit-overflow-scrolling: touch;
    }

    .hs-m-option {
        padding: 12px 4px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 15px;
        cursor: pointer;
    }

    .hs-m-option.activated {
        color: var(--hs-primary);
        font-weight: 600;
    }

    .hs-m-date-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .hs-m-date-columns .column-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .hs-m-radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 2px;
        font-size: 12px;
        cursor: pointer;
    }

    .hs-m-radio-option .dot {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        border: 2px solid #bbb;
        flex-shrink: 0;
        position: relative;
    }

    .hs-m-radio-option.selected .dot {
        border-color: var(--hs-primary);
    }

    .hs-m-radio-option.selected .dot::after {
        content: '';
        position: absolute;
        inset: 3px;
        background: var(--hs-primary);
        border-radius: 50%;
    }

    .hs-m-filter-section {
        margin-bottom: 16px;
    }

    .hs-m-filter-section .header {
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .hs-m-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 0;
        margin: 0;
    }

    .hs-m-chips li {
        list-style: none;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 13px;
        cursor: pointer;
    }

    .hs-m-chips li.selected {
        background: var(--hs-primary);
        border-color: var(--hs-primary);
        color: #fff;
    }

    .hs-m-sheet-actions {
        display: flex;
        gap: 10px;
        padding: 12px 16px 16px;
        border-top: 1px solid #eee;
    }

    .hs-m-sheet-actions button {
        flex: 1;
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
    }

    .hs-m-apply {
        background: var(--hs-primary);
        color: #fff;
    }

    .hs-m-clear {
        background: #f3f4f6;
        color: #374151;
    }
}

@media (min-width: 992px) {
    .hs-mobile-filters,
    .hs-mob-watch,
    .hs-map-status-bar {
        display: none !important;
    }
}

#hsMobileSheetsRoot .hs-m-sheet {
    display: none;
}

@media (max-width: 991px) {
    #hsMobileSheetsRoot .hs-m-sheet {
        display: flex;
    }
}

/* Final mobile safety overrides for map search popups */
@media (max-width: 991px) {
    .map-housesigma .maplibregl-popup-content {
        width: 100vw !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
    }

    .map-housesigma .maplibregl-popup-content .open-property.property-popup {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    .map-housesigma .maplibregl-popup-content .open-property.property-popup.hs-map-popup-full {
        display: flex !important;
        flex-direction: column !important;
    }

    .map-housesigma .maplibregl-popup-content .open-property.property-popup:not(.hs-map-popup-full) {
        display: block !important;
        zoom: 1 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .map-housesigma .maplibregl-popup-content .open-property.property-popup:not(.hs-map-popup-full) .popup-img-div {
        width: 100% !important;
        min-width: 0 !important;
    }

    .map-housesigma .maplibregl-popup-content .open-property.property-popup:not(.hs-map-popup-full) .popupspace {
        width: 100% !important;
        min-width: 0 !important;
        padding: 12px !important;
        overflow-wrap: anywhere;
    }
}
</style>

@php
    $serikRequestPath = trim(strtolower(request()->path()), '/');
    $isMapSearchPageView = request()->is('map')
        || request()->is('on/map')
        || request()->is('on/*/map')
        || (bool) preg_match('#^on/.+-for-(sale|lease)(?:-in-.+)?/map$#', $serikRequestPath)
        || (bool) preg_match('#^.+-for-(sale|lease)(?:-in-.+)?$#', $serikRequestPath);
@endphp

@if (! $isMapSearchPageView)
<section class="flat-map hero-banner-4" id="formMain">
    <div
        data-bb-toggle="list-map"
        id="map"
        style="min-height: 460px;"
        data-url="{{ route('public.ajax.properties.map') }}"
        data-tile-layer="{{ RealEstateHelper::getMapTileLayer() }}"
        data-center="{{ json_encode(RealEstateHelper::getMapCenterLatLng()) }}"
        data-max-zoom="{{ theme_option('map_max_zoom', '22') }}"
    ></div>

    @if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
        <div class="container">
            <div class="wrap-filter-search">
                @include(Theme::getThemeNamespace('views.real-estate.partials.search-box'), ['style' => 4, 'centeredTabs' => true])
            </div>
        </div>
    @endif
</section>
@endif



@if ($isMapSearchPageView)
<section class="flat-map hero-banner-4 map-housesigma" id="secondMain">

    <!-- ===== TOP BAR UI ===== -->
    <div class="hs-topbar">

        <div class="hs-row-1" style="padding:5px 14px;">
            <div class="hs-brand">
                <a href="{{ url('/') }}">{!! Theme::getLogoImage(['class' => 'logo_form'], maxHeight: 44) !!}
                <span class="logo-form-back" onclick="history.back();return false;">&#8249;</span>
                </a>
            
            </div>

            <div class="hs-search">
                
               <div class="smart-search">
                        <div class="search-box">
                            <i class="icon">🔍</i>
                            <input type="text" id="smartInput" placeholder="Address, Street Name or Listing#">
                            <span class="clear-btn" id="clearBtn">✕</span>
                        </div>

                            <div class="search-dropdown" id="searchDropdown">
                        
                                <!-- Locations -->
                                <div class="dropdown-section">
                                    <div class="section-title">Locations</div>
                                    <div id="locationResults"></div>
                                </div>
                        
                                <!-- Listings -->
                                <div class="dropdown-section">
                                    <div class="section-title">Listings</div>
                                    <div id="listingResults"></div>
                                </div>
                                <div id="dropdownLoader" class="dropdown-loader" style="display:none;">
                                    <div class="loader-spinner"></div>
                                    <span>Searching properties...</span>
                                </div>
                            <center> <button id="loadMoreBtn" class="show-more-btn tf-btn primary" style="width: 60%; padding: 5px 10px; display: block;margin-bottom:5px;">Load More</button></center>  
                            </div>
                        </div>
            </div>

            <button type="button" class="hs-mob-watch d-lg-none" id="hsMobWatchBtn" aria-label="Watch">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v3M15 3v3M3 9h18"/></svg>
                Watch
            </button>

            <div class="hs-menu">
                <a href="{{ url('/') }}">Home</a>
                  <a href="{{ url('mortgage-calculator') }}">
                    {{ __('Mortgage Calculator') }}
                   
                </a>
                <a href="{{ url('cash-back-calculator') }}">
                    {{ __('Cash Back Calculator') }}
                   
                </a>
                <!--a>Market Trends</a-->
                <a href="{{ url('/free-home-evaluation') }}">Home Evaluation</a>
                <a href="{{ url('agents') }}">Agents</a>
                <!--a>Tools</a-->
            </div>
        </div>

        <div class="hs-mobile-filters d-lg-none">
            <button type="button" class="hs-m-btn" id="hsMobPropertyBtn">All property &#9660;</button>
            <div class="hs-split-filter hs-split-filter--mobile actived" id="hsMobStatusSplit">
                <div class="hs-split-label" id="hsMobSplitLabel">Active</div>
                <button type="button" class="hs-split-value" id="hsMobDateBtn">All &#9660;</button>
            </div>
            <button type="button" class="hs-m-btn" id="hsMobFiltersBtn">Filters</button>
        </div>

        <div class="mobile-overlay" id="mobileOverlay"></div>
        <div class="hs-row-2 d-none d-lg-block">
            
            <div id="filterCollapse" class="collapse d-md-block">
               
                <div class="filter-bar"  >

                <!-- Sale Type -->
                <div class="filter-group">
                   <div class="dropdown">
                        <button class="filter-btn dropdown-toggle active"  id="transactionDropdown">
                            For Sale 
                        </button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item transaction-item" data-transaction="For Sale">For Sale</div>
                            <div class="dropdown-item transaction-item" data-transaction="For Lease">For Lease</div>
                        </div>
                    </div>
            
            
                    <div class="dropdown">
                        <button class="filter-btn dropdown-toggle property-selector">
                            All Properties
                        </button>
                        <div class="dropdown-menu " id="dropdown-menu-type">
                            <div class="dropdown-card">
                              <h3>Property Type</h3>
                              
                              <label class="checkbox-item">
                                <input type="checkbox" value="All">
                                <span class="custom-checkbox"></span>
                                All Properties
                            </label>
                            
                             <label class="checkbox-item">
                                <input type="checkbox" value="Att/Row/Townhouse">
                                <span class="custom-checkbox"></span>
                                Freehold Townhouse
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Condo Apartment">
                                <span class="custom-checkbox"></span>
                                Condo Apartment
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Condo Townhouse">
                                <span class="custom-checkbox"></span>
                                Condo Townhouse
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Detached">
                                <span class="custom-checkbox"></span>
                                Detached
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="Semi-Detached">
                                <span class="custom-checkbox"></span>
                                Semi-Detached
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Detached Condo">
                                <span class="custom-checkbox"></span>
                                Detached Condo
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Duplex">
                                <span class="custom-checkbox"></span>
                                Duplex
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Link">
                                <span class="custom-checkbox"></span>
                                Link
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Multiplex">
                                <span class="custom-checkbox"></span>
                                Multiplex
                            </label>
                            
                            <label class="checkbox-item">
                                <input type="checkbox" value="Other">
                                <span class="custom-checkbox"></span>
                                Other
                            </label>
                              <div class="actions">
                                <button class="btn-cancel">Cancel</button>
                                <button class="btn-save">Apply</button>
                              </div>
                            </div>
                        </div>
                    </div>
            
                   
            <div class="dropdown">
                        <button type="button" class="filter-btn filter-btn-price dropdown-toggle" id="hsPriceFilterBtn">
                           <span class="price-filter-btn-label">$0 - Max</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-min">
                            <div class="dropdown-card">
                              <h3>Price</h3>
                            
                              <div class="price-display">$0 - Max</div>
                            
                                <div class="range-wrapper">
                                    <input type="range" min="0" max="5000000" value="0" step="50000" class="slider slider-min">
                                    <input type="range" min="0" max="5000000" value="5000000" step="50000" class="slider slider-max" style="margin-top:10px;">
                                </div>
                            
                              <div class="price-scale">
                                <span>$0</span>
                                <span>$500K</span>
                                <span>$1M</span>
                                <span>$3M</span>
                                <span>$5M</span>
                                <span>Max</span>
                              </div>
                            
                              <div class="actions">
                                <button class="btn-cancel">Cancel</button>
                                <button class="btn-apply" style="background: #0255a1;color: #fff;">Apply</button>
                              </div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown hs-split-dropdown-wrap" id="hsSplitWrapActive">
                        <div class="hs-split-filter actived" data-split-status="active">
                            <div class="hs-split-label">Active</div>
                            <button type="button" class="hs-split-value filter-btn dropdown-toggle active" data-hs-date-toggle="sale">
                                <span class="hs-split-value-text" id="hsActiveDateLabel">All</span>
                                <span class="hs-split-caret">&#9660;</span>
                            </button>
                        </div>
                        <div class="dropdown-menu">
                            <div class="dropdown-card">

                              <label class="radio-item">
                                <input type="radio" name="date" value="last_1_day">
                                <span class="custom-radio"></span>
                                Last 1 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="last_3_day">
                                <span class="custom-radio"></span>
                                Last 3 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="last_7_day">
                                <span class="custom-radio"></span>
                                Last 7 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="last_30_day">
                                <span class="custom-radio"></span>
                                Last 30 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="last_90_day">
                                <span class="custom-radio"></span>
                                Last 90 days
                              </label>
                            
                              <label class="radio-item selected">
                                <input type="radio" name="date" checked value="all">
                                <span class="custom-radio"></span>
                                Listing date - All
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="more_than_15_days">
                                <span class="custom-radio"></span>
                                More than 15 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="more_than_30_days">
                                <span class="custom-radio"></span>
                                More than 30 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="more_than_60_days">
                                <span class="custom-radio"></span>
                                More than 60 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date" value="more_than_90_days">
                                <span class="custom-radio"></span>
                                More than 90 days
                              </label>
                            
                            </div>
                        </div>
                    </div>

                    <button type="button" class="filter-btn hs-status-plain" id="hsPlainActive" data-type="status" data-value="Active" style="display:none;">Active</button>
                    
                    <div class="dropdown hs-split-dropdown-wrap" id="hsSplitWrapSold" style="display:none;">
                        <div class="hs-split-filter actived" data-split-status="sold">
                            <div class="hs-split-label">Sold</div>
                            <button type="button" class="hs-split-value filter-btn dropdown-toggle" data-hs-date-toggle="sold">
                                <span class="hs-split-value-text" id="hsSoldDateLabel">All</span>
                                    <span class="hs-split-caret">&#9660;</span>
                            </button>
                        </div>
                        <div class="dropdown-menu">
                            <div class="dropdown-card">

                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="last_1_day">
                                <span class="custom-radio"></span>
                                Last 1 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="last_3_day">
                                <span class="custom-radio"></span>
                                Last 3 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="last_7_day">
                                <span class="custom-radio"></span>
                                Last 7 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="last_30_day">
                                <span class="custom-radio"></span>
                                Last 30 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="last_90_day">
                                <span class="custom-radio"></span>
                                Last 90 days
                              </label>
                            
                              <label class="radio-item selected">
                                <input type="radio" name="date-sold" checked value="all">
                                <span class="custom-radio"></span>
                                Listing date - All
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="more_than_15_days">
                                <span class="custom-radio"></span>
                                More than 15 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="more_than_30_days">
                                <span class="custom-radio"></span>
                                More than 30 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="more_than_60_days">
                                <span class="custom-radio"></span>
                                More than 60 days
                              </label>
                            
                              <label class="radio-item">
                                <input type="radio" name="date-sold" value="more_than_90_days">
                                <span class="custom-radio"></span>
                                More than 90 days
                              </label>
                            
                            </div>
                        </div>
                    </div>

                    <button type="button" class="filter-btn hs-status-plain" id="hsPlainSold" data-type="status" data-value="Sold">Sold</button>

                    <div class="dropdown hs-split-dropdown-wrap" id="hsSplitWrapDelisted" style="display:none;">
                        <div class="hs-split-filter actived" data-split-status="delisted">
                            <div class="hs-split-label">De-listed</div>
                            <button type="button" class="hs-split-value filter-btn dropdown-toggle" data-hs-date-toggle="sold">
                                <span class="hs-split-value-text" id="hsDelistedDateLabel">All</span>
                                    <span class="hs-split-caret">&#9660;</span>
                            </button>
                        </div>
                        <div class="dropdown-menu">
                            <div class="dropdown-card">
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="last_1_day">
                                <span class="custom-radio"></span>
                                Last 1 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="last_3_day">
                                <span class="custom-radio"></span>
                                Last 3 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="last_7_day">
                                <span class="custom-radio"></span>
                                Last 7 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="last_30_day">
                                <span class="custom-radio"></span>
                                Last 30 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="last_90_day">
                                <span class="custom-radio"></span>
                                Last 90 days
                              </label>
                              <label class="radio-item selected">
                                <input type="radio" name="date-delisted" checked value="all">
                                <span class="custom-radio"></span>
                                Listing date - All
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="more_than_15_days">
                                <span class="custom-radio"></span>
                                More than 15 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="more_than_30_days">
                                <span class="custom-radio"></span>
                                More than 30 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="more_than_60_days">
                                <span class="custom-radio"></span>
                                More than 60 days
                              </label>
                              <label class="radio-item">
                                <input type="radio" name="date-delisted" value="more_than_90_days">
                                <span class="custom-radio"></span>
                                More than 90 days
                              </label>
                            </div>
                        </div>
                    </div>
            
                   
                    <button type="button" class="filter-btn hs-status-plain" id="hsPlainDelisted" data-type="status" data-value="Expired">De-listed</button>
            
                    <div class="dropdown">
                        <button class="filter-btn dropdown-toggle">
                            More 
                        </button>
                        <div class="dropdown-menu dropdown-menu-all" style="min-width: 380px;">
                            <div class="filters-container"style="height:60vh;zoom:0.8; overflow-y: scroll;">

                                  <!--div class="filter-section">
                                    <div class="filter-title">Description contains keywords</div>
                                    <input type="text" class="keyword-input" placeholder="Waterfront, Pool, Fireplace..." />
                                  </div-->
                                
                                  <div class="filter-section two-col">
                                    <div>
                                      <div class="filter-title">Bedrooms</div>
                                      <div class="chip-group" data-type="bedroom">
                                        <div class="chip active">All</div>
                                        <div class="chip">0</div>
                                        <div class="chip">1+</div>
                                        <div class="chip">2+</div>
                                        <div class="chip">3+</div>
                                        <div class="chip">4+</div>
                                        <div class="chip">5+</div>
                                      </div>
                                    </div>
                                
                                     <div>
                                      <div class="filter-title">Bathrooms</div>
                                      <div class="chip-group" data-type="bathroom">
                                        <div class="chip active">All</div>
                                         <div class="chip">0</div>
                                        <div class="chip">1+</div>
                                        <div class="chip">2+</div>
                                        <div class="chip">3+</div>
                                        <div class="chip">4+</div>
                                        <div class="chip">5+</div>
                                      </div>
                                    </div>
                                  </div>
                                
                                  <div class="filter-section two-col">
                                    <!--div>
                                      <div class="filter-title">Max Maintenance Fee</div>
                                      <div class="range-container">
                                        <input type="range" min="0" max="1000" value="0" step="50" />
                                        <div class="range-values">
                                          <span>$0</span>
                                          <span>No max</span>
                                        </div>
                                      </div>
                                    </div-->
                                
                                    <div>
                                      <div class="filter-title">Garage/Covered Parking</div>
                                      <div class="chip-group" data-type="basement">
                                        <div class="chip active">All</div>
                                         <div class="chip">0</div>
                                        <div class="chip">1+</div>
                                        <div class="chip">2+</div>
                                        <div class="chip">3+</div>
                                        <div class="chip">4+</div>
                                        <div class="chip">5+</div>
                                      </div>
                                    </div>
                                 
                                  <div>
                                      <div class="filter-title">Basement</div>
                                      <div class="chip-group" data-type="basement1">
                                        <div class="chip active">All</div>
                                         <div class="chip">Finished</div>
                                          <div class="chip">Walk-out</div>
                                        <div class="chip">Separate Entrance</div>
                                       
                                       
                                      </div>
                                    </div>
                                  </div>
                                
                                 <div class="filter-section">
                                        <div class="filter-title-square">Square Footage: Unspecified - Max</div>
                                    
                                        <div class="range-container">
                                            <!-- MIN -->
                                            <input type="range" class="square-min" min="0" max="4000" value="0" step="100" />
                                    
                                            <!-- MAX -->
                                            <input type="range" class="square-max" min="0" max="4000" value="4000" step="100" />
                                    
                                            <div class="range-values">
                                                <span>0 sqft</span>
                                                <span>500</span>
                                                <span>1000</span>
                                                <span>1500</span>
                                                <span>2500</span>
                                                <span>3000</span>
                                                <span>3500</span>
                                                <span>Max</span>
                                            </div>
                                        </div>
                                    </div>
                                
                                  <!--div class="filter-section">
                                    <div class="filter-title">Rental Yield: Unspecified - Max</div>
                                    <div class="range-container">
                                      <input type="range" min="0" max="10" value="0" step="0.5" />
                                      <div class="range-values">
                                        <span>0%</span>
                                        <span>Max</span>
                                      </div>
                                    </div>
                                  </div-->
                                
                                 
                                
                                </div>
                                
                                 <div class="actions">
                                    <button class="btn-clear">Clear All</button>
                                    <!--button class="btn-secondary">Save Filters</button-->
                                    <button class="btn-primary apply-all">Apply</button>
                                  </div>
                        </div>
                    </div>
            
                    <button type="button" class="filter-btn hs-list-bar-btn d-none d-lg-inline-flex" id="hsListToggleBtnBar" aria-label="Toggle list">
                        <span class="hs-list-icon">&#9776;</span> List
                    </button>

                    <button type="button" class="filter-btn clear-btn clear-btn-main">Clear all</button>
                    |
                    
                    <div class="dropdown watched-dropdown">
                        <button class="filter-btn dropdown-toggle">
                            <span class="icon">⬚</span>
                            Watched Areas
                        </button>
                    
                        <div class="dropdown-menu" style="margin-left: -170px;">
                            <div class="watched-wrapper">
                                <!-- Clear All -->
                                <div class="watched-header">
                                    <button class="clear-btn">Clear all</button>
                                </div>
                        
                                <!-- New Area Button -->
                                <div class="new-area">
                                    <button class="btn-new">New Watched Area</button>
                                </div>
                            </div>
                        </div>
                    </div>
            
                </div>
            </div>
            </div>
        </div>

    </div>
    <div id="polygon-popup" class="polygon-popup">
        <div class="popup-card">
            <h3>Save Watched Area?</h3>
            <div class="popup-actions">
                <label>Polygon Name:</label>
                <input type="text" id="polygon-name" placeholder="Enter area name">
                <button id="cancelPolygon" class="btn-outline">Cancel</button>
                <button id="savePolygon" class="btn-filled">Save</button>
            </div>
        </div>
    </div>



    

  
    <!-- ===== YOUR EXISTING MAP ===== -->
    <div class="map-search-wrapper map-mobile-view" style="display:flex;width: 100%;">

        <aside class="hs-list-sidebar" id="hsListSidebar" aria-hidden="true">
            <div class="hs-list-sidebar-header">
                <span id="hsListSidebarCount">0 properties</span>
                <button type="button" class="hs-list-sidebar-close" id="hsListSidebarClose" aria-label="Close list">&times;</button>
            </div>
            <div class="hs-list-sidebar-body" id="hsListSidebarBody"></div>
        </aside>

        <div class="hs-map-status-bar d-lg-none">
            <button type="button" class="hs-m-status active" data-status="Active">For Sale</button>
            <button type="button" class="hs-m-status" data-status="Sold">Sold</button>
            <button type="button" class="hs-m-status" data-status="Expired">De-listed</button>
        </div>
    
        <div id="map" style="width: 100%; min-height: 70vh; height: 100%;"></div>

        <div id="map-property-count" class="map-count-box">
            Available Properties : 0
        </div>

        <div class="hs-mobile-list-panel d-lg-none" id="hsMobileListPanel">
            <div class="hs-mobile-list-header" id="hsMobileListCount">0 properties</div>
            <div class="hs-mobile-list-body" id="hsMobileListBody"></div>
        </div>
    
        <div id="hsPropertyListLegacy" style="display:none;overflow-y:scroll;">
            <!-- legacy placeholder -->
        </div>
    
    </div>

    <div class="hs-mobile-view-bar d-lg-none" id="hsMobileViewBar">
        <button type="button" class="hs-view-bar-btn active" data-hs-view="map">
            <span>&#128506;</span>
            Map
        </button>
        <button type="button" class="hs-view-bar-btn" data-hs-view="list">
            <span>&#9776;</span>
            List
        </button>
    </div>
    
    <div id="property-list-panel" class="property-list-panel">
        <div class="panel-header">
            <span id="property-count"></span>
            <button onclick="closePanel()">✕</button>
        </div>
        <div id="property-list"></div>
    </div>
    
    <div id="hsMobileSheetsRoot">
        <div class="hs-m-sheet" id="hsSheetProperty">
            <div class="hs-m-sheet-header">
                <span>Property Type</span>
                <button type="button" class="hs-m-sheet-close" data-close-sheet>&times;</button>
            </div>
            <div class="hs-m-sheet-body" id="hsPropertyOptions"></div>
        </div>

        <div class="hs-m-sheet" id="hsSheetDate">
            <div class="hs-m-sheet-header">
                <span>Listing Date</span>
                <button type="button" class="hs-m-sheet-close" data-close-sheet>&times;</button>
            </div>
            <div class="hs-m-sheet-body">
                <div class="hs-m-date-columns" id="hsDateColumns"></div>
            </div>
            <div class="hs-m-sheet-actions">
                <button type="button" class="hs-m-clear" data-close-sheet>Cancel</button>
                <button type="button" class="hs-m-apply" id="hsDateApply">Apply</button>
            </div>
        </div>

        <div class="hs-m-sheet" id="hsSheetFilters">
            <div class="hs-m-sheet-header">
                <span>Filters</span>
                <button type="button" class="hs-m-sheet-close" data-close-sheet>&times;</button>
            </div>
            <div class="hs-m-sheet-body">
                <div class="hs-m-filter-section">
                    <p class="header">Price range</p>
                    <div class="price-display hs-m-price-label">$0 - Max</div>
                    <div class="range-wrapper">
                        <input type="range" min="0" max="5000000" value="0" step="50000" class="slider hs-m-slider-min">
                        <input type="range" min="0" max="5000000" value="5000000" step="50000" class="slider hs-m-slider-max" style="margin-top:10px;">
                    </div>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Bedrooms</p>
                    <ul class="hs-m-chips" data-mfilter="bedroom">
                        <li class="selected">All</li><li>0</li><li>1+</li><li>2+</li><li>3+</li><li>4+</li><li>5+</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Bathrooms</p>
                    <ul class="hs-m-chips" data-mfilter="bathroom">
                        <li class="selected">All</li><li>1+</li><li>2+</li><li>3+</li><li>4+</li><li>5+</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Garage/Covered Parking</p>
                    <ul class="hs-m-chips" data-mfilter="garage">
                        <li class="selected">All</li><li>1+</li><li>2+</li><li>3+</li><li>4+</li><li>5+</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Basement</p>
                    <ul class="hs-m-chips" data-mfilter="basement1">
                        <li class="selected">All</li><li>Finished</li><li>Separate Entrance</li><li>Walk-out</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Listing Type</p>
                    <ul class="hs-m-chips" data-mfilter="listingtype">
                        <li class="selected">All</li><li>Resale</li><li>Excl. Assignment</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Open House</p>
                    <ul class="hs-m-chips" data-mfilter="openhouse">
                        <li class="selected">Unspecified</li><li>Today</li><li>Tomorrow</li><li>7 days</li><li>All Open Houses</li>
                    </ul>
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Description Contains Keywords</p>
                    <input type="text" id="hsMKeywords" placeholder="Waterfront, Pool, Fireplace..." style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;">
                </div>
                <div class="hs-m-filter-section">
                    <p class="header">Max Maintenance Fee</p>
                    <input type="number" id="hsMMaintenance" placeholder="" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;">
                </div>
            </div>
            <div class="hs-m-sheet-actions">
                <button type="button" class="hs-m-clear" id="hsFiltersClear">Clear all filters</button>
                <button type="button" class="hs-m-apply" id="hsFiltersApply">Apply</button>
            </div>
        </div>
    </div>

    <div id="propertyModal" class="property-modal">
        <div class="modal-content">
            <span class="close-modal" id="clearBtn_popup" >&times;</span>
                <div id="iframeLoader" class="iframe-loader">
                    <div class="spinner"></div>
                </div>
            <iframe id="propertyFrame" src="" frameborder="0" allowfullscreen allow="fullscreen; clipboard-write" scrolling="yes"></iframe>
        </div>
    </div>
        
    
   
<footer class="top-footer-bar">
    <div class="footer-container">
        <div class="footer-left">
            Serik Realty Inc. 
        </div>

        <div class="footer-center">
            <a href="{{ url('/about-us') }}">About Us</a>
            <!--a href="#">Recently Sold Listings</a>
            <a href="#">Careers</a-->
            <a href="{{ url('/faqs') }}">FAQs</a>
            <a href="{{ url('/contact-us') }}">Contact Us</a>
            <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
            <a href="{{ url('/terms-conditions') }}">Terms & Conditions</a>
        </div>

        <!--div class="footer-right">
            App Version 1.1.0
        </div-->
    </div>
</footer>
</section>
@endif




@include(Theme::getThemeNamespace('views.real-estate.partials.property-map-content'))

@if ($isMapSearchPageView)
<script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>

<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.js"></script>
@endif


<script>

window.SERIK_IS_MAP_SEARCH_PAGE = @json($isMapSearchPageView);
window.SERIK_CANONICAL_ORIGIN = @json(rtrim(\App\Support\CanonicalUrl::normalize(url('/')), '/'));

function updateSeoUrlpassed() {

   const params = new URLSearchParams(window.location.search);

    // get seo param
    const seo = params.get('seo');

    // if no seo param found
    if (!seo) return;

    // remove seo from query string
    params.delete('seo');

    // build final url
    const finalUrl =
        `/on/${seo}/map` +
        (params.toString() ? `?${params.toString()}` : '');

    // update browser url
    window.history.replaceState({}, '', finalUrl);
    
  
  //  console.log("UPDATED URL:", finalUrl);
}
    
  
    
    
  function isMapSearchPage() {
    if (typeof window.SERIK_IS_MAP_SEARCH_PAGE === 'boolean') {
        return window.SERIK_IS_MAP_SEARCH_PAGE;
    }

    const path = window.location.pathname.toLowerCase().replace(/\/$/, '');

    if (path === '/map' || path === '/on/map') {
        return true;
    }

    const seoPath = path
        .replace(/^\/on\//, '')
        .replace(/\/map$/, '')
        .replace(/^\//, '');

    return /^(?:.+)-for-(?:sale|lease)(?:-in-.+)?$/.test(seoPath);
  }


if (isMapSearchPage()) {
    const legacyFormMain = document.getElementById('formMain');
    if (legacyFormMain) {
        legacyFormMain.innerHTML = '';
        legacyFormMain.style.display = 'none';
    }

    const mapEl = document.querySelector('#secondMain #map') || document.getElementById('map');
    if (mapEl) {
        mapEl.style.minHeight = '100vh';
    }
} else {
    const legacySecondMain = document.getElementById('secondMain');
    if (legacySecondMain) {
        legacySecondMain.innerHTML = '';
        legacySecondMain.style.display = 'none';
    }

    const mapEl = document.getElementById('map');
    if (mapEl) {
        mapEl.style.minHeight = '460px';
    }
}



const cityCoordinates = {
    Brampton:      { lat: 43.6885808343192, lng: -79.75612479361787 }, 
    Mississauga:   { lat: 43.58779558372923, lng: -79.65645234744841 }, 
    Vaughan:       { lat: 43.786564653905706, lng: -79.51458180019757 }, 
    Milton:        { lat: 43.518035033560786, lng: -79.8792787892796 }, 
    Oakville:      { lat: 43.45223264301963, lng: -79.72089388069197 }, 
    NiagaraFalls:  { lat: 43.08892604552067, lng: -79.0818690359816 }, 
    Toronto:       { lat: 43.65754702025105, lng: -79.39867153523684 },
    Kitchener:     { lat: 43.4510951553707, lng: -80.49039489300593 }, 
    Waterloo:      { lat: 43.48318535045528, lng: -80.52543694739536 }, 
    Cambridge:     { lat: 43.36159746292483, lng: -80.3109622338881 },
    brampton:      { lat: 43.6885808343192, lng: -79.75612479361787 }, 
    mississauga:   { lat: 43.58779558372923, lng: -79.65645234744841 }, 
    vaughan:       { lat: 43.786564653905706, lng: -79.51458180019757 }, 
    milton:        { lat: 43.518035033560786, lng: -79.8792787892796 }, 
    oakville:      { lat: 43.45223264301963, lng: -79.72089388069197 }, 
    niagaraFalls:  { lat: 43.08892604552067, lng: -79.0818690359816 }, 
    toronto:       { lat: 43.65754702025105, lng: -79.39867153523684 },
    kitchener:     { lat: 43.4510951553707, lng: -80.49039489300593 }, 
    waterloo:      { lat: 43.48318535045528, lng: -80.52543694739536 }, 
    cambridge:     { lat: 43.36159746292483, lng: -80.3109622338881 },
    hamilton:      { lat: 43.253336051885114, lng: -79.87552747655647 },
    ottawa:        { lat: 45.421516119485744, lng: -75.70006114428827 },
    London:          { lat: 42.9849, lng: -81.2453 },
    Markham:         { lat: 43.8561, lng: -79.3370 },
    Windsor:         { lat: 42.3149, lng: -83.0364 },
    RichmondHill:    { lat: 43.8828, lng: -79.4403 },
    Burlington:      { lat: 43.3255, lng: -79.7990 },
    Oshawa:          { lat: 43.8971, lng: -78.8658 },
    Barrie:          { lat: 44.3894, lng: -79.6903 },
    Guelph:          { lat: 43.5448, lng: -80.2482 },
    Kingston:        { lat: 44.2312, lng: -76.4860 },
    Whitby:          { lat: 43.8975, lng: -78.9429 },
    Ajax:            { lat: 43.8509, lng: -79.0204 },
    Peterborough:    { lat: 44.3091, lng: -78.3197 },
    Sarnia:          { lat: 42.9745, lng: -82.4066 },
    ThunderBay:      { lat: 48.3809, lng: -89.2477 },
    Sudbury:         { lat: 46.4917, lng: -80.9930 },
    NorthBay:        { lat: 46.3091, lng: -79.4608 },
    Orillia:         { lat: 44.6087, lng: -79.4207 },
    Brantford:       { lat: 43.1394, lng: -80.2644 },
    StCatharines:    { lat: 43.1594, lng: -79.2469 },
    Welland:         { lat: 42.9930, lng: -79.2485 },
    Pickering:       { lat: 43.8354, lng: -79.0890 },
    Clarington:      { lat: 43.9120, lng: -78.6880 },
    Newmarket:       { lat: 44.0592, lng: -79.4613 },
    Aurora:          { lat: 44.0065, lng: -79.4504 },
    Orangeville:     { lat: 43.9190, lng: -80.0943 },
    Midland:         { lat: 44.7495, lng: -79.8923 },
    Collingwood:     { lat: 44.5008, lng: -80.2169 },
    Timmins:         { lat: 48.4758, lng: -81.3305 },
    Kenora:          { lat: 49.7670, lng: -94.4890 },
    ElliotLake:      { lat: 46.3834, lng: -82.6509 },
    Brockville:      { lat: 44.5895, lng: -75.6843 },
    Cornwall:        { lat: 45.0213, lng: -74.7303 },
    Stratford:       { lat: 43.3700, lng: -80.9819 },
    Woodstock:       { lat: 43.1315, lng: -80.7467 },
    Leamington:      { lat: 42.0534, lng: -82.5991 },
    Chatham:         { lat: 42.4048, lng: -82.1910 },
    Belleville:      { lat: 44.1628, lng: -77.3832 },
    Pembroke:        { lat: 45.8267, lng: -77.1103 }
};

 function normalizeCity(city) {
    if (!city) return '';

    return city
        .toString()
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '');
}
const cityCoordinatesNormalized = {};

Object.keys(cityCoordinates).forEach(key => {
    cityCoordinatesNormalized[normalizeCity(key)] = cityCoordinates[key];
});
 
document.addEventListener("DOMContentLoaded", function () {
    if (!isMapSearchPage()) {
        return;
    }

    let isMapUserLoggedIn = @json(auth('account')->check() || auth()->check());
    const MAP_SOLD_STATUSES = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];

    fetch('/api/v1/auth/session-status', {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
            if (data && data.logged_in) {
                isMapUserLoggedIn = true;
            }
        })
        .catch(() => {});

    window.isMapSoldListing = function (status, props) {
        if (props && props.requires_login) {
            return true;
        }
        if (!status) return false;
        const normalized = String(status).trim().toLowerCase();
        return MAP_SOLD_STATUSES.some((s) => s.toLowerCase() === normalized)
            || normalized.includes('sold')
            || normalized === 'leased';
    };

    window.mapListingStatus = function (props) {
        return (props && (props.mls_status || props.transaction)) || '';
    };

    window.mapLoginGateHtml = function (status, props) {
        if (isMapUserLoggedIn || !isMapSoldListing(status, props)) {
            return '';
        }
        return '<div class="map-sold-login-gate">' +
            '<p class="mb-2 small text-muted">Local real estate board rules require you to log in to view sold property details.</p>' +
            '<button type="button" class="btn btn-light fw-bold js-map-auth-open">Login to View Sold Property</button>' +
            '</div>';
    };

    window.mapBlurClass = function (status, props) {
        return (!isMapUserLoggedIn && isMapSoldListing(status, props)) ? 'blurred-content' : '';
    };

    if (window.location.hash === '#modalLogin' || window.location.hash === '#modalRegister') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }
    
   
    
   function getFiltersFromPath() {

    let path = window.location.pathname
        .toLowerCase()
        .replace(/^\/|\/$/g, '');

    // remove /on/
    path = path.replace(/^on\//, '');

    // remove /map
    path = path.replace(/\/map$/, '');

    let filters = {
        transaction: '',
        city: '',
        subtypes: []
    };

    // city optional now
     const match = path.match(
        /^(.*)-for-(sale|lease)(?:-in-(.*))?$/
    );
    if (!match) return filters;

    const typePart = match[1];
    const transaction = match[2];
    const city = match[3] || '';

    // transaction
    filters.transaction =
        transaction === 'sale'
            ? 'For Sale'
            : 'For Lease';

    // city
    filters.city = city;

    // subtype mapping (slug before -for-sale-in-...)
    const subtypeMap = {
        'detached-houses': 'Detached',
        'detached': 'Detached',
        'semi-detached-houses': 'Semi-Detached',
        'semi-detached': 'Semi-Detached',
        'freehold-townhouses': 'Att/Row/Townhouse',
        'townhouses': 'Att/Row/Townhouse',
        'condo-townhouses': 'Condo Townhouse',
        'condos': 'Condo Apartment',
        'condos-apartments': 'Condo Apartment',
        'condo-apartments': 'Condo Apartment',
        'duplex': 'Duplex',
        'duplexes': 'Duplex',
        'houses': null,
    };

    if (Object.prototype.hasOwnProperty.call(subtypeMap, typePart)) {
        if (subtypeMap[typePart]) {
            filters.subtypes = [subtypeMap[typePart]];
        }
    } else if (typePart && typePart !== 'houses') {
        const guessed = typePart
            .split('-')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ')
            .replace('Semi Detached', 'Semi-Detached');
        if (guessed) {
            filters.subtypes = [guessed];
        }
    }

    // normalize city slug → display name
    if (filters.city) {
        filters.city = filters.city
            .split('-')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ')
            .replace('Niagarafalls', 'Niagara Falls');
    }

    return filters;
}

const pathFilters = getFiltersFromPath();
    
const CITIES_GEO_URL = '/storage/cities.json';
let citiesGeoCache = null;

async function getCitiesGeo() {
    if (citiesGeoCache) {
        return citiesGeoCache;
    }
    const res = await fetch(CITIES_GEO_URL);
    citiesGeoCache = await res.json();
    return citiesGeoCache;
}

function geojsonBounds(geometry) {
    let minLng = Infinity, minLat = Infinity, maxLng = -Infinity, maxLat = -Infinity;

    function walk(coords) {
        if (typeof coords[0] === 'number') {
            const lng = coords[0];
            const lat = coords[1];
            minLng = Math.min(minLng, lng);
            maxLng = Math.max(maxLng, lng);
            minLat = Math.min(minLat, lat);
            maxLat = Math.max(maxLat, lat);
            return;
        }
        coords.forEach(walk);
    }

    walk(geometry.coordinates);
    return [[minLng, minLat], [maxLng, maxLat]];
}

    function ensureCityLayer() {

    if (map.getSource('cities')) return;

    map.addSource('cities', {
        type: 'geojson',
        data: CITIES_GEO_URL
    });

    map.addLayer({
        id: 'city-fill',
        type: 'fill',
        source: 'cities',
        filter: ['==', ['get', 'NAME_3'], ''],
        paint: {
            'fill-color': '#013677',
            'fill-opacity': 0.22
        }
    });

    map.addLayer({
        id: 'city-outline',
        type: 'line',
        source: 'cities',
        filter: ['==', ['get', 'NAME_3'], ''],
        paint: {
            'line-color': '#012a5c',
            'line-width': 3,
            'line-opacity': 0.85
        }
    });
}

async function showCityBoundary(cityName) {

    if (!cityName || !map) return;

    ensureCityLayer();

    if (!map.getLayer('city-fill')) return;

    const normalized = cityName
        .toString()
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '');

    map.setFilter('city-fill', [
        '==',
        ['downcase', ['get', 'NAME_3']],
        normalized
    ]);

    map.setFilter('city-outline', [
        '==',
        ['downcase', ['get', 'NAME_3']],
        normalized
    ]);

    try {
        const geo = await getCitiesGeo();
        const feature = geo.features.find((f) =>
            (f.properties.NAME_3 || '').toLowerCase().replace(/\s+/g, '') === normalized
        );

        if (feature) {
            const geoName = (feature.properties.NAME_3 || '').toLowerCase();

            map.setFilter('city-fill', [
                '==',
                ['downcase', ['get', 'NAME_3']],
                geoName
            ]);

            map.setFilter('city-outline', [
                '==',
                ['downcase', ['get', 'NAME_3']],
                geoName
            ]);

            activeCityGeometryType = feature.geometry.type;
            if (feature.geometry.type === 'Polygon') {
                activeCityPolygon = feature.geometry.coordinates;
            } else if (feature.geometry.type === 'MultiPolygon') {
                activeCityPolygon = feature.geometry.coordinates;
            } else {
                activeCityPolygon = null;
                activeCityGeometryType = null;
            }

            const bounds = geojsonBounds(feature.geometry);
            map.fitBounds(bounds, { padding: 50, duration: 1000, maxZoom: 12 });
            map.once('moveend', () => {
                if (typeof loadProperties === 'function') {
                    loadProperties();
                }
            });
        } else {
            activeCityPolygon = null;
            activeCityGeometryType = null;
            loadProperties();
        }
    } catch (e) {
        console.warn('City boundary fit failed', e);
        activeCityPolygon = null;
        activeCityGeometryType = null;
        loadProperties();
    }
}
    
    
    
     const mapContainer = document.querySelector('#secondMain #map');
    if (!mapContainer) {
        console.warn('Map search container not found.');
        return;
    }

    // ==============================
    // GLOBAL STATE
    // ==============================

    let cityFromUrl = '';
    let seoCitySlug = 'ontario';
    let mapLayersReady = false;
    let autoCenteringMap = false;
    let userHasMovedMap = false;

    let selectedTransaction = '';
    let selectedMinPrice = 0;
    let selectedMaxPrice = 0;
    let selectedStatus = null;
    let mapInitialized = false;
    let selectedBedrooms = null;
    let selectedBathrooms = null;
    let selectedBasement = null;
    let selectedBasement1 = null;
    let selectedMaxSquare = 0;
    let selectedMinSquare = 0;
    let selectedSubTypes = [];
    let mapHistoryNavigating = false;
    let lastPushedMapUrl = '';

    const HS_MOBILE_PROPERTY_TYPES = [
        { label: 'All property types', value: '' },
        { label: 'Detached', value: 'Detached' },
        { label: 'Semi-Detached', value: 'Semi-Detached' },
        { label: 'Freehold Townhouse', value: 'Att/Row/Townhouse' },
        { label: 'Condo Townhouse', value: 'Condo Townhouse' },
        { label: 'Condo Apt', value: 'Condo Apartment' },
        { label: 'Link', value: 'Link' },
        { label: 'Multiplex', value: 'Multiplex' },
        { label: 'Vacant Land', value: 'Land' },
        { label: 'Other', value: 'Other' },
    ];

    let activeMarker = null;
    let selectedCity = '';
    let hsMobileDateSale = 'all';
    let hsMobileDateSold = 'all';
    let draw = null;
    let isDrawing = false;
    let currentPolygon = null;
    let activeWatchedPolygon = null;
    let activeCityPolygon = null;
    let activeCityGeometryType = null;
    let editingWatchedIndex = null;
    
    const squareMinSlider = document.querySelector('.square-min');
    const squareMaxSlider = document.querySelector('.square-max');
    const squareTitle = document.querySelector('.filter-title-square');

    // ==============================
    // INITIALIZE MAP
    // ==============================
    const map = new maplibregl.Map({
        container: mapContainer,
        style: {
        version: 8,
        glyphs: "https://api.maptiler.com/fonts/{fontstack}/{range}.pbf?key=bt17xhTzpkGNXIhaRMl2",
        sources: {
            'carto-voyager': {
                type: 'raster',
                tiles: [
                    'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://d.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png'
                ],
                tileSize: 256,
                maxzoom: 20,
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            }
        },
        layers: [
            {
                id: 'carto-voyager-layer',
                type: 'raster',
                source: 'carto-voyager'
            }
        ]
    },
    
        center: [-79.3832, 43.6532],
        zoom: 11,
        maxZoom: 20,
        minZoom: 6,
        maxBounds: [[-95.2, 41.6], [-74.0, 56.9]]
    });
  
    map.addControl(new maplibregl.NavigationControl(), window.innerWidth <= 991 ? 'bottom-right' : 'top-right');
    window.hsMap = map;

    const resizeHsMap = () => {
        if (map && typeof map.resize === 'function') {
            map.resize();
        }
    };

    map.once('load', resizeHsMap);
    window.addEventListener('resize', resizeHsMap);
    window.addEventListener('orientationchange', () => setTimeout(resizeHsMap, 250));
    if (window.innerWidth < 992) {
        setTimeout(resizeHsMap, 150);
        setTimeout(resizeHsMap, 600);
    }

    // ==============================
    // LOAD SOURCE + LAYERS
    // ==============================
    
    
    // Set Transaction UI
document.getElementById('transactionDropdown').innerText = 'For Sale';
document.querySelectorAll('.transaction-item').forEach(i => {
    if (i.dataset.transaction === 'For Sale') {
        i.classList.add('active');
    }
});


const HS_DATE_LABELS = {
    last_1_day: 'Last 1 days',
    last_3_day: 'Last 3 days',
    last_7_day: 'Last 7 days',
    last_30_day: 'Last 30 days',
    last_90_day: 'Last 90 days',
    all: 'Listing date - All',
    more_than_15_days: 'More than 15 days',
    more_than_30_days: 'More than 30 days',
    more_than_60_days: 'More than 60 days',
    more_than_90_days: 'More than 90 days',
    last_180_day: 'Last 180 days',
    last_360_day: 'Last 360 days',
};

function getHsDateLabel(val) {
    if (!val || val === 'all') return 'All';
    if (val.startsWith('year_')) return 'Year ' + val.replace('year_', '');
    return HS_DATE_LABELS[val] || val;
}

function updateSplitFilterLabels() {
    const activeEl = document.getElementById('hsActiveDateLabel');
    const soldEl = document.getElementById('hsSoldDateLabel');
    const delistedEl = document.getElementById('hsDelistedDateLabel');
    const saleDate = hsMobileDateSale || 'all';
    const soldDate = hsMobileDateSold || 'all';
    if (activeEl) activeEl.textContent = getHsDateLabel(saleDate);
    if (soldEl) soldEl.textContent = getHsDateLabel(soldDate);
    if (delistedEl) delistedEl.textContent = getHsDateLabel(soldDate);

    const soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
    const delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
    const current = selectedStatus || [];
    const isSold = current.some((s) => soldStatuses.includes(s));
    const isDelisted = current.some((s) => delistedStatuses.includes(s));

    const mobLabel = document.getElementById('hsMobSplitLabel');
    if (mobLabel) {
        if (isDelisted) mobLabel.textContent = 'De-listed';
        else if (isSold) mobLabel.textContent = 'Sold';
        else mobLabel.textContent = 'Active';
    }

    const mobDate = document.getElementById('hsMobDateBtn');
    if (mobDate) {
        const val = (isSold || isDelisted) ? soldDate : saleDate;
        mobDate.textContent = getHsDateLabel(val) + ' \u25BC';
    }
}

function syncSoldDateRadios(soldVal) {
    ['date-sold', 'date-delisted'].forEach((groupName) => {
        document.querySelectorAll('input[name="' + groupName + '"]').forEach((r) => {
            const isSelected = r.value === soldVal;
            r.checked = isSelected;
            const label = r.closest('.radio-item');
            if (label) {
                label.classList.toggle('selected', isSelected);
            }
        });
    });
}

function syncDateRadiosFromState() {
    const saleVal = hsMobileDateSale || 'all';
    const soldVal = hsMobileDateSold || 'all';
    document.querySelectorAll('input[name="date"]').forEach((r) => {
        const isSelected = r.value === saleVal;
        r.checked = isSelected;
        r.closest('.radio-item')?.classList.toggle('selected', isSelected);
    });
    syncSoldDateRadios(soldVal);
}

function applyPathFiltersFromUrl() {
    const filters = getFiltersFromPath();
    const pathCity = (filters.city || '').toLowerCase();

    if (pathCity && pathCity !== 'ontario') {
        seoCitySlug = slugify(filters.city);
        cityFromUrl = filters.city;
        selectedCity = filters.city;
    } else if (pathCity === 'ontario') {
        seoCitySlug = 'ontario';
        cityFromUrl = 'ontario';
        selectedCity = '';
    } else {
        seoCitySlug = 'ontario';
        cityFromUrl = '';
        selectedCity = '';
    }

    if (filters.transaction) {
        selectedTransaction = filters.transaction;
    }

    if (filters.subtypes.length) {
        selectedSubTypes = filters.subtypes.slice();
    }
}

function initFromUrl() {
    applyPathFiltersFromUrl();

    const urlParams = new URLSearchParams(window.location.search);

    if (!selectedTransaction) {
        selectedTransaction = pathFilters.transaction || 'For Sale';
    }

    selectedMinPrice = parseInt(urlParams.get('min_price') || 0);
    selectedMaxPrice = parseInt(urlParams.get('max_price') || 500000000);

    selectedStatus = urlParams.get('status')
        ? urlParams.get('status').split(',')
        : selectedStatus;

    const urlDate = urlParams.get('date');
    if (urlDate) {
        hsMobileDateSale = urlDate;
    }

    const urlDateSold = urlParams.get('date_sold');
    if (urlDateSold) {
        hsMobileDateSold = urlDateSold;
    }

    if (!selectedStatus || !selectedStatus.length) {
        selectedStatus = ['New', 'Price Change', 'Extension', 'Previous Status'];
    }

    selectedMinSquare = parseInt(urlParams.get('square_min') || 0);
    selectedMaxSquare = parseInt(urlParams.get('square_max') || 4000);

    selectedBedrooms = urlParams.get('bedrooms') || null;
    selectedBathrooms = urlParams.get('bathrooms') || null;
    selectedBasement = urlParams.get('basement') || null;
    selectedBasement1 = urlParams.get('basement1') || null;

    const urlSubtypes = urlParams.get('subtypes');
    if (urlSubtypes) {
        selectedSubTypes = urlSubtypes.split(',').filter(Boolean);
    }

    syncDateRadiosFromState();
}

initFromUrl();
syncFilterUiFromState();

function restoreMapStateFromHistory(state) {
    if (!state || !state.mapSearch) {
        return;
    }

    selectedCity = state.selectedCity || '';
    selectedTransaction = state.selectedTransaction || selectedTransaction;
    selectedMinPrice = parseInt(state.selectedMinPrice || 0, 10);
    selectedMaxPrice = parseInt(state.selectedMaxPrice || selectedMaxPrice, 10);
    selectedStatus = Array.isArray(state.selectedStatus) && state.selectedStatus.length
        ? state.selectedStatus.slice()
        : selectedStatus;
    selectedSubTypes = Array.isArray(state.selectedSubTypes)
        ? state.selectedSubTypes.slice()
        : selectedSubTypes;
    selectedBedrooms = state.selectedBedrooms || null;
    selectedBathrooms = state.selectedBathrooms || null;
    selectedBasement = state.selectedBasement || null;
    selectedBasement1 = state.selectedBasement1 || null;
    selectedMinSquare = parseInt(state.selectedMinSquare || 0, 10);
    selectedMaxSquare = parseInt(state.selectedMaxSquare || 4000, 10);
    hsMobileDateSale = state.hsMobileDateSale || hsMobileDateSale;
    hsMobileDateSold = state.hsMobileDateSold || hsMobileDateSold;

    syncFilterUiFromState();
    syncPriceSlidersFromState?.();
    updatePriceDisplay?.();
    bustMapFetchCache();
}

window.addEventListener('popstate', function (event) {
    if (!isMapSearchPage()) {
        return;
    }

    if (event.state && event.state.mapSearch) {
        mapHistoryNavigating = true;
        restoreMapStateFromHistory(event.state);
        skipSeoUrlOnNextLoad = true;
        bustMapFetchCache();
        loadProperties();
        mapHistoryNavigating = false;
    }
});

if (isMapSearchPage()) {
    const initialMapState = {
        mapSearch: true,
        selectedCity: selectedCity || '',
        selectedTransaction: selectedTransaction || '',
        selectedMinPrice: selectedMinPrice || 0,
        selectedMaxPrice: selectedMaxPrice || 0,
        selectedStatus: Array.isArray(selectedStatus) ? selectedStatus.slice() : [],
        selectedSubTypes: Array.isArray(selectedSubTypes) ? selectedSubTypes.slice() : [],
        selectedBedrooms: selectedBedrooms || '',
        selectedBathrooms: selectedBathrooms || '',
        selectedBasement: selectedBasement || '',
        selectedBasement1: selectedBasement1 || '',
        selectedMinSquare: selectedMinSquare || 0,
        selectedMaxSquare: selectedMaxSquare || 0,
        hsMobileDateSale: hsMobileDateSale || 'all',
        hsMobileDateSold: hsMobileDateSold || 'all',
    };

    if (!window.history.state || !window.history.state.mapSearch) {
        window.history.replaceState(initialMapState, '', window.location.href);
    }

    lastPushedMapUrl = window.location.pathname + window.location.search;
}

function syncFilterUiFromState() {
    const activeStatuses = ['New', 'Price Change', 'Extension', 'Previous Status'];
    const soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
    const delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
    const current = selectedStatus || [];

    document.querySelectorAll('[data-type="status"]').forEach((btn) => btn.classList.remove('active'));
    document.querySelectorAll('.hs-m-status').forEach((btn) => btn.classList.remove('active'));
    document.querySelectorAll('.hs-split-value').forEach((btn) => btn.classList.remove('active'));

    const isActive = activeStatuses.every((s) => current.includes(s)) && current.length === activeStatuses.length;
    const isSold = current.some((s) => soldStatuses.includes(s));
    const isDelisted = current.some((s) => delistedStatuses.includes(s));

    const activeWrap = document.getElementById('hsSplitWrapActive');
    const soldWrap = document.getElementById('hsSplitWrapSold');
    const delistedWrap = document.getElementById('hsSplitWrapDelisted');
    const plainActive = document.getElementById('hsPlainActive');
    const plainSold = document.getElementById('hsPlainSold');
    const plainDelisted = document.getElementById('hsPlainDelisted');

    const showActiveSplit = isActive || (!isSold && !isDelisted);
    const showSoldSplit = isSold && !isDelisted;
    const showDelistedSplit = isDelisted;

    if (activeWrap) activeWrap.style.display = showActiveSplit ? '' : 'none';
    if (soldWrap) soldWrap.style.display = showSoldSplit ? '' : 'none';
    if (delistedWrap) delistedWrap.style.display = showDelistedSplit ? '' : 'none';
    if (plainActive) plainActive.style.display = showActiveSplit ? 'none' : '';
    if (plainSold) plainSold.style.display = showSoldSplit ? 'none' : '';
    if (plainDelisted) plainDelisted.style.display = showDelistedSplit ? 'none' : '';

    if (showActiveSplit) {
        document.querySelector('#hsSplitWrapActive .hs-split-value')?.classList.add('active');
        document.querySelector('.hs-m-status[data-status="Active"]')?.classList.add('active');
    } else if (showSoldSplit) {
        document.querySelector('#hsSplitWrapSold .hs-split-value')?.classList.add('active');
        document.querySelector('.hs-m-status[data-status="Sold"]')?.classList.add('active');
    } else if (showDelistedSplit) {
        document.querySelector('#hsSplitWrapDelisted .hs-split-value')?.classList.add('active');
        document.querySelector('.hs-m-status[data-status="Expired"]')?.classList.add('active');
    }

    document.getElementById('hsMobStatusSplit')
        ?.classList.toggle('actived', showActiveSplit);

    if (typeof updateSplitFilterLabels === 'function') {
        updateSplitFilterLabels();
    }
    if (typeof updateMobileFilterLabels === 'function') {
        updateMobileFilterLabels();
    }
    syncDateRadiosFromState();
    syncPropertyTypeUiFromState();

    if (selectedTransaction && document.getElementById('transactionDropdown')) {
        document.getElementById('transactionDropdown').innerText = selectedTransaction;
        document.querySelectorAll('.transaction-item').forEach((item) => {
            item.classList.toggle('active', item.dataset.transaction === selectedTransaction);
        });
    }
}

function syncPropertyTypeUiFromState() {
    const propertyButton = document.querySelector('.property-selector');
    const typeMenu = document.getElementById('dropdown-menu-type');
    const checkboxes = typeMenu
        ? typeMenu.querySelectorAll('input[type="checkbox"]')
        : [];

    if (!checkboxes.length) return;

    checkboxes.forEach((cb) => {
        if (cb.value === 'All') {
            cb.checked = !selectedSubTypes || selectedSubTypes.length === 0;
            return;
        }
        cb.checked = Array.isArray(selectedSubTypes) && selectedSubTypes.includes(cb.value);
    });

    let label = 'All Properties';
    if (selectedSubTypes && selectedSubTypes.length === 1) {
        label = selectedSubTypes[0];
    } else if (selectedSubTypes && selectedSubTypes.length > 1) {
        label = `${selectedSubTypes[0]} +${selectedSubTypes.length - 1}`;
    }

    if (propertyButton) {
        propertyButton.innerText = label;
        propertyButton.classList.toggle('filter-active', selectedSubTypes && selectedSubTypes.length > 0);
    }

    const mobilePropBtn = document.getElementById('hsMobPropertyBtn');
    if (mobilePropBtn && selectedSubTypes && selectedSubTypes.length === 1) {
        const match = HS_MOBILE_PROPERTY_TYPES?.find((t) => t.value === selectedSubTypes[0]);
        mobilePropBtn.textContent = (match ? match.label : selectedSubTypes[0]) + ' \u25BE';
    }
}

function getEffectiveSubtypes() {
    if (Array.isArray(selectedSubTypes) && selectedSubTypes.length > 0) {
        return selectedSubTypes.slice();
    }

    const pathSubtypes = getFiltersFromPath().subtypes;
    if (Array.isArray(pathSubtypes) && pathSubtypes.length > 0) {
        return pathSubtypes.slice();
    }

    return [];
}

function bustMapFetchCache() {
    lastMapFetchKey = '';
    lastFetchCenter = null;
    lastFetchZoom = null;
}


    map.on('load', function () { 
        
        updateSeoUrlpassed();

 
        setTimeout(() => {
            
              const pathFilters = getFiltersFromPath();
    
  const cityFromUrlRaw = pathFilters.city || getCityFromUrl();
cityFromUrl = cityFromUrlRaw;
const cityKey = normalizeCity(cityFromUrlRaw);

        ensureCityLayer();

        map.addSource('properties', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: []
            },
            cluster: true,
            clusterMaxZoom: 15,
            clusterRadius: 55
        });

        // ======================
        // CLUSTER CIRCLES
        // ======================

       map.addLayer({
    id: 'clusters',
    type: 'circle',
    source: 'properties',
    filter: ['has', 'point_count'],
    paint: {
        'circle-color': [
            'step',
            ['get', 'point_count'],
            '#ff5722',
            10, '#ff5722',
            50, '#e64a19'
        ],
        'circle-radius': [
            'step',
            ['get', 'point_count'],
            20,
            10, 30,
            50, 40
        ],
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
    }
    });
    
    
    
   
    
    
    
    
    const urlLocation = getLatLngFromUrl();

if (urlLocation) {
    const { lat, lng } = urlLocation;

    const marker = new maplibregl.Marker({ color: "#0255a1" })
        .setLngLat([lng, lat])
        .addTo(map);

    map.setCenter([lng, lat]);
    map.setZoom(13);
}
    
    // Cluster number text
 map.addLayer({
    id: 'cluster-count',
    type: 'symbol',
    source: 'properties',
    filter: ['has', 'point_count'],
    layout: {
        'text-field': ['get', 'point_count_abbreviated'],
        'text-font': ['Noto Sans Regular'],
        'text-size': 13
    },
    paint: {
        'text-color': '#ffffff'
    }
});

    // ======================
    // SINGLE PROPERTY MARKERS
    // ======================
    
    map.addLayer({
        id: 'unclustered-point',
        type: 'circle',
        source: 'properties',
        filter: ['!', ['has', 'point_count']],
        paint: {
            'circle-color': '#0255a1',
            'circle-radius': 8,
            'circle-stroke-width': 2,
            'circle-stroke-color': '#fff'
        }
    });

    mapLayersReady = true;

    applyPathFiltersFromUrl();

    const urlSubtypes = getSubtypesFromUrl();
    if (urlSubtypes.length > 0) {
        selectedSubTypes = urlSubtypes;
    } else if (!selectedSubTypes.length) {
        const pathSubtypes = getFiltersFromPath().subtypes;
        if (pathSubtypes.length) {
            selectedSubTypes = pathSubtypes.slice();
        }
    }

    const explicitPathCity = (pathFilters.city || '').trim();
    const queryCity = getCityFromUrl();
    const resolvedCity = explicitPathCity || queryCity || cityFromUrlRaw || '';
    const resolvedCityLower = resolvedCity.toLowerCase();

    if (!urlLocation) {
        if (resolvedCity && resolvedCityLower !== 'ontario') {
            selectedCity = resolvedCity;
            cityFromUrl = resolvedCity;
        } else if (resolvedCityLower === 'ontario') {
            selectedCity = '';
            cityFromUrl = 'ontario';
        }

        if (resolvedCityLower === 'ontario') {
            // Ontario-wide search: center on the visitor's area (zoomed in) so
            // they see nearby properties first; subtype filters still apply.
            centerOnVisitorAreaNoLock();
        } else if (shouldAutoCenterOnUserLocation()) {
            setMapToUserLocation();
        } else if (resolvedCity && resolvedCityLower !== 'ontario') {
            const citySlug = resolvedCityLower;

            if (citySlug === 'kwc') {
                const kwcCities = ['Kitchener', 'Waterloo', 'Cambridge'];
                const coords = kwcCities.map(c => cityCoordinates[c]);

                const lats = coords.map(c => c.lat);
                const lngs = coords.map(c => c.lng);

                const sw = [Math.min(...lngs), Math.min(...lats)];
                const ne = [Math.max(...lngs), Math.max(...lats)];

                map.fitBounds([sw, ne], { padding: 50 });
                map.once('moveend', () => loadProperties());
            } else {
                showCityBoundary(resolvedCity);
            }
        } else {
            loadProperties();
        }
    } else {
        loadProperties();
    }
    

    if (pathFilters.transaction) {
        selectedTransaction = pathFilters.transaction;
    }

    if (pathFilters.city && pathFilters.city.toLowerCase() !== 'ontario') {
        selectedCity = pathFilters.city;
        seoCitySlug = slugify(pathFilters.city);
    }

    syncPropertyTypeUiFromState();
    syncFilterUiFromState();

        if (resolvedCityLower === 'ontario') {
            skipSeoUrlOnNextLoad = true;
            map.once('idle', () => {
                loadProperties();
                updateSeoUrl();
            });
        }

        initMapDraw();
        
        
        }, 50);
    });
    
    
    
    
    
    
    
    
    
    
    
    
    // SINGLE PROPERTY CLICK — handler registered after showPropertyPopup is defined below

map.on('mouseenter', 'clusters', () => {
    map.getCanvas().style.cursor = 'pointer';
});
map.on('mouseleave', 'clusters', () => {
    map.getCanvas().style.cursor = '';
});

map.on('mouseenter', 'unclustered-point', () => {
    map.getCanvas().style.cursor = 'pointer';
});
map.on('mouseleave', 'unclustered-point', () => {
    map.getCanvas().style.cursor = '';
});

    // ==============================
    // ==============================

    function isSoldOrDelistedStatus() {
        const soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        const delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
        const current = selectedStatus || [];
        return current.some((s) => soldStatuses.includes(s) || delistedStatuses.includes(s));
    }

    function isDelistedStatus() {
        const delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
        return (selectedStatus || []).some((s) => delistedStatuses.includes(s));
    }

    function getSelectedDate() {
        if (isSoldOrDelistedStatus()) {
            return 'all';
        }
        return hsMobileDateSale || 'all';
    }

    function getSelectedDateSold() {
        if (!isSoldOrDelistedStatus()) {
            return 'all';
        }
        return hsMobileDateSold || 'all';
    }
    
    
    
  document.querySelectorAll('[data-type="status"]').forEach(btn => {
    btn.addEventListener('click', function () {

       const value = this.dataset.value;

        if (value === 'Expired') {
            selectedStatus = ['Expired', 'Terminated', 'Suspended'];
        }
        
        else if (value === 'Sold') {
            selectedStatus = [
                'Sold',
                'Sold Conditional',
                'Sold Conditional Escape',
                'Leased',
                'Leased Conditional'
            ];
            hsMobileDateSold = hsMobileDateSold || 'all';
        }
        
        else if (value === 'Active') {
            selectedStatus = [
                'New',
                'Price Change',
                'Extension',
                'Previous Status'
            ];
        }
        
        else {
            selectedStatus = [value];
        }

        syncFilterUiFromState();
        syncDateRadiosFromState();
        if (typeof updateSplitFilterLabels === 'function') {
            updateSplitFilterLabels();
        }
        loadProperties();
    });
});

document.querySelectorAll('input[name="date"], input[name="date-sold"], input[name="date-delisted"]').forEach(radio => {
    radio.addEventListener('change', function () {
        if (this.name === 'date') {
            hsMobileDateSale = this.value;
        } else {
            hsMobileDateSold = this.value;
        }
        syncDateRadiosFromState();
        if (typeof updateSplitFilterLabels === 'function') {
            updateSplitFilterLabels();
        }
        if (typeof updateMobileFilterLabels === 'function') {
            updateMobileFilterLabels();
        }
        loadProperties();
    });
});
    
    
    
   document.querySelectorAll('.transaction-item').forEach(item => {
        item.addEventListener('click', function() {
            selectedTransaction = this.dataset.transaction;
            document.getElementById('transactionDropdown').innerText = selectedTransaction;
    
            // Remove active class from all items
            document.querySelectorAll('.transaction-item').forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
    
            loadProperties();
        });
    });
    
    
    const saveBtn = document.querySelector('.btn-save');
const propertyButton = document.querySelector('.property-selector');
const dropdown = document.getElementById('dropdown-menu-type');

if (saveBtn) {
    saveBtn.addEventListener('click', function () {

        selectedSubTypes = [];

        const checkboxes = document.querySelectorAll('.dropdown-card input[type="checkbox"]');
        const checkedBoxes = document.querySelectorAll('.dropdown-card input[type="checkbox"]:checked');

        const allCheckbox = document.querySelector('.dropdown-card input[value="All"]');

        // ==============================
        // If "All Properties" selected
        // ==============================
        if (allCheckbox && allCheckbox.checked) {

            // Uncheck all others
            checkboxes.forEach(cb => {
                if (cb.value !== "All") cb.checked = false;
            });

            selectedSubTypes = [];
            propertyButton.innerText = "All Properties";

        } else {

            // Collect selected types (except All)
            checkedBoxes.forEach(cb => {
                if (cb.value !== "All") {
                    selectedSubTypes.push(cb.value);
                }
            });

            if (selectedSubTypes.length === 0) {
                propertyButton.innerText = "All Properties";
            }
            else if (selectedSubTypes.length === 1) {
                propertyButton.innerText = selectedSubTypes[0];
            }
            else {
                propertyButton.innerText =
                    `${selectedSubTypes[0]} +${selectedSubTypes.length - 1}`;
            }
        }

        // Hide dropdown
        if (dropdown) {
            dropdown.style.display = 'none';
            dropdown.classList.remove('active');
        }

        loadProperties();
    });
}

document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();

        const dropdown = this.closest('.dropdown');
        if (!dropdown) return;

        // close dropdown
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu) menu.style.display = 'none';

        // reset ONLY this dropdown
        resetDropdown(dropdown);

        loadProperties();
    });
});

function resetDropdown(dropdown) {

    // =========================
    // PROPERTY TYPE DROPDOWN
    // =========================
    if (dropdown.querySelector('.property-selector')) {

        selectedSubTypes = [];

        dropdown.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });

        const btn = dropdown.querySelector('.property-selector');
        if (btn) btn.innerText = "All Properties";
    }

    // =========================
    // PRICE DROPDOWN
    // =========================
    if (dropdown.querySelector('.slider-min')) {

        selectedMinPrice = 0;
        selectedMaxPrice = 500000000;

        if (minSlider) minSlider.value = 0;
        if (maxSlider) maxSlider.value = 500000000;

        updatePriceDisplay();
    }

    // =========================
    // DATE / STATUS DROPDOWN
    // =========================
    if (dropdown.querySelector('input[name="date"]')) {

        dropdown.querySelectorAll('input[name="date"]').forEach(r => {
            r.checked = (r.value === 'all');
        });

        selectedStatus = null;
    }

    // =========================
    // SOLD / DE-LISTED DATE DROPDOWN
    // =========================
    if (dropdown.querySelector('input[name="date-sold"], input[name="date-delisted"]')) {
        dropdown.querySelectorAll('input[name="date-sold"], input[name="date-delisted"]').forEach((r) => {
            const isAll = r.value === 'all';
            r.checked = isAll;
            r.closest('.radio-item')?.classList.toggle('selected', isAll);
        });
        hsMobileDateSold = 'all';
    }

    // =========================
    // CHIP DROPDOWNS (More filter)
    // =========================
    if (dropdown.querySelector('.chip-group')) {

        dropdown.querySelectorAll('.chip-group').forEach(group => {

            group.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));

            const first = group.querySelector('.chip');
            if (first) first.classList.add('active');
        });

        selectedBedrooms = null;
        selectedBathrooms = null;
        selectedBasement = null;
        selectedBasement1 = null;
    }
}
    
    
   const minSlider = document.querySelector('.slider-min');
    const maxSlider = document.querySelector('.slider-max');
    const applyBtn = document.querySelector('.btn-apply');
    const PRICE_SALE_MAX = 5000000;
    const PRICE_LEASE_MAX = 50000;
    const PRICE_NO_LIMIT = 500000000;

    if (!selectedMaxPrice || selectedMaxPrice === PRICE_NO_LIMIT) {
        selectedMaxPrice = PRICE_SALE_MAX;
    }

    const transactionItems = document.querySelectorAll('.transaction-item');
    const priceScale = document.querySelector('.price-scale');
    
    
const SALE_CONFIG = {
    min: 0,
    max: 5000000,
    step: 50000
};

const LEASE_CONFIG = {
    min: 0,
    max: 50000,
    step: 500
};



function updatePriceScale(max) {
    if (max === 50000) {
        priceScale.innerHTML = `
            <span>$0</span>
            <span>$10K</span>
            <span>$20K</span>
            <span>$30K</span>
            <span>$40K</span>
            <span>$50K</span>
        `;
    } else {
        priceScale.innerHTML = `
            <span>$0</span>
            <span>$500K</span>
            <span>$1M</span>
            <span>$3M</span>
            <span>$5M</span>
            <span>Max</span>
        `;
    }
}


function applyPriceConfig(config) {
    // Update slider attributes
    minSlider.min = config.min;
    minSlider.max = config.max;
    minSlider.step = config.step;

    maxSlider.min = config.min;
    maxSlider.max = config.max;
    maxSlider.step = config.step;

    // Reset values
    selectedMinPrice = config.min;
    selectedMaxPrice = config.max;

    minSlider.value = selectedMinPrice;
    maxSlider.value = selectedMaxPrice;
    
    updatePriceScale(config.max);
    syncPriceSlidersFromState();
    updatePriceDisplay();
}

transactionItems.forEach(item => {
    item.addEventListener('click', function () {
        const type = this.dataset.transaction;

        if (type === "For Lease") {
            applyPriceConfig(LEASE_CONFIG);
        } else {
            applyPriceConfig(SALE_CONFIG);
        }
    });
});
    
    
    function formatPriceShort(value) {
        const num = parseInt(value, 10) || 0;
        if (num >= 1_000_000) {
            const millions = num / 1_000_000;
            return (millions % 1 === 0 ? millions.toFixed(0) : millions.toFixed(1).replace(/\.0$/, '')) + 'M';
        }
        if (num >= 1_000) {
            const thousands = num / 1_000;
            return (thousands % 1 === 0 ? thousands.toFixed(0) : thousands.toFixed(1).replace(/\.0$/, '')) + 'K';
        }
        return num.toLocaleString('en-CA');
    }

    function isDefaultMaxPrice(value) {
        const isLease = selectedTransaction === 'For Lease';
        const cap = isLease ? PRICE_LEASE_MAX : PRICE_SALE_MAX;
        return !value || value >= PRICE_NO_LIMIT || value >= cap;
    }

    function formatPriceFilterLabel(value, isMax = false) {
        if (isMax && isDefaultMaxPrice(value)) {
            return 'Max';
        }

        const num = parseInt(value, 10) || 0;
        if (num <= 0) {
            return '$0';
        }

        return '$' + formatPriceShort(num);
    }

    function buildPriceFilterText() {
        return `${formatPriceFilterLabel(selectedMinPrice)} - ${formatPriceFilterLabel(selectedMaxPrice, true)}`;
    }

    function syncPriceSlidersFromState() {
        const cap = selectedTransaction === 'For Lease' ? PRICE_LEASE_MAX : PRICE_SALE_MAX;
        const maxVal = isDefaultMaxPrice(selectedMaxPrice) ? cap : selectedMaxPrice;

        if (minSlider) minSlider.value = selectedMinPrice || 0;
        if (maxSlider) maxSlider.value = maxVal;

        const mMinSlider = document.querySelector('.hs-m-slider-min');
        const mMaxSlider = document.querySelector('.hs-m-slider-max');
        if (mMinSlider) mMinSlider.value = selectedMinPrice || 0;
        if (mMaxSlider) mMaxSlider.value = maxVal;
    }

    function updatePriceDisplay() {
        const text = buildPriceFilterText();

        document.querySelectorAll('.price-filter-btn-label, .dropdown-card .price-display, .hs-m-price-label').forEach((el) => {
            el.textContent = text;
        });

        const priceBtn = document.getElementById('hsPriceFilterBtn');
        if (priceBtn) {
            const isFiltered = (selectedMinPrice || 0) > 0 || !isDefaultMaxPrice(selectedMaxPrice);
            priceBtn.classList.toggle('filter-active', isFiltered);
        }
    }
    const dropdownMenu = document.querySelector('.dropdown-menu-min');

    // Prevent dropdown from closing when clicking inside
    if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    if (minSlider && maxSlider) {
        const urlMin = selectedMinPrice;
        const urlMax = selectedMaxPrice;
        applyPriceConfig(selectedTransaction === 'For Lease' ? LEASE_CONFIG : SALE_CONFIG);
        if (urlMin > 0) {
            minSlider.value = urlMin;
            selectedMinPrice = parseInt(minSlider.value, 10);
        }
        if (!isDefaultMaxPrice(urlMax)) {
            maxSlider.value = urlMax;
            selectedMaxPrice = parseInt(maxSlider.value, 10);
        }
        syncPriceSlidersFromState();
        updatePriceDisplay();
    }

    const mMinSlider = document.querySelector('.hs-m-slider-min');
    const mMaxSlider = document.querySelector('.hs-m-slider-max');

    if (mMinSlider) {
        mMinSlider.addEventListener('input', function () {
            if (parseInt(this.value, 10) >= parseInt(mMaxSlider?.value || 0, 10)) {
                this.value = parseInt(mMaxSlider.value, 10) - parseInt(this.step || 1, 10);
            }
            selectedMinPrice = parseInt(this.value, 10);
            if (minSlider) minSlider.value = this.value;
            updatePriceDisplay();
        });
    }

    if (mMaxSlider) {
        mMaxSlider.addEventListener('input', function () {
            if (parseInt(this.value, 10) <= parseInt(mMinSlider?.value || 0, 10)) {
                this.value = parseInt(mMinSlider.value, 10) + parseInt(this.step || 1, 10);
            }
            selectedMaxPrice = parseInt(this.value, 10);
            if (maxSlider) maxSlider.value = this.value;
            updatePriceDisplay();
        });
    }
    
    // Min slider
   if (minSlider) minSlider.addEventListener('input', function () {
    if (parseInt(this.value) >= parseInt(maxSlider.value)) {
        this.value = maxSlider.value - parseInt(minSlider.step);
    }

    selectedMinPrice = parseInt(this.value);
    if (mMinSlider) mMinSlider.value = this.value;
    updatePriceDisplay();
});

// Max slider
if (maxSlider) maxSlider.addEventListener('input', function () {
    if (parseInt(this.value) <= parseInt(minSlider.value)) {
        this.value = parseInt(minSlider.value) + parseInt(maxSlider.step);
    }

    selectedMaxPrice = parseInt(this.value);
    if (mMaxSlider) mMaxSlider.value = this.value;
    updatePriceDisplay();
});
    
    // Apply button
    if (applyBtn && dropdownMenu) applyBtn.addEventListener('click', function () {
        selectedMinPrice = parseInt(minSlider?.value || selectedMinPrice || 0, 10);
        selectedMaxPrice = parseInt(maxSlider?.value || selectedMaxPrice || 0, 10);
        dropdownMenu.style.display = 'none';
        bustMapFetchCache();
        loadProperties();
    });
        
    
    
   const applyAll = document.querySelector('.apply-all');
const dropdownMenu1 = document.querySelector('.dropdown-menu-all');

// Prevent dropdown from closing when clicking inside
dropdownMenu1.addEventListener('click', function (e) {
    e.stopPropagation();
});

// CHIP FILTERS
document.querySelectorAll('.chip-group .chip').forEach(chip => {

    chip.addEventListener('click', function () {

        const group = this.closest('.chip-group');
        const type = group.dataset.type;
        const value = this.innerText.trim();

        group.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');

       if (value === 'All') {
            if (type.toLowerCase() === 'bedroom') selectedBedrooms = null;
            if (type.toLowerCase() === 'bathroom') selectedBathrooms = null;
            if (type.toLowerCase() === 'basement') selectedBasement = null;
             if (type.toLowerCase() === 'basement1') selectedBasement1 = null;
        } else {
            if (type.toLowerCase() === 'bedroom') selectedBedrooms = value;
            if (type.toLowerCase() === 'bathroom') selectedBathrooms = value;
            if (type.toLowerCase() === 'basement') selectedBasement = value;
            if (type.toLowerCase() === 'basement1') selectedBasement1 = value;
        }

    });

});

// SQUARE SLIDER
function updateSquareTitle() {

    selectedMinSquare = parseInt(squareMinSlider.value);
    selectedMaxSquare = parseInt(squareMaxSlider.value);

    if (selectedMinSquare === 0 && selectedMaxSquare === 4000) {
        squareTitle.innerText = 'Square Footage: Unspecified - Max';
        return;
    }

    // if max is 5000 → show 5000+
    if (selectedMaxSquare === 4000) {
        squareTitle.innerText =
            `Square Footage: ${selectedMinSquare.toLocaleString()} - 4000+ sqft`;
    } else {
        squareTitle.innerText =
            `Square Footage: ${selectedMinSquare.toLocaleString()} - ${selectedMaxSquare.toLocaleString()} sqft`;
    }
}

squareMinSlider.addEventListener('input', updateSquareTitle);
squareMaxSlider.addEventListener('input', updateSquareTitle);

// APPLY BUTTON
applyAll.addEventListener('click', function () {

    if (dropdownMenu1) {
        dropdownMenu1.style.display = 'none';
    }

    loadProperties(); // only loads here
});




document.querySelector('.clear-btn-main').addEventListener('click', function () {

    // ==============================
    // 1️⃣ Reset JS Variables
    // ==============================
    selectedTransaction = 'all';
    selectedStatus = 'all';
    selectedSubTypes = [];
    activeCityPolygon = null;
    activeCityGeometryType = null;
    selectedMinPrice = 0;
    selectedMaxPrice = 500000000;
    selectedBedrooms = null;
    selectedBathrooms = null;
    
    // ==============================
    // Reset Transaction UI
    // ==============================
    document.getElementById('transactionDropdown').innerText = 'Transaction';
    document.querySelectorAll('.transaction-item')
        .forEach(i => i.classList.remove('active'));

    // ==============================
    // Reset Status Buttons
    // ==============================
    document.querySelectorAll('[data-type="status"]')
        .forEach(b => b.classList.remove('active'));

    // ==============================
    //  Reset Checkboxes
    // ==============================
    document.querySelectorAll('.dropdown-card input[type="checkbox"]')
        .forEach(cb => cb.checked = false);

    // ==============================
    // Reset Price Sliders
    // ==============================
    minSlider.value = 0;
    maxSlider.value = 500000000;
    syncPriceSlidersFromState();
    updatePriceDisplay();

    // ==============================
    //  Reset Bedroom / Bathroom Chips
    // ==============================
    document.querySelectorAll('.chip-group .chip')
        .forEach(chip => chip.classList.remove('active'));

    // ==============================
    // Reset Square Footage
    // ==============================
    selectedMaxSquare = 4000;
    selectedMinSquare = 0;
    squareTitle.innerText = 'Square Footage: Unspecified - Max';
//alert('hello');
    // ==============================
    //  Reload Map With Defaults
    // ==============================
    
    loadProperties();
});






function getCityFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const city = params.get('city') || '';

    if (!city) {
        return '';
    }

    return decodeURIComponent(city.replace(/\+/g, ' ')).trim();
}

function isBareMapPath() {
    const path = window.location.pathname.toLowerCase().replace(/\/$/, '');

    return path === '/map' || path === '/on/map';
}

function shouldAutoCenterOnUserLocation() {
    if (userHasMovedMap) {
        return false;
    }

    if (getLatLngFromUrl()) {
        return false;
    }

    const pathCity = (pathFilters.city || '').trim().toLowerCase();
    if (pathCity && pathCity !== 'ontario') {
        return false;
    }

    const queryCity = getCityFromUrl().toLowerCase();
    if (queryCity && queryCity !== 'ontario') {
        return false;
    }

    return isBareMapPath() || (!pathCity && !queryCity);
}

function getZoomForDetectedLocation(detectedLocation) {
    if (!detectedLocation) {
        return 11;
    }

    if (detectedLocation.source === 'browser') {
        if (detectedLocation.accuracy && detectedLocation.accuracy <= 250) {
            return 13;
        }

        return 12;
    }

    if (detectedLocation.source === 'ip' && detectedLocation.accuracy === 'city') {
        return 11;
    }

    if (detectedLocation.source === 'ip') {
        return 12;
    }

    return detectedLocation.zoom || 11;
}

function flyMapToDetectedLocation(detectedLocation) {
    if (!map || !detectedLocation || !Number.isFinite(detectedLocation.lat) || !Number.isFinite(detectedLocation.lng)) {
        loadProperties();
        return;
    }

    autoCenteringMap = true;
    let centeringFinished = false;

    const finishCentering = () => {
        if (centeringFinished) {
            return;
        }
        centeringFinished = true;
        autoCenteringMap = false;
        loadProperties();
    };

    map.once('moveend', finishCentering);

    map.flyTo({
        center: [detectedLocation.lng, detectedLocation.lat],
        zoom: getZoomForDetectedLocation(detectedLocation),
        duration: 1500,
        essential: true,
    });

    // flyTo may not fire moveend when already at the same center
    setTimeout(finishCentering, 1800);
}

function applyOntarioDefaultLocation() {
    const fallback = window.SerikVisitorLocation
        ? window.SerikVisitorLocation.ONTARIO_DEFAULT
        : { lat: 43.6532, lng: -79.3832, zoom: 11 };

    flyMapToDetectedLocation(fallback);
}

// Center the map on the visitor's detected area (IP/browser) and zoom in so
// nearby properties are visible first, WITHOUT locking to a city polygon.
async function centerOnVisitorAreaNoLock() {
    activeCityPolygon = null;
    activeCityGeometryType = null;
    selectedCity = '';

    const detector = window.SerikVisitorLocation;
    if (!detector) {
        applyOntarioDefaultLocation();
        return;
    }

    try {
        const location = await detector.detectLocation({ preferCached: true, preferBrowser: false });

        if (location && Number.isFinite(location.lat) && Number.isFinite(location.lng)) {
            // Force a closer zoom regardless of accuracy, but never lock.
            flyMapToDetectedLocation({
                lat: location.lat,
                lng: location.lng,
                source: location.source,
                zoom: location.source === 'browser' ? 12 : 11,
            });
        } else {
            applyOntarioDefaultLocation();
        }
    } catch (e) {
        console.warn('Visitor area centering failed', e);
        applyOntarioDefaultLocation();
    }
}

function getVisitorCityFromCookie() {
    if (window.SerikVisitorLocation) {
        return window.SerikVisitorLocation.getVisitorCity();
    }

    const match = document.cookie.match(/(?:^|;\s*)serik_visitor_city=([^;]+)/);
    if (match) {
        return decodeURIComponent(match[1]).trim();
    }

    try {
        return (localStorage.getItem('serik_visitor_city') || '').trim();
    } catch (e) {
        return '';
    }
}

function clearCityBoundaryLock() {
    selectedCity = '';
    cityFromUrl = '';
    seoCitySlug = 'ontario';
    activeCityPolygon = null;
    activeCityGeometryType = null;

    if (map && map.getLayer('city-fill')) {
        map.setFilter('city-fill', ['==', ['get', 'NAME_3'], '']);
        map.setFilter('city-outline', ['==', ['get', 'NAME_3'], '']);
    }
}

async function setMapToUserLocation() {
    if (userHasMovedMap) {
        loadProperties();
        return;
    }

    const detector = window.SerikVisitorLocation;
    if (!detector) {
        applyOntarioDefaultLocation();
        return;
    }

    try {
        const location = await detector.detectLocation({
            preferCached: true,
            preferBrowser: false,
        });

        if (!location) {
            applyOntarioDefaultLocation();
            return;
        }

        activeCityPolygon = null;
        activeCityGeometryType = null;

        // City-level IP hit: center + zoom on the area but DON'T lock to the
        // city polygon, so the visitor can freely pan/explore nearby areas.
        if (location.accuracy === 'city' && location.city) {
            selectedCity = '';
            cityFromUrl = '';
            seoCitySlug = slugify(location.city);

            if (Number.isFinite(location.lat) && Number.isFinite(location.lng)) {
                flyMapToDetectedLocation(location);
            } else {
                applyOntarioDefaultLocation();
            }
            return;
        }

        if (Number.isFinite(location.lat) && Number.isFinite(location.lng)) {
            selectedCity = '';
            cityFromUrl = '';
            if (location.city) {
                seoCitySlug = slugify(location.city);
            }
            flyMapToDetectedLocation(location);
            return;
        }

        selectedCity = '';
        cityFromUrl = '';
        flyMapToDetectedLocation(location);
    } catch (e) {
        console.warn('Visitor location detection failed', e);
        applyOntarioDefaultLocation();
    }
}




 



  let moveTimer;
function getSubtypesFromUrl() {
    const params = new URLSearchParams(window.location.search);
    let subtypes = params.get('subtypes') || '';

    if (!subtypes) return [];

    // Replace '+' with space, then decode URI
    subtypes = decodeURIComponent(subtypes.replace(/\+/g, ' '));

    // Split by comma in case multiple subtypes are passed
    return subtypes.split(',').map(s => s.trim());
}


function getLatLngFromUrl() {
    const params = new URLSearchParams(window.location.search);

    const lat = parseFloat(params.get('lat'));
    const lng = parseFloat(params.get('lng'));

    if (isNaN(lat) || isNaN(lng)) return null;

    return { lat, lng };
}



function getTransactionFromUrl() {
    const params = new URLSearchParams(window.location.search);
    let transactionURL = params.get('transaction') || '';

    if (!transactionURL) return [];

    // Replace '+' with space, then decode URI
    transactionURL = decodeURIComponent(transactionURL.replace(/\+/g, ' '));

    // Split by comma in case multiple subtypes are passed
    return transactionURL.split(',').map(s => s.trim());
}


    map.on('dragstart', function () {
        if (!autoCenteringMap) {
            userHasMovedMap = true;
            // Keep the boundary + polygon clip when a city/area is locked.
            if (!activeCityPolygon) clearCityBoundaryLock();
        }
    });

    map.on('zoomstart', function (event) {
        if (!autoCenteringMap && event.originalEvent) {
            userHasMovedMap = true;
            if (!activeCityPolygon) clearCityBoundaryLock();
        }
    });

    map.on('moveend', function () {
        if (!mapLayersReady) return;
        clearTimeout(moveTimer);
        moveTimer = setTimeout(() => {
            if (!mapMovedEnoughToRefetch()) return;
            skipSeoUrlOnNextLoad = true;
            loadProperties({ fromMapMove: true });
        }, 500);
    });
    
 
    let controller = null;
    let requestTimer = null;
    let isLoading = false;
    let skipSeoUrlOnNextLoad = false;
    let lastMapFetchKey = '';
    let lastFetchCenter = null;
    let lastFetchZoom = null;

    if (!selectedTransaction) {
        selectedTransaction = 'For Sale';
    }
    if (!selectedStatus || !selectedStatus.length) {
        selectedStatus = [
            'New',
            'Price Change',
            'Extension',
            'Previous Status'
        ];
    }



function slugify(text) {
    return text
        .toString()
        .trim()
        .toLowerCase()
        .replace(/&/g, 'and')
        .replace(/\s+/g, '-')
        .replace(/--+/g, '-');
}

const ONTARIO_FETCH_BOUNDS = { south: 41.6, north: 56.9, west: -95.2, east: -74.0 };

function pointInPolygon(lngLat, polygonCoords) {
    if (!lngLat || !polygonCoords || !polygonCoords[0]) return true;
    const x = lngLat[0];
    const y = lngLat[1];
    const ring = polygonCoords[0];
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0], yi = ring[i][1];
        const xj = ring[j][0], yj = ring[j][1];
        const intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi + 0.0) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
}

function filterFeaturesByCityPolygon(features) {
    if (!activeCityPolygon || !Array.isArray(features)) return features;

    return features.filter((f) => {
        const coords = f.geometry?.coordinates;
        if (!coords) return false;

        if (activeCityGeometryType === 'MultiPolygon') {
            return activeCityPolygon.some((poly) => pointInPolygon(coords, poly));
        }

        return pointInPolygon(coords, activeCityPolygon);
    });
}

function filterFeaturesByWatchedPolygon(features) {
    if (!activeWatchedPolygon || !Array.isArray(features)) return features;
    return features.filter((f) => {
        const coords = f.geometry?.coordinates;
        return coords && pointInPolygon(coords, activeWatchedPolygon);
    });
}

function getPolygonFetchBounds(polygonCoords) {
    const ring = polygonCoords[0] || [];
    let south = Infinity, north = -Infinity, west = Infinity, east = -Infinity;
    ring.forEach(([lng, lat]) => {
        south = Math.min(south, lat);
        north = Math.max(north, lat);
        west = Math.min(west, lng);
        east = Math.max(east, lng);
    });
    return {
        south: Math.max(south, ONTARIO_FETCH_BOUNDS.south),
        north: Math.min(north, ONTARIO_FETCH_BOUNDS.north),
        west: Math.max(west, ONTARIO_FETCH_BOUNDS.west),
        east: Math.min(east, ONTARIO_FETCH_BOUNDS.east),
    };
}

function closeWatchedDropdown() {
    document.querySelectorAll('.watched-dropdown .dropdown-menu').forEach((m) => {
        m.style.display = 'none';
    });
}

function showWatchedPolygonOnMap(polygonCoords) {
    if (!draw || !polygonCoords) return;
    draw.deleteAll();
    draw.add({
        type: 'Feature',
        properties: {},
        geometry: { type: 'Polygon', coordinates: polygonCoords },
    });
}

function fitMapToPolygon(polygonCoords) {
    if (!map || !polygonCoords?.[0]?.length) return;
    const bounds = polygonCoords[0].reduce((b, coord) => b.extend(coord),
        new maplibregl.LngLatBounds(polygonCoords[0][0], polygonCoords[0][0]));
    map.fitBounds(bounds, { padding: 60, duration: 800 });
}

function initMapDraw() {
    if (draw || !map || typeof MapboxDraw === 'undefined') return;

    draw = new MapboxDraw({
        displayControlsDefault: false,
        controls: { polygon: false, trash: false },
        styles: [
            {
                id: 'gl-draw-polygon-fill',
                type: 'fill',
                filter: ['all', ['==', '$type', 'Polygon'], ['!=', 'mode', 'static']],
                paint: { 'fill-color': '#0255a1', 'fill-opacity': 0.25 },
            },
            {
                id: 'gl-draw-polygon-stroke',
                type: 'line',
                filter: ['all', ['==', '$type', 'Polygon'], ['!=', 'mode', 'static']],
                paint: { 'line-color': '#0255a1', 'line-width': 3 },
            },
            {
                id: 'gl-draw-polygon-and-line-vertex-active',
                type: 'circle',
                filter: ['all', ['==', 'meta', 'vertex'], ['==', '$type', 'Point']],
                paint: {
                    'circle-radius': 8,
                    'circle-color': '#ee2128',
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 1,
                },
            },
            {
                id: 'gl-draw-polygon-midpoint',
                type: 'circle',
                filter: ['all', ['==', 'meta', 'midpoint']],
                paint: {
                    'circle-radius': 8,
                    'circle-color': '#ee2128',
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 2,
                },
            },
            {
                id: 'gl-draw-point-active',
                type: 'circle',
                filter: ['all', ['==', '$type', 'Point'], ['==', 'active', 'true']],
                paint: { 'circle-radius': 8, 'circle-color': '#ee2128' },
            },
        ],
    });
    map.addControl(draw, 'top-left');

    function showSavePopup() {
        const popup = document.getElementById('polygon-popup');
        const nameInput = document.getElementById('polygon-name');
        if (popup) popup.style.display = 'block';
        if (nameInput) nameInput.value = editingWatchedIndex !== null
            ? (JSON.parse(localStorage.getItem('watchedAreas') || '[]')[editingWatchedIndex]?.title || '')
            : '';
    }

    function hideSavePopup() {
        const popup = document.getElementById('polygon-popup');
        if (popup) popup.style.display = 'none';
        editingWatchedIndex = null;
    }

    function saveWatchedArea(area) {
        const savedAreas = JSON.parse(localStorage.getItem('watchedAreas') || '[]');
        if (editingWatchedIndex !== null && savedAreas[editingWatchedIndex]) {
            savedAreas[editingWatchedIndex] = area;
        } else {
            savedAreas.push(area);
        }
        localStorage.setItem('watchedAreas', JSON.stringify(savedAreas));
    }

    function renderWatchedAreas() {
        const container = document.querySelector('.watched-wrapper');
        if (!container) return;

        container.querySelectorAll('.watched-card').forEach((c) => c.remove());
        const saved = JSON.parse(localStorage.getItem('watchedAreas') || '[]');

        saved.forEach((area, index) => {
            const card = document.createElement('div');
            const isActive = activeWatchedPolygon
                && JSON.stringify(activeWatchedPolygon) === JSON.stringify(area.polygon);
            card.className = 'watched-card' + (isActive ? ' active' : '');
            card.innerHTML = `
                <h4 class="watched-title">${area.title}</h4>
                <div class="watched-actions">
                    <button type="button" class="btn-outline">Edit</button>
                    <button type="button" class="btn-filled">View</button>
                </div>
            `;

            const newAreaBtn = container.querySelector('.new-area');
            container.insertBefore(card, newAreaBtn);

            card.querySelector('.btn-filled')?.addEventListener('click', () => {
                activeWatchedPolygon = area.polygon;
                lastMapFetchKey = '';
                lastFetchCenter = null;
                lastFetchZoom = null;
                showWatchedPolygonOnMap(area.polygon);
                fitMapToPolygon(area.polygon);
                closeWatchedDropdown();
                renderWatchedAreas();
                skipSeoUrlOnNextLoad = true;
                loadProperties();
            });

            card.querySelector('.btn-outline')?.addEventListener('click', () => {
                editingWatchedIndex = index;
                currentPolygon = area.polygon;
                isDrawing = false;
                showWatchedPolygonOnMap(area.polygon);
                const featureIds = draw.getAll().features.map((f) => f.id);
                if (featureIds.length > 0) {
                    draw.changeMode('direct_select', { featureId: featureIds[0] });
                }
                closeWatchedDropdown();
                showSavePopup();
            });
        });
    }

    function startNewWatchedArea() {
        closeWatchedDropdown();
        draw.deleteAll();
        currentPolygon = null;
        editingWatchedIndex = null;
        isDrawing = true;
        draw.changeMode('draw_polygon');
        if (map.getCanvas()) map.getCanvas().style.cursor = 'crosshair';
    }

    document.querySelector('.btn-new')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        startNewWatchedArea();
    });

    map.on('draw.create', function (e) {
        if (!isDrawing && editingWatchedIndex === null) return;
        currentPolygon = e.features[0].geometry.coordinates;
        isDrawing = false;
        if (map.getCanvas()) map.getCanvas().style.cursor = '';
        showSavePopup();
    });

    map.on('draw.update', function (e) {
        if (e.features?.[0]) {
            currentPolygon = e.features[0].geometry.coordinates;
        }
    });

    document.getElementById('savePolygon')?.addEventListener('click', () => {
        if (!currentPolygon) return;
        const name = document.getElementById('polygon-name')?.value?.trim();
        if (!name) {
            alert('Please enter a name for this area');
            return;
        }
        const area = { title: name, sub: 'Email me daily', polygon: currentPolygon };
        saveWatchedArea(area);
        activeWatchedPolygon = currentPolygon;
        lastMapFetchKey = '';
        lastFetchCenter = null;
        lastFetchZoom = null;
        renderWatchedAreas();
        draw.deleteAll();
        showWatchedPolygonOnMap(activeWatchedPolygon);
        currentPolygon = null;
        hideSavePopup();
        skipSeoUrlOnNextLoad = true;
        loadProperties();
    });

    document.getElementById('cancelPolygon')?.addEventListener('click', () => {
        draw.deleteAll();
        currentPolygon = null;
        hideSavePopup();
        if (map.getCanvas()) map.getCanvas().style.cursor = '';
    });

    const watchedContainer = document.querySelector('.watched-wrapper');
    watchedContainer?.querySelector('.watched-header .clear-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        localStorage.removeItem('watchedAreas');
        activeWatchedPolygon = null;
        draw.deleteAll();
        currentPolygon = null;
        hideSavePopup();
        watchedContainer.querySelectorAll('.watched-card').forEach((card) => card.remove());
        lastMapFetchKey = '';
        loadProperties();
    });

    renderWatchedAreas();
    window.hsStartWatchedAreaDraw = startNewWatchedArea;
}

function getFetchBoundsFromMap() {
    if (activeWatchedPolygon && activeWatchedPolygon[0]?.length) {
        return getPolygonFetchBounds(activeWatchedPolygon);
    }
    const bounds = map.getBounds();
    return {
        south: Math.max(bounds.getSouth(), ONTARIO_FETCH_BOUNDS.south),
        north: Math.min(bounds.getNorth(), ONTARIO_FETCH_BOUNDS.north),
        west: Math.max(bounds.getWest(), ONTARIO_FETCH_BOUNDS.west),
        east: Math.min(bounds.getEast(), ONTARIO_FETCH_BOUNDS.east),
    };
}


function mapMovedEnoughToRefetch() {
    const zoom = Math.round(map.getZoom());
    const center = map.getCenter();
    if (lastFetchZoom === null || lastFetchCenter === null) {
        return true;
    }
    if (zoom !== lastFetchZoom) {
        return true;
    }
    const bounds = map.getBounds();
    const latSpan = bounds.getNorth() - bounds.getSouth();
    const lngSpan = bounds.getEast() - bounds.getWest();
    const movedLat = Math.abs(center.lat - lastFetchCenter.lat);
    const movedLng = Math.abs(center.lng - lastFetchCenter.lng);
    return movedLat > latSpan * 0.2 || movedLng > lngSpan * 0.2;
}




    function loadProperties(options = {}) {
        const fromMapMove = options.fromMapMove === true;

        if (!skipSeoUrlOnNextLoad) {
            updateSeoUrl();
        }
        if (!fromMapMove) {
            bustMapFetchCache();
        }
        skipSeoUrlOnNextLoad = false;
    
        if (!map || !mapLayersReady || !map.getSource('properties')) return;
    
        clearTimeout(requestTimer);
        
        if (!userHasMovedMap && !selectedCity && cityFromUrl && cityFromUrl.toLowerCase() !== 'ontario') {
            selectedCity = cityFromUrl;
        }
    
        requestTimer = setTimeout(() => {
    
            if (isLoading && controller) {
                controller.abort();
            }
    
            controller = new AbortController();
            isLoading = true;

            const selectedDate = getSelectedDate();
            const selectedDateSold = getSelectedDateSold();
            const effectiveSubtypes = getEffectiveSubtypes();
            let apiCity = userHasMovedMap ? '' : selectedCity;
            if (apiCity && apiCity.toLowerCase() === 'ontario') {
                apiCity = '';
            }
            if (!apiCity && !userHasMovedMap && cityFromUrl && cityFromUrl.toLowerCase() !== 'ontario') {
                apiCity = cityFromUrl;
            }
    
            const fetchBounds = getFetchBoundsFromMap();
            const params = new URLSearchParams({
                south: fetchBounds.south,
                north: fetchBounds.north,
                west: fetchBounds.west,
                east: fetchBounds.east,
                zoom: Math.round(map.getZoom()),
                transaction: selectedTransaction || '',
                min_price: selectedMinPrice || 0,
                max_price: isDefaultMaxPrice(selectedMaxPrice) ? PRICE_NO_LIMIT : selectedMaxPrice,
                status: selectedStatus ? selectedStatus.join(',') : '',
                square_min: selectedMinSquare || 0,
                square_max: selectedMaxSquare === 4000 ? '' : selectedMaxSquare,
                bedrooms: selectedBedrooms || '',
                bathrooms: selectedBathrooms || '',
                basement: selectedBasement || '',
                basement1: selectedBasement1 || '',
                subtypes: effectiveSubtypes.join(','),
                city: apiCity || ''
            });

            if (selectedDate && selectedDate !== 'all') {
                params.set('date', selectedDate);
            }
            if (selectedDateSold && selectedDateSold !== 'all') {
                params.set('date_sold', selectedDateSold);
            }

            const fetchKey = params.toString();
            if (fetchKey === lastMapFetchKey) {
                isLoading = false;
                return;
            }
    
            fetch(`/api/v1/map-properties?${params.toString()}`, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(res => {
                if (!res.ok) throw new Error("Network error");
                return res.json();
            })
            .then(data => {
                if (!map.getSource('properties')) return;

                if (!data || data.type !== 'FeatureCollection' || !Array.isArray(data.features)) {
                    console.error('Invalid map properties response', data);
                    map.getSource('properties').setData({ type: 'FeatureCollection', features: [] });
                    lastMapFetchKey = '';
                    return;
                }

                lastMapFetchKey = fetchKey;
                lastFetchCenter = map.getCenter();
                lastFetchZoom = Math.round(map.getZoom());

                // Always clip to the active city polygon (no-op when no city is
                // locked) so panning/zooming stays inside the selected area.
                const filteredFeatures = filterFeaturesByCityPolygon(
                    filterFeaturesByWatchedPolygon(data.features)
                );
                const featureCollection = { type: 'FeatureCollection', features: filteredFeatures };
                map.getSource('properties').setData(featureCollection);

                lastMapFeatures = filteredFeatures;

                const total = filteredFeatures.length;
                const countEl = document.getElementById('map-property-count');
                if (countEl) {
                    countEl.innerText = 'Available Properties : ' + total;
                }
                if (typeof renderMapListCards === 'function') {
                    renderMapListCards(filteredFeatures);
                }
            })
            .catch(err => {
                if (err.name !== "AbortError") {
                    console.error("API Error:", err);
                    lastMapFetchKey = '';
                }
            })
            .finally(() => {
                isLoading = false;
            });
    
        }, 200);
    }

  

    // ==============================
    // CLUSTER CLICK BEHAVIOR
    // ==============================

    map.on('click', 'clusters', function (e) {

        const features = map.queryRenderedFeatures(e.point, {
            layers: ['clusters']
        });

        if (!features.length) return;

        const clusterId = features[0].properties.cluster_id;
        const pointCount = features[0].properties.point_count;
        const clickLngLat = e.lngLat.toArray();

        const source = map.getSource('properties');

        if (pointCount >= 20) {
            source.getClusterExpansionZoom(clusterId, function (err, zoom) {
                map.easeTo({
                    center: clickLngLat,
                    zoom: zoom
                });
            });
            return;
        }

        source.getClusterLeaves(clusterId, 20, 0, function (err, leaves) {

            if (leaves.length === 1) {
                openPropertyFromFeature(leaves[0]);
            } else {
                showClusterPopup(leaves, clickLngLat);
            }
        });
    });
    
    map.on('touchend', 'clusters', function (e) {
    map.fire('click', e); // reuse same logic
});

  map.on('touchend', 'unclustered-point', function (e) {
    if (!e.features || !e.features.length) return;
    if (e.originalEvent) {
        e.originalEvent.preventDefault();
        e.originalEvent.stopPropagation();
    }
    openPropertyFromFeature(e.features[0]);
});
   
   
   
    const urlParams = new URLSearchParams(window.location.search);

    if (!selectedTransaction) {
        selectedTransaction = pathFilters.transaction || 'For Sale';
    }


   
    
   // console.log("FULL URL:", window.location.href); 
    
    
  


    
 function updateSeoUrl() {

    // =========================
    // TRANSACTION
    // =========================
    let transactionSlug = '';

    if (selectedTransaction === 'For Sale') {
        transactionSlug = 'sale';
    } else if (selectedTransaction === 'For Lease') {
        transactionSlug = 'lease';
    } else {
        transactionSlug = slugify(selectedTransaction || 'sale');
    }

    // =========================
    // PROPERTY TYPE
    // =========================
    let subtypeSlug = '';

    if (selectedSubTypes.length > 0) {

        const subtypeMap = {
            'Detached': 'detached-houses',
            'Semi-Detached': 'semi-detached',
            'Att/Row/Townhouse': 'freehold-townhouses',
            'Condo Townhouse': 'condo-townhouses',
            'Condo Apartment': 'condos',
            'Duplex': 'duplex',
        };

        subtypeSlug =
            subtypeMap[selectedSubTypes[0]] ||
            slugify(selectedSubTypes[0]);

    } else {

        subtypeSlug = 'houses';
    }

    // =========================
    // CITY — keep Ontario when viewing province-wide
    // =========================
    let citySlug = seoCitySlug || 'ontario';
    if (selectedCity && selectedCity.toLowerCase() !== 'ontario') {
        citySlug = slugify(selectedCity);
        seoCitySlug = citySlug;
    }

    // =========================
    // MAIN SEO URL
    // =========================
    const region = 'on'; // Ontario

    let path = `/${region}/map`;
    
    if (subtypeSlug && transactionSlug && citySlug) {
    
        path =
            `/${region}/${subtypeSlug}-for-${transactionSlug}-in-${citySlug}/map`;
    }

    // =========================
    // QUERY PARAMS
    // =========================
    const params = new URLSearchParams();

    // PRICE
    if (selectedMinPrice > 0) {
        params.set('min_price', selectedMinPrice);
    }

    if (
        selectedMaxPrice &&
        selectedMaxPrice !== 5000000 &&
        selectedMaxPrice !== 50000
    ) {
        params.set('max_price', selectedMaxPrice);
    }

    // BEDS / BATHS
    if (selectedBedrooms) {
        params.set('bedrooms', selectedBedrooms);
    }

    if (selectedBathrooms) {
        params.set('bathrooms', selectedBathrooms);
    }

    // BASEMENT
    if (selectedBasement) {
        params.set('basement', selectedBasement);
    }

    if (selectedBasement1) {
        params.set('basement1', selectedBasement1);
    }

    // PROPERTY TYPE (include single subtype so refresh keeps the filter)
    const seoSubtypes = getEffectiveSubtypes();
    if (seoSubtypes.length > 0) {
        params.set('subtypes', seoSubtypes.join(','));
    }

    // STATUS
    if (selectedStatus && selectedStatus.length) {
        params.set('status', selectedStatus.join(','));
    }

    // SQUARE FEET
    if (selectedMinSquare > 0) {
        params.set('square_min', selectedMinSquare);
    }

    if (
        selectedMaxSquare &&
        selectedMaxSquare !== 4000
    ) {
        params.set('square_max', selectedMaxSquare);
    }

    // DATE FILTERS
    const selectedDate = getSelectedDate?.();
    const selectedDateSold = getSelectedDateSold?.();

    if (selectedDate && selectedDate !== 'all') {
        params.set('date', selectedDate);
    }

    if (selectedDateSold && selectedDateSold !== 'all') {
        params.set('date_sold', selectedDateSold);
    }

    const currentView = new URLSearchParams(window.location.search).get('view');
    if (currentView === 'list') {
        params.set('view', 'list');
    }

    // =========================
    // FINAL URL
    // =========================
    const finalUrl = params.toString()
        ? `${path}?${params.toString()}`
        : path;

    // update browser URL without reload
    if (mapHistoryNavigating) {
        return;
    }

    if (finalUrl !== lastPushedMapUrl) {
        const mapState = {
            mapSearch: true,
            selectedCity: selectedCity || '',
            selectedTransaction: selectedTransaction || '',
            selectedMinPrice: selectedMinPrice || 0,
            selectedMaxPrice: selectedMaxPrice || 0,
            selectedStatus: Array.isArray(selectedStatus) ? selectedStatus.slice() : [],
            selectedSubTypes: Array.isArray(selectedSubTypes) ? selectedSubTypes.slice() : [],
            selectedBedrooms: selectedBedrooms || '',
            selectedBathrooms: selectedBathrooms || '',
            selectedBasement: selectedBasement || '',
            selectedBasement1: selectedBasement1 || '',
            selectedMinSquare: selectedMinSquare || 0,
            selectedMaxSquare: selectedMaxSquare || 0,
            hsMobileDateSale: hsMobileDateSale || 'all',
            hsMobileDateSold: hsMobileDateSold || 'all',
        };

        if (!window.history.state || !window.history.state.mapSearch) {
            window.history.replaceState(mapState, '', finalUrl);
        } else {
            window.history.pushState(mapState, '', finalUrl);
        }

        lastPushedMapUrl = finalUrl;
    } else {
        window.history.replaceState(window.history.state || { mapSearch: true }, '', finalUrl);
    }
}
  
  
    
        // ==============================
    // SINGLE PROPERTY POPUP
    // ==============================

    function escapeMapHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Human-friendly relative listed label (mirrors TrebPropertyHelper::relativeListedLabel).
    // e.g. "Listed today", "Listed this week", "Listed this month", "Listed this year", "Listed in 2023".
    function relativeListedLabel(dateStr, prefix) {
        prefix = (prefix === undefined || prefix === null) ? 'Listed' : prefix;
        if (!dateStr) return '';

        const listed = new Date(String(dateStr).replace(' ', 'T'));
        if (isNaN(listed.getTime())) return '';

        const year = listed.getFullYear();
        if (year < 2000 || year > (new Date().getFullYear() + 1)) return '';

        const now = new Date();
        const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());

        if (startOfDay(listed).getTime() === startOfDay(now).getTime()) {
            return (prefix + ' today').trim();
        }

        const weekAgo = startOfDay(now);
        weekAgo.setDate(weekAgo.getDate() - 7);
        if (listed.getTime() >= weekAgo.getTime()) return (prefix + ' this week').trim();

        if (listed.getFullYear() === now.getFullYear() && listed.getMonth() === now.getMonth()) {
            return (prefix + ' this month').trim();
        }

        // Older than this month: show month + year, e.g. "Listed June 2026".
        const monthYear = listed.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        return (prefix + ' ' + monthYear).trim();
    }

    function serikCanonicalOrigin() {
        return window.SERIK_CANONICAL_ORIGIN || window.location.origin;
    }

    function buildMapImageAlt(props) {
        const parts = [
            props?.name || props?.display_address,
            props?.external_id,
            props?.property_type || props?.property_subtype || props?.type,
        ].filter(Boolean);

        return parts.join(' - ') || 'Property listing photo';
    }

    function buildMapPopupGalleryHtml(images, statusLabel, props) {
        const imageAlt = buildMapImageAlt(props || {});
        const safeImages = (images && images.length) ? images : ['https://serik.ca/storage/avatars/1.jpg'];
        const nav = safeImages.length > 1 ? `
            <button type="button" class="hs-map-gallery-nav prev" aria-label="Previous">‹</button>
            <button type="button" class="hs-map-gallery-nav next" aria-label="Next">›</button>
            <span class="hs-map-gallery-counter">1 / ${safeImages.length}</span>
            <button type="button" class="hs-map-see-all-photos">See all ${safeImages.length} photos</button>
        ` : '';
        const thumbs = safeImages.length > 1 ? `
            <div class="hs-map-gallery-thumbs">
                ${safeImages.map((src, i) => `<img src="${escapeMapHtml(src)}" data-index="${i}" class="${i === 0 ? 'active' : ''}" loading="lazy" alt="${escapeMapHtml(imageAlt)}">`).join('')}
            </div>
        ` : '';

        return `
            <div class="hs-map-popup-gallery" data-images='${JSON.stringify(safeImages)}' data-index="0">
                <div class="hs-map-gallery-main">
                    <img src="${escapeMapHtml(safeImages[0])}" class="property-popup-img hs-map-gallery-active js-map-gallery-lightbox-open" loading="lazy" alt="${escapeMapHtml(imageAlt)}">
                    ${nav}
                    <div class="property-popup-sale">${escapeMapHtml(statusLabel)}</div>
                </div>
                ${thumbs}
            </div>
        `;
    }

    function openMapPopupLightbox(gallery, startIndex) {
        if (!gallery || !window.SerikPhotoLightbox) return;
        let images = [];
        try {
            images = JSON.parse(gallery.dataset.images || '[]');
        } catch (e) {
            images = [];
        }
        if (!images.length) return;
        const idx = startIndex ?? Number(gallery.dataset.index || 0);
        window.SerikPhotoLightbox.open(images, idx);
    }

    function updateMapPopupGallery(gallery, nextIndex) {
        if (!gallery) return;
        let images = [];
        try {
            images = JSON.parse(gallery.dataset.images || '[]');
        } catch (e) {
            images = [];
        }
        if (!images.length) return;

        const total = images.length;
        const idx = ((nextIndex % total) + total) % total;
        gallery.dataset.index = String(idx);

        const mainImg = gallery.querySelector('.hs-map-gallery-active');
        if (mainImg) mainImg.src = images[idx];

        const counter = gallery.querySelector('.hs-map-gallery-counter');
        if (counter) counter.textContent = `${idx + 1} / ${total}`;

        gallery.querySelectorAll('.hs-map-gallery-thumbs img').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === idx);
        });
    }

    function bindMapPopupGallery(popupEl) {
        if (!popupEl || popupEl.dataset.galleryBound === '1') return;
        popupEl.dataset.galleryBound = '1';

        popupEl.addEventListener('click', (e) => {
            const gallery = e.target.closest('.hs-map-popup-gallery');
            if (!gallery) return;

            if (e.target.closest('.hs-map-gallery-nav.prev')) {
                e.preventDefault();
                e.stopPropagation();
                updateMapPopupGallery(gallery, Number(gallery.dataset.index || 0) - 1);
                return;
            }
            if (e.target.closest('.hs-map-gallery-nav.next')) {
                e.preventDefault();
                e.stopPropagation();
                updateMapPopupGallery(gallery, Number(gallery.dataset.index || 0) + 1);
                return;
            }
            const thumb = e.target.closest('.hs-map-gallery-thumbs img');
            if (thumb) {
                e.preventDefault();
                e.stopPropagation();
                updateMapPopupGallery(gallery, Number(thumb.dataset.index || 0));
                return;
            }
            if (e.target.closest('.hs-map-see-all-photos') || e.target.closest('.js-map-gallery-lightbox-open')) {
                e.preventDefault();
                e.stopPropagation();
                openMapPopupLightbox(gallery, Number(gallery.dataset.index || 0));
            }
        });
    }

    function mergeMapPopupImages(detailImages, fallbackImage) {
        const out = [];
        (Array.isArray(detailImages) ? detailImages : []).forEach((url) => {
            const u = String(url || '').trim();
            if (u && !out.includes(u)) out.push(u);
        });
        if (fallbackImage && !out.includes(fallbackImage)) {
            out.unshift(fallbackImage);
        }
        return out.length ? out : (fallbackImage ? [fallbackImage] : []);
    }

    function buildFallbackDetailRes(props, status) {
        return {
            data: {
                display_address: props.name || 'Property',
                display_location: '',
                PropertySubType: props.property_subtype || '',
                BedroomsTotal: props.bedrooms,
                BedroomsBelowGrade: props.bedrooms_below,
                BathroomsTotalInteger: props.bathrooms,
                CoveredSpaces: props.garage ?? props.basement,
                LivingAreaRange: props.area,
                ListingKey: props.external_id,
                ListOfficeName: props.agency,
                ListingContractDate: props.date,
            },
            key_facts: {},
            property_details: {},
            description: '',
            listing_history: [],
            price_changes: [],
            rooms: [],
            images: props.image ? [props.image] : [],
            property_id: props.id || '',
            is_locked: !isMapUserLoggedIn && isMapSoldListing(status, props),
        };
    }

    function buildMapPriceHtml(props, status, soldLocked) {
        if (soldLocked) {
            return '<span>Sold listing</span>';
        }
        if (isMapSoldListing(status, props) && props.ClosePrice) {
            return `<span style="text-decoration:line-through;color:gray;">$${Number(props.price || 0).toLocaleString()}</span> <span style="margin-left:8px;color:#ff7b0a;">$${Number(props.ClosePrice).toLocaleString()}</span>`;
        }
        const strike = ['Expired', 'Terminated', 'Suspended'].includes(status);
        return `<span style="${strike ? 'text-decoration:line-through;color:gray;' : ''}">$${Number(props.price || 0).toLocaleString()}</span>`;
    }

    function buildMapHistoryTableHtml(rows) {
        if (!rows || !rows.length) {
            return '<p class="text-muted" style="margin:0;">No history found</p>';
        }
        const body = rows.map((row) => `
            <tr>
                <td>${escapeMapHtml(row.date_start || '-')}</td>
                <td>${escapeMapHtml(row.date_end || '')}</td>
                <td>${row.price != null ? '$' + Number(row.price).toLocaleString() : '-'}</td>
                <td>${escapeMapHtml(row.event || '-')}</td>
                <td>${escapeMapHtml(row.listing_id || '-')}</td>
            </tr>
        `).join('');
        return `<div style="overflow-x:auto;"><table class="hs-map-table"><thead><tr><th>Date Start</th><th>Date End</th><th>Price</th><th>Event</th><th>Listing ID</th></tr></thead><tbody>${body}</tbody></table></div>`;
    }

    function buildMapPriceChangesTableHtml(rows) {
        if (!rows || !rows.length) {
            return '<p class="text-muted" style="margin:0;">No price changes recorded</p>';
        }
        const body = rows.map((row) => `
            <tr>
                <td>${escapeMapHtml(row.date || '-')}</td>
                <td>${row.old_price != null ? '$' + Number(row.old_price).toLocaleString() : '-'}</td>
                <td>${row.new_price != null ? '$' + Number(row.new_price).toLocaleString() : '-'}</td>
                <td>${escapeMapHtml(row.event || 'Price Change')}</td>
            </tr>
        `).join('');
        return `<div style="overflow-x:auto;"><table class="hs-map-table"><thead><tr><th>Date</th><th>Old Price</th><th>New Price</th><th>Event</th></tr></thead><tbody>${body}</tbody></table></div>`;
    }

    function buildMapRoomsTableHtml(rows) {
        if (!rows || !rows.length) {
            return '<p class="text-muted" style="margin:0;">Room details are not available for this listing.</p>';
        }
        const body = rows.map((room) => `
            <tr>
                <td>${escapeMapHtml(room.name || 'Room')}</td>
                <td>${escapeMapHtml(room.size || '-')}</td>
                <td>${escapeMapHtml(room.level || '-')}</td>
                <td>${escapeMapHtml(room.features && room.features !== '-' ? room.features : '')}</td>
            </tr>
        `).join('');
        return `<div style="overflow-x:auto;"><table class="hs-map-table"><thead><tr><th>Room</th><th>Size</th><th>Level</th><th>Features</th></tr></thead><tbody>${body}</tbody></table></div>`;
    }

    function buildMapKeyFactsHtml(keyFacts, displayName, displayLocation, displayType, listingKey, brokerage) {
        const facts = [
            ['Tax', keyFacts.tax],
            ['Property Type', keyFacts.property_type || displayType],
            ['Building Age', keyFacts.building_age || keyFacts.year_built],
            ['Size', keyFacts.size],
            ['Lot Size', keyFacts.lot_size],
            ['Price/sqft', keyFacts.price_per_sqft],
            ['Parking', keyFacts.parking],
            ['Basement', keyFacts.basement],
            ['Maintenance', keyFacts.maintenance],
            ['Included Utility', keyFacts.included_utility],
            ['Locker', keyFacts.locker],
            ['Listing #', keyFacts.listing_number || listingKey],
            ['Data Source', keyFacts.data_source || 'TRREB / PropTX'],
            ['Listing Brokerage', keyFacts.brokerage || brokerage],
            ['Days on Market', keyFacts.days_on_site],
            ['Status Change', keyFacts.status_change],
            ['Listed on', keyFacts.listed_on],
            ['Updated on', keyFacts.updated_on],
        ];
        const cells = facts.map(([label, value]) => `
            <div><span class="fact-label">${escapeMapHtml(label)}</span><span class="fact-value">${escapeMapHtml(value || '-')}</span></div>
        `).join('');
        return `<p class="hs-map-section-subtitle">Key facts for ${escapeMapHtml(displayName)}${displayLocation ? ', ' + escapeMapHtml(displayLocation) : ''}</p><div class="hs-map-facts-grid">${cells}</div>`;
    }

    function buildMapDetailsGridHtml(details) {
        if (!details || !Object.keys(details).length) {
            return '<p class="text-muted" style="margin:0;">Details loading…</p>';
        }
        const groups = [
            { title: 'Property', keys: ['property_type', 'style', 'fronting_on', 'community', 'municipality'] },
            { title: 'Inside', keys: ['bedrooms', 'bathrooms', 'bathrooms_detail', 'basement', 'kitchens', 'rooms', 'family_room', 'fireplace'] },
            { title: 'Utilities', keys: ['water', 'cooling', 'heating_type', 'heating_fuel'] },
            { title: 'Building', keys: ['building_age', 'construction'] },
            { title: 'Parking', keys: ['garage_type', 'garage', 'parking_places'] },
            { title: 'Land', keys: ['sewer', 'frontage', 'depth', 'lot_size', 'zoning', 'cross_street'] },
        ];
        const labels = {
            property_type: 'Property Type', style: 'Style', fronting_on: 'Fronting on', community: 'Community', municipality: 'Municipality',
            bedrooms: 'Bedrooms', bathrooms: 'Bathrooms', bathrooms_detail: 'Bathrooms Detail', basement: 'Basement', kitchens: 'Kitchens',
            rooms: 'Rooms', family_room: 'Family Room', fireplace: 'Fireplace', water: 'Water', cooling: 'Cooling',
            heating_type: 'Heating Type', heating_fuel: 'Heating Fuel', building_age: 'Building Age', construction: 'Construction',
            garage_type: 'Garage Type', garage: 'Garage', parking_places: 'Parking Places', sewer: 'Sewer', frontage: 'Frontage',
            depth: 'Depth', lot_size: 'Lot Size', zoning: 'Zoning', cross_street: 'Cross Street',
        };
        let html = '<div class="hs-map-details-grid">';
        groups.forEach((group) => {
            html += `<div class="hs-map-group-title">${escapeMapHtml(group.title)}</div>`;
            group.keys.forEach((key) => {
                html += `<div><span class="fact-label">${escapeMapHtml(labels[key] || key)}</span><span class="fact-value">${escapeMapHtml(details[key] || '-')}</span></div>`;
            });
        });
        html += '</div>';
        return html;
    }

    function buildMapContactFormHtml(propertyId, propertyName) {
        if (!propertyId) {
            return `<div class="hs-map-inquiry-card"><div class="hs-map-form-title">Contact Us</div><p class="hs-map-form-subtitle">Inquiry form will be available once listing is synced.</p></div>`;
        }
        return `
            <div class="hs-map-inquiry-card">
                <form class="hs-map-consult-form" data-property-id="${escapeMapHtml(propertyId)}">
                    <div class="hs-map-form-title">Contact Us</div>
                    <p class="hs-map-form-subtitle">Interested in this property? Send us a message and we'll get back to you shortly.</p>
                    <div class="hs-map-form-row">
                        <input type="text" name="name" placeholder="Your name *" required class="hs-map-form-input" autocomplete="name">
                        <input type="tel" name="phone" placeholder="Phone number" class="hs-map-form-input" autocomplete="tel">
                    </div>
                    <input type="email" name="email" placeholder="Email address" class="hs-map-form-input" autocomplete="email">
                    <textarea name="content" class="hs-map-form-input" rows="4" required placeholder="I'm interested in ${escapeMapHtml(propertyName)}…"></textarea>
                    <button type="submit" class="hs-map-form-submit">Send Inquiry</button>
                    <div class="hs-map-form-msg" hidden></div>
                </form>
            </div>
        `;
    }

    function buildPropertyPopupHtml(props, status, detailRes) {
        const soldLocked = mapBlurClass(status, props);
        const detail = detailRes?.data || null;
        const isLoading = !detailRes && props.external_id && !props.requires_login;
        const res = detailRes || buildFallbackDetailRes(props, status);
        const keyFacts = res.key_facts || {};
        const propertyDetails = res.property_details || {};
        const listingHistory = res.listing_history || [];
        const priceChanges = res.price_changes || [];
        const rooms = res.rooms || [];
        const isLocked = res.is_locked || false;
        const propertyId = res.property_id || props.id || '';
        const description = res.description || '';
        const images = mergeMapPopupImages(res.images, props.image);
        const statusLabel = props.transaction || status;

        const displayName = detail?.display_address || props.name || 'Property';
        const displayLocation = detail?.display_location || '';
        const displayType = detail?.PropertySubType || props.property_subtype || '';
        const shareUrl = props.url ? (serikCanonicalOrigin() + '/properties/' + props.url) : window.location.href;
        const actionsHtml = `
            <div class="hs-map-actions">
                <button type="button" class="hs-map-action-btn" data-map-share data-share-url="${escapeMapHtml(shareUrl)}" data-share-title="${escapeMapHtml(displayName)}" title="Share" aria-label="Share">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                </button>
                ${props.url ? `<button type="button" class="hs-map-action-btn map-popup-details-btn" title="Full screen" aria-label="Full screen"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>` : ''}
                <button type="button" class="hs-map-action-btn hs-map-wishlist-btn" data-bb-toggle="add-to-wishlist" data-type="property" data-id="${escapeMapHtml(propertyId)}" title="Save to wishlist" aria-label="Save to wishlist"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
            </div>`;
        const beds = detail?.BedroomsTotal ?? props.bedrooms;
        const bedsBelow = detail?.BedroomsBelowGrade ?? props.bedrooms_below;
        const baths = detail?.BathroomsTotalInteger ?? props.bathrooms;
        const garage = detail?.CoveredSpaces ?? props.garage ?? detail?.ParkingSpaces ?? props.basement;
        const areaRaw = detail?.LivingAreaRange ?? props.area;
        const areaHtml = areaRaw
            ? String(areaRaw).split('-').map((n) => Number(n).toLocaleString()).join('-') + ' sq. ft.'
            : '—';
        const brokerage = detail?.ListOfficeName || props.agency || '';
        const listingId = detail?.ListingKey || props.external_id || '';
        const addedLabel = detail?.ListingContractDate || detail?.OriginalEntryTimestamp || props.date || '';
        const detailUrl = props.url ? '/properties/' + props.url : '';

        const lockedHtml = `<div class="hs-map-locked-box"><strong style="color:#e63946;">🔒 Complete Account</strong><p style="margin:8px 0 0;">Real estate boards need a verified account to see listing history &amp; sold data.</p><button type="button" class="btn btn-primary btn-sm mt-2 js-map-auth-open" style="background:#0255a1;border:none;">Log in to view</button></div>`;

        const descriptionHtml = description
            ? `<div class="hs-map-description">${escapeMapHtml(description)}</div>`
            : (isLoading ? '' : '');

        const centerBody = isLoading
            ? '<div class="hs-map-popup-loading">Loading property details…</div>'
            : `
                <div class="map-popup-price-row">
                    <div class="map-popup-price">${buildMapPriceHtml(props, status, soldLocked)}</div>
                    <div class="map-popup-date">${escapeMapHtml(relativeListedLabel(props.date, 'Listed'))}</div>
                </div>
                <div class="map-popup-detail-header">${escapeMapHtml(displayName)}</div>
                ${displayLocation ? `<div class="map-popup-detail-location">${escapeMapHtml(displayLocation)}</div>` : ''}
                ${displayType ? `<div class="map-popup-detail-type">${escapeMapHtml(displayType)}</div>` : ''}
                <div class="hs-map-stats-row">
                    ${beds != null ? `<span class="stat"><span aria-hidden="true">🛏</span> <strong>${beds}${bedsBelow ? '+' + bedsBelow : ''}</strong></span>` : ''}
                    ${baths != null ? `<span class="stat"><span aria-hidden="true">🛁</span> <strong>${baths}</strong></span>` : ''}
                    ${garage != null ? `<span class="stat"><span aria-hidden="true">🚘</span> <strong>${garage}</strong></span>` : ''}
                    ${areaHtml !== '—' ? `<span class="stat"><strong>${areaHtml}</strong></span>` : ''}
                    ${relativeListedLabel(addedLabel, '') ? `<span class="stat"><strong>Listed</strong> ${escapeMapHtml(relativeListedLabel(addedLabel, ''))}</span>` : ''}
                </div>
                ${descriptionHtml}
                <div class="hs-map-section-title">Listing History</div>
                <p class="hs-map-section-subtitle">Buy/sell history for ${escapeMapHtml(displayName)}${displayType ? ' (' + escapeMapHtml(displayType) + ')' : ''}</p>
                <div class="hs-map-tabs-scroll">
                    <div class="hs-map-tabs" role="tablist">
                        <button type="button" class="hs-map-tab-btn active" data-map-tab="history">Listing History</button>
                        <button type="button" class="hs-map-tab-btn" data-map-tab="price-change">Price Changes (${priceChanges.length})</button>
                        <button type="button" class="hs-map-tab-btn" data-map-tab="facts">Key Facts</button>
                        <button type="button" class="hs-map-tab-btn" data-map-tab="details">Details</button>
                        <button type="button" class="hs-map-tab-btn" data-map-tab="rooms">Rooms (${rooms.length})</button>
                    </div>
                </div>
                <div class="hs-map-tab-panel active" data-map-panel="history">${isLocked ? lockedHtml : buildMapHistoryTableHtml(listingHistory)}</div>
                <div class="hs-map-tab-panel" data-map-panel="price-change">${isLocked ? lockedHtml : buildMapPriceChangesTableHtml(priceChanges)}</div>
                <div class="hs-map-tab-panel" data-map-panel="facts">${buildMapKeyFactsHtml(keyFacts, displayName, displayLocation, displayType, listingId, brokerage)}</div>
                <div class="hs-map-tab-panel" data-map-panel="details">${buildMapDetailsGridHtml(propertyDetails)}</div>
                <div class="hs-map-tab-panel" data-map-panel="rooms">${buildMapRoomsTableHtml(rooms)}</div>
                <div class="property-popup-footer" style="margin-top:8px;font-size:12px;color:#6c757d;">${escapeMapHtml(listingId)}${brokerage ? ' , ' + escapeMapHtml(brokerage) : ''}</div>
            `;

        return `
            ${mapLoginGateHtml(status, props)}
            <div class="${soldLocked} open-property property-popup hs-map-popup-full"
                data-url="${escapeMapHtml(detailUrl)}"
                data-property-id="${escapeMapHtml(propertyId)}">
                <div class="hs-map-gallery-col">
                    <div class="popup-img-div hs-map-popup-gallery-wrap">
                        ${buildMapPopupGalleryHtml(images, statusLabel, props)}
                    </div>
                </div>
                <div class="hs-map-details-col popupspace">${actionsHtml}${centerBody}</div>
                <div class="hs-map-inquiry-col">
                    ${buildMapContactFormHtml(propertyId, displayName)}
                    ${detailUrl ? '<span class="map-popup-details-btn" role="button">View Full Page</span>' : ''}
                </div>
            </div>
        `;
    }

    function bindMapPopupTabs(popupEl) {
        if (!popupEl) return;
        delete popupEl.dataset.tabsBound;
    }

    function activateMapPopupTab(tabBtn) {
        if (!tabBtn) return;
        const root = tabBtn.closest('.hs-map-popup-full');
        const tab = tabBtn.dataset.mapTab;
        if (!root || !tab) return;
        root.querySelectorAll('.hs-map-tab-btn').forEach((b) => b.classList.toggle('active', b === tabBtn));
        root.querySelectorAll('.hs-map-tab-panel').forEach((p) => p.classList.toggle('active', p.dataset.mapPanel === tab));
    }

    function patchMapPopupFromBundle(popup, props, status, merged, activeTab) {
        const el = popup.getElement();
        const root = el?.querySelector('.hs-map-popup-full');
        if (!root) return;

        const detail = merged.data || {};
        const isLocked = merged.is_locked || false;
        const listingHistory = merged.listing_history || [];
        const priceChanges = merged.price_changes || [];
        const rooms = merged.rooms || [];
        const keyFacts = merged.key_facts || {};
        const propertyDetails = merged.property_details || {};
        const displayName = detail.display_address || props.name || 'Property';
        const displayLocation = detail.display_location || '';
        const displayType = detail.PropertySubType || props.property_subtype || '';
        const listingId = detail.ListingKey || props.external_id || '';
        const brokerage = detail.ListOfficeName || props.agency || '';
        const statusLabel = props.transaction || status;

        const lockedHtml = `<div class="hs-map-locked-box"><strong style="color:#e63946;">🔒 Complete Account</strong><p style="margin:8px 0 0;">Real estate boards need a verified account to see listing history &amp; sold data.</p><button type="button" class="btn btn-primary btn-sm mt-2 js-map-auth-open" style="background:#0255a1;border:none;">Log in to view</button></div>`;

        const headerEl = root.querySelector('.map-popup-detail-header');
        if (headerEl && displayName) headerEl.textContent = displayName;

        const locationEl = root.querySelector('.map-popup-detail-location');
        if (displayLocation) {
            if (locationEl) {
                locationEl.textContent = displayLocation;
            } else if (headerEl) {
                headerEl.insertAdjacentHTML('afterend', `<div class="map-popup-detail-location">${escapeMapHtml(displayLocation)}</div>`);
            }
        }

        if (merged.description) {
            let descEl = root.querySelector('.hs-map-description');
            if (!descEl) {
                const statsRow = root.querySelector('.hs-map-stats-row');
                if (statsRow) {
                    statsRow.insertAdjacentHTML('afterend', `<div class="hs-map-description">${escapeMapHtml(merged.description)}</div>`);
                }
            } else {
                descEl.textContent = merged.description;
            }
        }

        const priceTab = root.querySelector('.hs-map-tab-btn[data-map-tab="price-change"]');
        if (priceTab) priceTab.textContent = `Price Changes (${priceChanges.length})`;

        const roomsTab = root.querySelector('.hs-map-tab-btn[data-map-tab="rooms"]');
        if (roomsTab) roomsTab.textContent = `Rooms (${rooms.length})`;

        const historyPanel = root.querySelector('[data-map-panel="history"]');
        if (historyPanel) historyPanel.innerHTML = isLocked ? lockedHtml : buildMapHistoryTableHtml(listingHistory);

        const pricePanel = root.querySelector('[data-map-panel="price-change"]');
        if (pricePanel) pricePanel.innerHTML = isLocked ? lockedHtml : buildMapPriceChangesTableHtml(priceChanges);

        const factsPanel = root.querySelector('[data-map-panel="facts"]');
        if (factsPanel) factsPanel.innerHTML = buildMapKeyFactsHtml(keyFacts, displayName, displayLocation, displayType, listingId, brokerage);

        const detailsPanel = root.querySelector('[data-map-panel="details"]');
        if (detailsPanel) detailsPanel.innerHTML = buildMapDetailsGridHtml(propertyDetails);

        const roomsPanel = root.querySelector('[data-map-panel="rooms"]');
        if (roomsPanel) roomsPanel.innerHTML = buildMapRoomsTableHtml(rooms);

        const images = mergeMapPopupImages(merged.images, props.image);
        if (images.length > 1) {
            const wrap = root.querySelector('.hs-map-popup-gallery-wrap');
            if (wrap) {
                wrap.innerHTML = buildMapPopupGalleryHtml(images, statusLabel, props);
                if (el) delete el.dataset.galleryBound;
                bindMapPopupGallery(el);
            }
        }

        const restoredTab = root.querySelector(`.hs-map-tab-btn[data-map-tab="${activeTab}"]`)
            || root.querySelector('.hs-map-tab-btn');
        if (restoredTab) {
            activateMapPopupTab(restoredTab);
        }

        const propertyId = merged.property_id || props.id || '';
        if (propertyId) {
            root.dataset.propertyId = String(propertyId);
            const form = root.querySelector('.hs-map-consult-form');
            if (form) form.dataset.propertyId = String(propertyId);
        }
    }

    window.hsMapDetailCache = window.hsMapDetailCache || new Map();

    function hsMergeMapBundle(props, status, bundle) {
        const merged = Object.assign(buildFallbackDetailRes(props, status), bundle || {});
        merged.listing_history = bundle?.listing_history || [];
        merged.price_changes = bundle?.price_changes || [];
        merged.rooms = bundle?.rooms || [];
        return merged;
    }

    window.hsMergeMapBundle = hsMergeMapBundle;

    function showPropertyIframeLoader() {
        const loader = document.getElementById('iframeLoader');
        if (!loader) return;
        loader.classList.remove('is-hidden');
        loader.style.display = 'flex';
    }

    function hidePropertyIframeLoader() {
        const loader = document.getElementById('iframeLoader');
        if (!loader) return;
        loader.style.display = 'none';
        loader.classList.add('is-hidden');
    }

    function enablePropertyIframeScroll(iframe) {
        if (!iframe) return;
        try {
            const doc = iframe.contentDocument || iframe.contentWindow?.document;
            if (!doc) return;
            doc.documentElement.style.overflowY = 'auto';
            doc.documentElement.style.webkitOverflowScrolling = 'touch';
            doc.body.style.overflowY = 'auto';
            doc.body.style.webkitOverflowScrolling = 'touch';
            doc.body.style.touchAction = 'pan-y';
        } catch (e) {
            // Cross-origin iframe — rely on scrolling="yes"
        }
    }

    function bindPropertyIframeLoad(iframe) {
        if (!iframe) return;
        iframe.onload = function () {
            hidePropertyIframeLoader();
            enablePropertyIframeScroll(iframe);
        };
    }

    function ensurePropertyModalOnBody() {
        const modal = document.getElementById('propertyModal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    function lockBodyForPropertyModal() {
        ensurePropertyModalOnBody();
        document.documentElement.classList.add('hs-property-modal-open');
        document.body.dataset.hsModalScrollY = String(window.scrollY || 0);
        document.body.style.position = 'fixed';
        document.body.style.top = `-${document.body.dataset.hsModalScrollY}px`;
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';
    }

    function unlockBodyForPropertyModal() {
        document.documentElement.classList.remove('hs-property-modal-open');
        const scrollY = parseInt(document.body.dataset.hsModalScrollY || '0', 10);
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        document.body.style.overflow = '';
        delete document.body.dataset.hsModalScrollY;
        window.scrollTo(0, scrollY);
    }

    function pinMobileMapPopup(popup) {
        if (window.innerWidth > 991 || !popup?._container) return;

        const container = popup._container;
        document.body.appendChild(container);
        container.classList.add('hs-map-mobile-sheet');

        const bottomOffset = 52 + (parseInt(getComputedStyle(document.documentElement).getPropertyValue('env(safe-area-inset-bottom)')) || 0);
        container.style.cssText = [
            'position:fixed',
            'left:0',
            'right:0',
            'top:auto',
            'bottom:' + bottomOffset + 'px',
            'width:100%',
            'max-width:100vw',
            'transform:none',
            'z-index:10050',
            'margin:0',
            'padding:0',
            'touch-action:pan-y',
        ].join(';');

        const content = container.querySelector('.maplibregl-popup-content');
        if (content) {
            const maxH = 'calc(100dvh - 60px - 52px - env(safe-area-inset-bottom, 0px))';
            content.style.maxHeight = maxH;
            content.style.height = maxH;
            content.style.overflowY = 'hidden';
            content.style.overflowX = 'hidden';
            content.style.display = 'flex';
            content.style.flexDirection = 'column';
            content.style.webkitOverflowScrolling = 'touch';
            content.style.overscrollBehavior = 'contain';
            content.style.touchAction = 'pan-y';
        }

        const cluster = container.querySelector('.clusterpopup');
        if (cluster) {
            cluster.style.display = 'flex';
            cluster.style.flexDirection = 'column';
            cluster.style.flex = '1 1 auto';
            cluster.style.minHeight = '0';
            cluster.style.maxHeight = '100%';
            cluster.style.overflow = 'hidden';
        }

        const list = container.querySelector('.hs-cluster-popup-list');
        if (list) {
            list.style.flex = '1 1 auto';
            list.style.minHeight = '0';
            list.style.maxHeight = 'none';
            list.style.overflowY = 'auto';
            list.style.webkitOverflowScrolling = 'touch';
            list.style.overscrollBehavior = 'contain';
            list.style.touchAction = 'pan-y';
        }

        if (window.hsMap?.dragPan?.disable) {
            window.hsMap.dragPan.disable();
            popup.once('close', () => window.hsMap?.dragPan?.enable?.());
        }

        popup._update = function () {};
    }

    function prefetchMapBundlesForFeatures(features, limit = 3) {
        window._hsBundlePrefetch = window._hsBundlePrefetch || new Set();
        (features || []).slice(0, limit).forEach((feature) => {
            const extId = feature?.properties?.external_id;
            if (!extId) return;

            const cacheKey = String(extId).toUpperCase();
            if (window.hsMapDetailCache?.has(cacheKey) || window._hsBundlePrefetch.has(cacheKey)) return;

            window._hsBundlePrefetch.add(cacheKey);
            fetch(`/api/v1/map-property-bundle/${encodeURIComponent(extId)}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((r) => (r.ok ? r.json() : null))
                .then((bundle) => {
                    if (!bundle || bundle.success === false) return;
                    if (!window.hsMapDetailCache.has(cacheKey)) {
                        window.hsMapDetailCache.set(cacheKey, bundle);
                    }
                })
                .catch(() => {})
                .finally(() => window._hsBundlePrefetch.delete(cacheKey));
        });
    }

    function enrichMapPopup(popup, props, status) {
        if (!props.external_id || props.requires_login) return;

        const cacheKey = String(props.external_id).toUpperCase();
        const listingKey = encodeURIComponent(props.external_id);

        const applyMerged = (merged) => {
            const activeTab = popup.getElement()?.querySelector('.hs-map-tab-btn.active')?.dataset.mapTab || 'history';
            patchMapPopupFromBundle(popup, props, status, merged, activeTab);
        };

        if (window.hsMapDetailCache.has(cacheKey)) {
            const cached = window.hsMapDetailCache.get(cacheKey);
            applyMerged(cached.success === true ? hsMergeMapBundle(props, status, cached) : cached);
            return;
        }

        fetch(`/api/v1/map-property-bundle/${listingKey}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((bundle) => {
                if (!bundle || bundle.success === false) {
                    return;
                }

                const merged = hsMergeMapBundle(props, status, bundle);
                window.hsMapDetailCache.set(cacheKey, merged);
                applyMerged(merged);

                const imageCount = (merged.images || []).length;
                if (imageCount <= 1) {
                    fetch(`/api/v1/getPropertyImages/${listingKey}`)
                        .then((r) => (r.ok ? r.json() : null))
                        .then((imgRes) => {
                            if (!imgRes?.images || imgRes.images.length <= 1) return;
                            merged.images = imgRes.images;
                            window.hsMapDetailCache.set(cacheKey, merged);
                            const activeTab = popup.getElement()?.querySelector('.hs-map-tab-btn.active')?.dataset.mapTab || 'history';
                            patchMapPopupFromBundle(popup, props, status, merged, activeTab);
                        })
                        .catch(() => {});
                }
            })
            .catch(() => {});
    }

    window.loadMapPopupRooms = function (popup, props, status) {
        if (!props.external_id) return;

        const cacheKey = String(props.external_id).toUpperCase();
        const merged = window.hsMapDetailCache.get(cacheKey) || buildFallbackDetailRes(props, status);
        if (merged.rooms && merged.rooms.length) return;

        const listingKey = encodeURIComponent(props.external_id);
        fetch(`/api/v1/property-rooms/${listingKey}`)
            .then((r) => (r.ok ? r.json() : null))
            .then((roomsRes) => {
                if (!roomsRes?.data || !roomsRes.data.length) return;
                merged.rooms = roomsRes.data;
                window.hsMapDetailCache.set(cacheKey, merged);
                const panel = popup.getElement()?.querySelector('[data-map-panel="rooms"]');
                if (panel) {
                    panel.innerHTML = buildMapRoomsTableHtml(merged.rooms);
                }
                const tabBtn = popup.getElement()?.querySelector('.hs-map-tab-btn[data-map-tab="rooms"]');
                if (tabBtn) {
                    tabBtn.textContent = `Rooms (${merged.rooms.length})`;
                }
            })
            .catch(() => {});
    };

    function openPropertyDetailModal(props) {
        if (!props) return;

        const status = mapListingStatus(props);
        if (!isMapUserLoggedIn && isMapSoldListing(status, props)) {
            if (typeof openAuthModal === 'function') {
                openAuthModal('login');
            }
            return;
        }

        const modal = document.getElementById('propertyModal');
        const iframe = document.getElementById('propertyFrame');
        if (!modal || !iframe) return;

        const slug = props.url || '';
        if (!slug || slug === 'undefined') {
            if (typeof openAuthModal === 'function') {
                openAuthModal('login');
            }
            return;
        }

        let url = '/properties/' + String(slug).replace(/^\/+/, '');
        if (!url.startsWith('http')) {
            url = serikCanonicalOrigin() + url;
        }
        url += (url.includes('?') ? '&' : '?') + 'iframe=1&t=' + Date.now();

        document.querySelectorAll('.maplibregl-popup').forEach((popup) => popup.remove());

        ensurePropertyModalOnBody();
        modal.style.display = 'block';
        showPropertyIframeLoader();
        lockBodyForPropertyModal();
        bindPropertyIframeLoad(iframe);
        iframe.src = url;
    }

    window.openPropertyDetailModal = openPropertyDetailModal;

    function openPropertyFromFeature(feature) {
        if (!feature?.properties) return;
        openPropertyDetailModal(feature.properties);
    }

    window.openPropertyFromFeature = openPropertyFromFeature;

    function showPropertyPopup(feature) {
        openPropertyFromFeature(feature);
    }

    window.showPropertyPopup = showPropertyPopup;

    window._hsBundlePrefetch = window._hsBundlePrefetch || new Set();

    function hsPrefetchMapBundleFromItem(item) {
        if (!item) return;

        let props = null;
        if (item.classList.contains('hs-list-item')) {
            const id = item.dataset.id;
            const feature = hsMapListFeatures.find((f) => String(f.properties.id) === String(id));
            props = feature?.properties || null;
        } else if (item.classList.contains('hs-cluster-list-item')) {
            const idx = parseInt(item.dataset.clusterIdx, 10);
            props = window._hsLastClusterLeaves?.[idx]?.properties || null;
        }
        if (!props?.external_id) return;

        const cacheKey = String(props.external_id).toUpperCase();
        if (window.hsMapDetailCache.has(cacheKey) || window._hsBundlePrefetch.has(cacheKey)) return;

        window._hsBundlePrefetch.add(cacheKey);
        fetch(`/api/v1/map-property-bundle/${encodeURIComponent(props.external_id)}`)
            .then((r) => (r.ok ? r.json() : null))
            .then((bundle) => {
                if (!bundle || bundle.success === false) return;
                if (!window.hsMapDetailCache.has(cacheKey)) {
                    window.hsMapDetailCache.set(cacheKey, bundle);
                }
            })
            .catch(() => {})
            .finally(() => window._hsBundlePrefetch.delete(cacheKey));
    }

    document.addEventListener('mouseover', function (e) {
        hsPrefetchMapBundleFromItem(e.target.closest('.hs-list-item, .hs-cluster-list-item'));
    });

    document.addEventListener('touchstart', function (e) {
        hsPrefetchMapBundleFromItem(e.target.closest('.hs-list-item, .hs-cluster-list-item'));
    }, { passive: true });

    function handleMapPropertyClick(e) {
        if (!e.features || !e.features.length) return;
        if (e.originalEvent) {
            e.originalEvent.stopPropagation();
        }
        openPropertyFromFeature(e.features[0]);
    }

    map.on('click', 'unclustered-point', handleMapPropertyClick);

    // ==============================
    // MULTI PROPERTY POPUP
    // ==============================

    function buildClusterListCardHtml(feature, index) {
        const props = feature.properties || {};
        const itemStatus = mapListingStatus(props);
        const locked = mapBlurClass(itemStatus, props);
        const priceHtml = buildMapPriceHtml(props, itemStatus, locked);
        const areaText = props.area
            ? String(props.area).split('-').map((n) => Number(n).toLocaleString()).join('-') + ' ft²'
            : '—';

        return `
            ${mapLoginGateHtml(itemStatus, props)}
            <article class="hs-cluster-list-item ${locked}" data-cluster-idx="${index}" role="button" tabindex="0">
                <div class="hs-cluster-card-img">
                    ${props.image
                        ? `<img src="${escapeMapHtml(props.image)}" alt="${escapeMapHtml(buildMapImageAlt(props))}" loading="lazy" onerror="this.style.display='none';this.parentNode.classList.add('hs-img-empty');">`
                        : '<div class="hs-img-empty-fill"></div>'}
                    <span class="hs-cluster-card-badge">${escapeMapHtml(props.transaction || itemStatus || '')}</span>
                </div>
                <div class="hs-cluster-card-body">
                    <div class="hs-cluster-card-top">
                        <div class="hs-cluster-card-price">${priceHtml}</div>
                        <div class="hs-cluster-card-date">${escapeMapHtml(relativeListedLabel(props.date, 'Listed'))}</div>
                    </div>
                    <div class="hs-cluster-card-title">${escapeMapHtml(props.name || 'Property')}</div>
                    <div class="hs-cluster-card-meta">
                        <span>🛏 ${escapeMapHtml(props.bedrooms ?? '-')}</span>
                        <span>🛁 ${escapeMapHtml(props.bathrooms ?? '-')}</span>
                        <span>📐 ${escapeMapHtml(areaText)}</span>
                    </div>
                    <div class="hs-cluster-card-footer">${escapeMapHtml(props.external_id || '')}${props.agency ? ' · ' + escapeMapHtml(props.agency) : ''}</div>
                </div>
            </article>
        `;
    }

    function ensureMapPopupFullyVisible(popup, coordinates) {
        if (!popup || !map || window.innerWidth <= 991) {
            return;
        }

        const adjust = () => {
            const el = popup.getElement();
            const mapEl = map.getContainer();
            if (!el || !mapEl) {
                return;
            }

            const padding = 20;
            const mapRect = mapEl.getBoundingClientRect();
            const popupRect = el.getBoundingClientRect();
            let panY = 0;
            let panX = 0;

            if (popupRect.bottom > mapRect.bottom - padding) {
                panY = popupRect.bottom - (mapRect.bottom - padding);
            }
            if (popupRect.top < mapRect.top + padding) {
                panY = popupRect.top - (mapRect.top + padding);
            }
            if (popupRect.right > mapRect.right - padding) {
                panX = popupRect.right - (mapRect.right - padding);
            }
            if (popupRect.left < mapRect.left + padding) {
                panX = popupRect.left - (mapRect.left + padding);
            }

            if (panX !== 0 || panY !== 0) {
                map.easeTo({
                    center: coordinates,
                    offset: [panX * 0.5, panY],
                    duration: 420,
                    essential: true,
                });
            }
        };

        requestAnimationFrame(() => requestAnimationFrame(adjust));
    }

    function showClusterPopup(leaves, coordinates) {
        window._hsLastClusterLeaves = leaves || [];

        const cards = (leaves || []).map((feature, index) => buildClusterListCardHtml(feature, index)).join('');
        const html = `
            <div class="clusterpopup">
                <div class="hs-cluster-popup-header">
                    <h6>${leaves.length} Listings</h6>
                    <p>Tap a listing to view full details</p>
                </div>
                <div class="hs-cluster-popup-list">${cards}</div>
            </div>
        `;

        const point = map.project(coordinates);
        const mapWidth = map.getContainer().offsetWidth;
        const mapHeight = map.getContainer().offsetHeight;
        const anchor = point.x > mapWidth / 2 ? 'right' : 'left';
        const verticalAnchor = point.y > mapHeight / 2 ? 'bottom' : 'top';
        const isMobile = window.innerWidth <= 991;

        document.querySelectorAll('.maplibregl-popup').forEach((popup) => popup.remove());

        const clusterPopup = new maplibregl.Popup({
            closeButton: true,
            closeOnClick: false,
            closeOnMove: false,
            anchor: isMobile ? 'bottom' : verticalAnchor,
            offset: isMobile ? [0, 8] : 12,
            maxWidth: isMobile ? '100vw' : '420px',
            className: isMobile ? 'hs-map-cluster-mobile-popup' : 'hs-map-cluster-popup',
        })
            .setLngLat(coordinates)
            .setHTML(html)
            .addTo(map);

        if (isMobile) {
            pinMobileMapPopup(clusterPopup);
        } else {
            ensureMapPopupFullyVisible(clusterPopup, coordinates);
        }

        hydrateMapThumbnailsForFeatures(leaves, '.hs-cluster-list-item');
        prefetchMapBundlesForFeatures(leaves);
    }

    function hydrateMapThumbnailsForFeatures(features, itemSelector) {
        const list = (features || []).slice(0, 60);
        if (!list.length) return;

        const ids = list.map((f) => f?.properties?.id).filter(Boolean);
        if (!ids.length) return;

        fetch(`/api/v1/map-thumbnails?ids=${ids.join(',')}`, { credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : null))
            .then((payload) => {
                const data = payload?.data || {};
                list.forEach((feature, index) => {
                    const props = feature.properties || {};
                    const img = data[String(props.id)] || data[String(props.external_id || '').toUpperCase()] || '';
                    if (!img) return;
                    props.image = img;

                    const card = document.querySelector(`${itemSelector}[data-cluster-idx="${index}"]`)
                        || document.querySelector(`${itemSelector}[data-id="${props.id}"]`);
                    if (!card) return;
                    const wrap = card.querySelector('.hs-cluster-card-img, .hs-list-card-img');
                    if (!wrap) return;
                    if (wrap.querySelector('img')) return;
                    wrap.classList.remove('hs-img-empty');
                    const empty = wrap.querySelector('.hs-img-empty-fill');
                    if (empty) empty.remove();
                    const image = document.createElement('img');
                    image.src = img;
                    image.alt = '';
                    image.loading = 'lazy';
                    image.onerror = function () {
                        this.style.display = 'none';
                        wrap.classList.add('hs-img-empty');
                    };
                    wrap.insertBefore(image, wrap.firstChild);
                });
            })
            .catch(() => {});
    }
    window.hydrateMapThumbnailsForFeatures = hydrateMapThumbnailsForFeatures;
    
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-map-auth-open, .map-sold-login-gate');

    if (btn) {
        e.preventDefault();
        e.stopPropagation();

        if (typeof openAuthModal === 'function') {
            openAuthModal('login');
        }

        if (window.innerWidth <= 768) {
            document.querySelectorAll('.maplibregl-popup').forEach((popup) => popup.remove());
        }
    }
});
    
    
  function centerPopupVertically(lngLat) {

    if (window.innerWidth <= 768) return;

    const popup = document.querySelector('.maplibregl-popup');
    if (!popup) return;

    const mapRect = map.getContainer().getBoundingClientRect();
    const popupRect = popup.getBoundingClientRect();

    let translateY = 0;
    const topPad = 165;
    const bottomPad = 40;

    if (popupRect.top < mapRect.top + topPad) {
        translateY = (mapRect.top + topPad) - popupRect.top;
    } else if (popupRect.bottom > mapRect.bottom - bottomPad) {
        translateY = (mapRect.bottom - bottomPad) - popupRect.bottom;
    }

    if (translateY !== 0) {
        popup.style.transform = (popup.style.transform || '').replace(/ translateY\([^)]+\)/, '') + ` translateY(${translateY}px)`;
    }
}

    // ==============================
    // HELPER FUNCTIONS
    // ==============================

    window.zoomToProperty = function(lat, lng) {
        map.easeTo({
            center: [lng, lat],
            zoom: 12
        });
    }

    function panMarkerToBottom(lngLat, offsetRatio = 0.90) {

        const container = map.getContainer();
        const rect = container.getBoundingClientRect();

        const point = map.project(lngLat);
        const newY = rect.height * offsetRatio;

        const deltaY = point.y - newY;
        const center = map.getCenter();
        const centerPoint = map.project(center);

        const newCenter = map.unproject([
            centerPoint.x,
            centerPoint.y + deltaY
        ]);

        map.easeTo({ center: newCenter });
    }
    
document.addEventListener('click', function (e) {

    const item = e.target.closest('.location-item, .listing-item');
    if (!item) return;

    // always close dropdown
    closeSearchDropdown();

    const lat = parseFloat(item.dataset.lat);
    const lng = parseFloat(item.dataset.lng);

    if (isNaN(lat) || isNaN(lng)) return;

    // =========================
    // CITY → ONLY MOVE MAP
    // =========================
    if (item.classList.contains('city-item')) {

    const cityName = item.dataset.city || item.innerText.replace(/[^\w\s]/g, '').trim();


    selectedCity = cityName;
    seoCitySlug = slugify(cityName);
    cityFromUrl = cityName;
    updateSeoUrl();
    showCityBoundary(cityName);

    if (activeMarker) {
        activeMarker.remove();
        activeMarker = null;
    }

    document.getElementById('smartInput').value = cityName;

    setTimeout(() => {
        loadProperties();
    }, 1200);

    return;

 
}
    // =========================
    // ADDRESS → MOVE + POPUP (SAME AS LISTING)
    // =========================
    if (item.classList.contains('address-item')) {
        showPopupFromSearchItem(item);
        return;
    }

    // =========================
    // LISTING → MOVE + POPUP
    // =========================
    if (item.classList.contains('listing-item')) {
        const listingKey = item.dataset.external_id || item.querySelector('.property-image')?.dataset.key;
        if (listingKey) {
            fetch(`/api/v1/add-single-property/${listingKey}`).catch(() => {});
        }
        showPopupFromSearchItem(item);
        return;
    }
});


function showPopupFromSearchItem(item) {
    const popupProps = {
        id: item.dataset.id || '',
        external_id: item.dataset.external_id || '',
        url: item.dataset.slug || '',
        image: item.dataset.image || '',
        transaction: item.dataset.transaction || '',
        mls_status: item.dataset.transaction || '',
        price: item.dataset.price || 0,
        date: item.dataset.date || '',
        name: item.dataset.name || '',
        bedrooms: item.dataset.bedrooms || '',
        bathrooms: item.dataset.bathrooms || '',
        area: item.dataset.square || '',
        agency: item.dataset.agency || '',
    };

    openPropertyDetailModal(popupProps);

    const lat = parseFloat(item.dataset.lat);
    const lng = parseFloat(item.dataset.lng);
    if (map && !isNaN(lat) && !isNaN(lng)) {
        map.easeTo({ center: [lng, lat], zoom: Math.max(map.getZoom(), 12), duration: 800 });
    }
}

// ===== Mobile filters (HouseSigma style) =====
let hsMapListFeatures = [];
let lastMapFeatures = [];

function buildHsListCardHtml(props, geometry) {
    const status = mapListingStatus(props);
    const locked = mapBlurClass(status, props);
    const gate = mapLoginGateHtml(status, props);
    const isSold = isMapSoldListing(status, props);
    const isDelisted = props.transaction === 'Expired' || props.transaction === 'Terminated';
    let priceHtml;
    if (locked) {
        priceHtml = '<span style="color:#888;font-weight:600;">Login to view</span>';
    } else if (isSold && props.ClosePrice) {
        priceHtml = '<span style="text-decoration:line-through;color:#888;margin-right:6px;">$' +
            Number(props.price || 0).toLocaleString() + '</span>$' +
            Number(props.ClosePrice).toLocaleString();
    } else {
        const strike = isDelisted ? 'text-decoration:line-through;color:#888;' : 'color:var(--hs-primary);';
        priceHtml = '<span style="' + strike + 'font-weight:700;">$' +
            Number(props.price || 0).toLocaleString() + '</span>';
    }

    const img = (!locked && props.image) ? props.image : '';
    const imageAlt = escapeMapHtml(buildMapImageAlt(props));
    const meta = [
        (props.bedrooms ?? '-') + ' bed',
        (props.bathrooms ?? '-') + ' bath',
    ];
    if (props.area && !locked) meta.push(props.area + ' ft\u00B2');

    return '<div class="hs-list-item' + (locked ? ' sold-locked' : '') + '" data-id="' + (props.id || '') + '">' +
        gate +
        '<article class="hs-list-card ' + locked + '">' +
        (img
            ? '<img src="' + img + '" alt="' + imageAlt + '" loading="lazy">'
            : '<div class="hs-list-card-img hs-img-empty" style="width:100px;min-width:100px;height:76px;border-radius:8px;"><div class="hs-img-empty-fill"></div></div>') +
        '<div class="hs-list-card-body">' +
        '<div class="hs-list-card-price">' + priceHtml + '</div>' +
        '<div class="hs-list-card-addr">' + (locked ? 'Sold listing — login required' : (props.name || '')) + '</div>' +
        '<div class="hs-list-card-meta">' + meta.join(' \u00B7 ') + '</div>' +
        '</div></article></div>';
}

function renderMapListCards(features) {
    const sidebarOpen = document.getElementById('hsListSidebar')?.classList.contains('open');
    const isListView = document.querySelector('.map-housesigma')?.classList.contains('view-list');
    if (!sidebarOpen && !isListView) {
        return;
    }

    const capped = (features || []).slice(0, 150);
    hsMapListFeatures = features || [];
    const total = (features || []).length;
    const countText = total + (total === 1 ? ' property' : ' properties');

    ['hsListSidebarCount', 'hsMobileListCount'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.textContent = countText;
    });

    const html = capped.length
        ? capped.map((f) => buildHsListCardHtml(f.properties, f.geometry)).join('')
        : '<div class="hs-list-empty">No properties in this area</div>';

    const sidebarBody = document.getElementById('hsListSidebarBody');
    const mobileBody = document.getElementById('hsMobileListBody');
    if (sidebarBody) sidebarBody.innerHTML = html;
    if (mobileBody) mobileBody.innerHTML = html;

    if (typeof hydrateMapThumbnailsForFeatures === 'function') {
        hydrateMapThumbnailsForFeatures(capped, '.hs-list-item');
    }
}

function setDesktopListOpen(open) {
    const wrapper = document.querySelector('.map-search-wrapper');
    const sidebar = document.getElementById('hsListSidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
    wrapper?.classList.toggle('list-open', open);
    if (open && lastMapFeatures.length) {
        renderMapListCards(lastMapFeatures);
    }
    if (window.innerWidth >= 992) {
        setTimeout(() => map?.resize(), 320);
    }
}

function setHsViewMode(mode) {
    const root = document.querySelector('.map-housesigma');
    if (!root) return;

    const isList = mode === 'list';
    root.classList.toggle('view-list', isList);

    document.querySelectorAll('.hs-view-bar-btn').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.hsView === mode);
    });

    const params = new URLSearchParams(window.location.search);
    if (isList) params.set('view', 'list');
    else params.delete('view');
    const qs = params.toString();
    window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);

    const sidebar = document.getElementById('hsListSidebar');
    if (sidebar && window.innerWidth >= 992) {
        setDesktopListOpen(isList);
    }
    if (isList && window.innerWidth < 992 && lastMapFeatures.length) {
        renderMapListCards(lastMapFeatures);
    }

    if (!isList && window.innerWidth < 992) {
        setTimeout(() => window.hsMap?.resize?.(), 320);
    }
}

function onHsListCardClick(e) {
    const item = e.target.closest('.hs-list-item');
    if (!item) return;

    if (e.target.closest('.js-map-auth-open')) {
        return;
    }

    const id = item.dataset.id;
    const feature = hsMapListFeatures.find((f) => String(f.properties.id) === String(id));
    if (!feature) return;

    const status = mapListingStatus(feature.properties);
    if (mapBlurClass(status, feature.properties)) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof openAuthModal === 'function') {
            openAuthModal('login');
        }
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    if (!map) return;

    const coords = feature.geometry.coordinates;
    if (window.innerWidth < 992) {
        setHsViewMode('map');
    }

    if (typeof window.openPropertyFromFeature === 'function') {
        window.openPropertyFromFeature(feature);
    }

    map.flyTo({ center: coords, zoom: Math.max(map.getZoom(), 13), duration: 600 });
}

function toggleListSidebar() {
    const sidebar = document.getElementById('hsListSidebar');
    if (!sidebar) return;
    setDesktopListOpen(!sidebar.classList.contains('open'));
}

function initHsViewMode() {
    const view = new URLSearchParams(window.location.search).get('view');
    if (view === 'list') {
        if (window.innerWidth < 992) {
            setHsViewMode('list');
        } else {
            setDesktopListOpen(true);
        }
    }

    document.getElementById('hsListToggleBtnBar')?.addEventListener('click', toggleListSidebar);

    document.getElementById('hsListSidebarClose')?.addEventListener('click', () => {
        setDesktopListOpen(false);
    });

    document.querySelectorAll('.hs-view-bar-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            setHsViewMode(btn.dataset.hsView || 'map');
            setTimeout(() => window.hsMap?.resize?.(), 320);
        });
    });

    document.getElementById('hsListSidebarBody')?.addEventListener('click', onHsListCardClick);
    document.getElementById('hsMobileListBody')?.addEventListener('click', onHsListCardClick);
}

function buildHsDateColumns() {
    const container = document.getElementById('hsDateColumns');
    if (!container) return;

    const forSaleOptions = [
        'last_1_day', 'last_3_day', 'last_7_day', 'last_30_day', 'last_90_day',
        'all', 'more_than_15_days', 'more_than_30_days', 'more_than_60_days', 'more_than_90_days',
    ];
    const soldOptions = ['last_1_day', 'last_3_day', 'last_7_day', 'last_30_day', 'last_90_day', 'last_180_day', 'last_360_day'];
    for (let y = 2026; y >= 2003; y--) soldOptions.push('year_' + y);

    const renderCol = (title, group, options, selected) => {
        let html = '<div class="column"><p class="column-title">' + title + '</p>';
        options.forEach((val) => {
            const label = val.startsWith('year_') ? 'Year ' + val.replace('year_', '') : (HS_DATE_LABELS[val] || val);
            html += '<div class="hs-m-radio-option' + (val === selected ? ' selected' : '') + '" data-date-group="' + group + '" data-value="' + val + '">';
            html += '<span class="dot"></span><span>' + label + '</span></div>';
        });
        return html + '</div>';
    };

    container.innerHTML = renderCol('For Sale', 'sale', forSaleOptions, hsMobileDateSale) +
        renderCol('Sold & De-listed', 'sold', soldOptions, hsMobileDateSold);
}

function buildHsPropertyOptions() {
    const container = document.getElementById('hsPropertyOptions');
    if (!container) return;
    const current = selectedSubTypes.length === 1 ? selectedSubTypes[0] : '';
    container.innerHTML = HS_MOBILE_PROPERTY_TYPES.map((item) => {
        const active = item.value === current || (!item.value && !current) ? ' activated' : '';
        return '<p class="hs-m-option' + active + '" data-value="' + item.value + '">' + item.label + '</p>';
    }).join('');
}

function updateMobileFilterLabels() {
    const propBtn = document.getElementById('hsMobPropertyBtn');
    if (propBtn) {
        if (!selectedSubTypes.length) {
            propBtn.innerHTML = 'All property &#9660;';
        } else {
            const match = HS_MOBILE_PROPERTY_TYPES.find((t) => t.value === selectedSubTypes[0]);
            propBtn.textContent = (match ? match.label : selectedSubTypes[0]) + ' \u25BE';
        }
    }
    if (typeof updateSplitFilterLabels === 'function') {
        updateSplitFilterLabels();
    }
}

function openMobileSheet(sheetId) {
    closeMobileSheetsGlobal();
    document.querySelectorAll('.dropdown-menu').forEach((m) => {
        m.style.display = 'none';
        m.classList.remove('active-mobile');
    });
    const sheet = document.getElementById(sheetId);
    if (sheet) {
        sheet.style.display = 'flex';
        sheet.classList.add('open');
        document.getElementById('mobileOverlay')?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeMobileSheets() {
    document.querySelectorAll('.hs-m-sheet').forEach((s) => {
        s.classList.remove('open');
        s.style.display = '';
    });
    document.getElementById('mobileOverlay')?.classList.remove('active');
    if (window.innerWidth <= 991) {
        document.body.style.overflow = '';
    }
}

function initMobileFilters() {
    buildHsPropertyOptions();
    buildHsDateColumns();
    syncFilterUiFromState();

    document.getElementById('hsMobPropertyBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        buildHsPropertyOptions();
        openMobileSheet('hsSheetProperty');
    });
    document.getElementById('hsMobDateBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        buildHsDateColumns();
        openMobileSheet('hsSheetDate');
    });
    document.getElementById('hsMobFiltersBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        const mMin = document.querySelector('.hs-m-slider-min');
        const mMax = document.querySelector('.hs-m-slider-max');
        if (mMin) mMin.value = selectedMinPrice || 0;
        if (mMax) mMax.value = selectedMaxPrice || 5000000;
        openMobileSheet('hsSheetFilters');
    });
    document.getElementById('hsMobWatchBtn')?.addEventListener('click', () => {
        document.querySelector('.watched-dropdown .filter-btn')?.click();
    });

    document.getElementById('hsPropertyOptions')?.addEventListener('click', (e) => {
        const opt = e.target.closest('.hs-m-option');
        if (!opt) return;
        selectedSubTypes = opt.dataset.value ? [opt.dataset.value] : [];
        const propBtn = document.querySelector('.property-selector');
        if (propBtn) {
            propBtn.textContent = selectedSubTypes.length
                ? (HS_MOBILE_PROPERTY_TYPES.find((t) => t.value === selectedSubTypes[0])?.label || selectedSubTypes[0])
                : 'All Properties';
        }
        updateMobileFilterLabels();
        closeMobileSheets();
        loadProperties();
    });

    document.getElementById('hsDateColumns')?.addEventListener('click', (e) => {
        const opt = e.target.closest('.hs-m-radio-option');
        if (!opt) return;
        document.querySelectorAll('.hs-m-radio-option[data-date-group="' + opt.dataset.dateGroup + '"]').forEach((el) => {
            el.classList.toggle('selected', el === opt);
        });
        if (opt.dataset.dateGroup === 'sale') hsMobileDateSale = opt.dataset.value;
        else hsMobileDateSold = opt.dataset.value;
    });

    document.getElementById('hsDateApply')?.addEventListener('click', () => {
        syncDateRadiosFromState();
        updateMobileFilterLabels();
        closeMobileSheets();
        bustMapFetchCache();
        loadProperties();
    });

    document.querySelectorAll('.hs-m-status').forEach((btn) => {
        btn.addEventListener('click', function () {
            const value = this.dataset.status;
            if (value === 'Expired') {
                selectedStatus = ['Expired', 'Terminated', 'Suspended'];
            } else if (value === 'Sold') {
                selectedStatus = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
                hsMobileDateSold = hsMobileDateSold || 'all';
            } else {
                selectedStatus = ['New', 'Price Change', 'Extension', 'Previous Status'];
            }
            syncFilterUiFromState();
            syncDateRadiosFromState();
            if (typeof updateSplitFilterLabels === 'function') {
                updateSplitFilterLabels();
            }
            loadProperties();
        });
    });

    document.querySelectorAll('.hs-m-chips').forEach((ul) => {
        ul.addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (!li) return;
            ul.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
            li.classList.add('selected');
        });
    });

    const mMinSlider = document.querySelector('.hs-m-slider-min');
    const mMaxSlider = document.querySelector('.hs-m-slider-max');
    document.getElementById('hsFiltersApply')?.addEventListener('click', () => {
        selectedMinPrice = parseInt(mMinSlider?.value || 0, 10);
        selectedMaxPrice = parseInt(mMaxSlider?.value || 5000000, 10);
        if (minSlider) minSlider.value = selectedMinPrice;
        if (maxSlider) maxSlider.value = selectedMaxPrice;
        syncPriceSlidersFromState();
        updatePriceDisplay();
        const bedVal = document.querySelector('.hs-m-chips[data-mfilter="bedroom"] li.selected')?.textContent.trim();
        const bathVal = document.querySelector('.hs-m-chips[data-mfilter="bathroom"] li.selected')?.textContent.trim();
        const garageVal = document.querySelector('.hs-m-chips[data-mfilter="garage"] li.selected')?.textContent.trim();
        const basementVal = document.querySelector('.hs-m-chips[data-mfilter="basement1"] li.selected')?.textContent.trim();
        selectedBedrooms = (!bedVal || bedVal === 'All') ? null : bedVal;
        selectedBathrooms = (!bathVal || bathVal === 'All') ? null : bathVal;
        selectedBasement = (!garageVal || garageVal === 'All') ? null : garageVal;
        selectedBasement1 = (!basementVal || basementVal === 'All') ? null : basementVal;
        closeMobileSheets();
        bustMapFetchCache();
        loadProperties();
    });

    document.getElementById('hsSheetFilters')?.addEventListener('input', (e) => {
        if (!e.target.matches('.hs-m-slider-min, .hs-m-slider-max')) {
            return;
        }

        const sheetMin = document.querySelector('#hsSheetFilters .hs-m-slider-min');
        const sheetMax = document.querySelector('#hsSheetFilters .hs-m-slider-max');

        if (e.target.classList.contains('hs-m-slider-min')) {
            if (parseInt(e.target.value, 10) >= parseInt(sheetMax?.value || 0, 10)) {
                e.target.value = parseInt(sheetMax.value, 10) - parseInt(e.target.step || 1, 10);
            }
            selectedMinPrice = parseInt(e.target.value, 10);
            if (minSlider) minSlider.value = e.target.value;
        } else {
            if (parseInt(e.target.value, 10) <= parseInt(sheetMin?.value || 0, 10)) {
                e.target.value = parseInt(sheetMin.value, 10) + parseInt(e.target.step || 1, 10);
            }
            selectedMaxPrice = parseInt(e.target.value, 10);
            if (maxSlider) maxSlider.value = e.target.value;
        }

        const label = document.querySelector('#hsSheetFilters .hs-m-price-label');
        if (label) {
            label.textContent = buildPriceFilterText();
        }
        updatePriceDisplay();
    });
    document.getElementById('hsFiltersClear')?.addEventListener('click', () => {
        selectedMinPrice = 0;
        selectedMaxPrice = 5000000;
        selectedBedrooms = selectedBathrooms = selectedBasement = selectedBasement1 = null;
        if (mMinSlider) mMinSlider.value = 0;
        if (mMaxSlider) mMaxSlider.value = 5000000;
        if (minSlider) minSlider.value = 0;
        if (maxSlider) maxSlider.value = 5000000;
        syncPriceSlidersFromState();
        updatePriceDisplay();
        document.querySelectorAll('.hs-m-chips').forEach((ul) => {
            ul.querySelectorAll('li').forEach((li, i) => li.classList.toggle('selected', i === 0));
        });
        closeMobileSheets();
        loadProperties();
    });
    document.querySelectorAll('[data-close-sheet]').forEach((btn) => {
        btn.addEventListener('click', closeMobileSheets);
    });

    document.querySelectorAll('.hs-m-sheet').forEach((sheet) => {
        sheet.addEventListener('click', (e) => e.stopPropagation());
    });
}

initMobileFilters();
initHsViewMode();
initSplitDateDropdowns();

function initSplitDateDropdowns() {
    document.querySelectorAll('.hs-split-value[data-hs-date-toggle]').forEach((toggle) => {
        if (toggle.dataset.hsDateBound) return;
        toggle.dataset.hsDateBound = '1';
        toggle.addEventListener('click', function (e) {
            if (window.innerWidth <= 991) return;
            e.preventDefault();
            e.stopPropagation();
            const menu = this.closest('.dropdown')?.querySelector('.dropdown-menu');
            if (!menu) return;
            const isOpen = menu.style.display === 'block';
            document.querySelectorAll('.dropdown-menu').forEach((m) => { m.style.display = 'none'; });
            if (!isOpen) menu.style.display = 'block';
        });
    });
}

    map.on('click', function (e) {
        if (window.innerWidth > 991) return;

        const features = map.queryRenderedFeatures(e.point, {
            layers: ['unclustered-point']
        });

        if (!features.length) return;

        if (e.originalEvent) {
            e.originalEvent.stopPropagation();
        }
        openPropertyFromFeature(features[0]);
    });

});


//////////////////////////
//*****popup property///////
///////////////////////////
    

document.addEventListener('submit', function (e) {
    const form = e.target.closest('.hs-map-consult-form');
    if (!form) return;
    e.preventDefault();
    e.stopPropagation();

    const propertyId = form.dataset.propertyId;
    const submitBtn = form.querySelector('.hs-map-form-submit');
    const msgEl = form.querySelector('.hs-map-form-msg');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!propertyId) return;

    const fd = new FormData();
    fd.append('name', form.querySelector('[name="name"]')?.value || '');
    fd.append('email', form.querySelector('[name="email"]')?.value || '');
    fd.append('phone', form.querySelector('[name="phone"]')?.value || '');
    fd.append('content', form.querySelector('[name="content"]')?.value || '');
    fd.append('type', 'property');
    fd.append('data_id', propertyId);

    if (submitBtn) submitBtn.disabled = true;
    if (msgEl) { msgEl.hidden = true; msgEl.className = 'hs-map-form-msg'; }

    fetch('/send-consult', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((res) => res.json().then((data) => ({ ok: res.ok, data })).catch(() => ({ ok: res.ok, data: {} })))
        .then(({ ok, data }) => {
            if (msgEl) {
                msgEl.hidden = false;
                if (ok || data?.message) {
                    msgEl.textContent = data?.message || 'Thank you! We will contact you shortly.';
                    msgEl.classList.add('success');
                    form.reset();
                } else {
                    msgEl.textContent = data?.message || 'Could not send inquiry. Please try again.';
                    msgEl.classList.add('error');
                }
            }
        })
        .catch(() => {
            if (msgEl) {
                msgEl.hidden = false;
                msgEl.textContent = 'Could not send inquiry. Please try again.';
                msgEl.classList.add('error');
            }
        })
        .finally(() => { if (submitBtn) submitBtn.disabled = false; });
});

document.addEventListener('click', function (e) {

    const tabBtn = e.target.closest('.hs-map-tab-btn');
    if (tabBtn) {
        e.preventDefault();
        e.stopPropagation();
        const root = tabBtn.closest('.hs-map-popup-full');
        const tab = tabBtn.dataset.mapTab;
        if (root && tab) {
            root.querySelectorAll('.hs-map-tab-btn').forEach((b) => b.classList.toggle('active', b === tabBtn));
            root.querySelectorAll('.hs-map-tab-panel').forEach((p) => p.classList.toggle('active', p.dataset.mapPanel === tab));
            if (tab === 'rooms' && window.loadMapPopupRooms && window._hsActiveMapPopup && window._hsActiveMapPopupProps) {
                window.loadMapPopupRooms(
                    window._hsActiveMapPopup,
                    window._hsActiveMapPopupProps,
                    window._hsActiveMapPopupStatus
                );
            }
        }
        return;
    }

    const clusterItem = e.target.closest('.hs-cluster-list-item');
    if (clusterItem) {
        e.preventDefault();
        e.stopPropagation();
        const idx = parseInt(clusterItem.dataset.clusterIdx, 10);
        const feature = window._hsLastClusterLeaves?.[idx];
        if (!feature) return;
        if (typeof window.openPropertyFromFeature === 'function') {
            window.openPropertyFromFeature(feature);
        }
        return;
    }

    const shareBtn = e.target.closest('[data-map-share]');
    if (shareBtn) {
        e.preventDefault();
        e.stopPropagation();
        const shareUrl = shareBtn.dataset.shareUrl || window.location.href;
        const shareTitle = shareBtn.dataset.shareTitle || document.title;
        if (navigator.share) {
            navigator.share({ title: shareTitle, url: shareUrl }).catch(() => {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(shareUrl).then(() => {
                shareBtn.classList.add('active');
                shareBtn.setAttribute('title', 'Link copied!');
                setTimeout(() => {
                    shareBtn.classList.remove('active');
                    shareBtn.setAttribute('title', 'Share');
                }, 1400);
            }).catch(() => {});
        } else {
            window.prompt('Copy this link:', shareUrl);
        }
        return;
    }

    const detailsBtn = e.target.closest('.map-popup-details-btn');
    if (detailsBtn) {
        e.preventDefault();
        e.stopPropagation();
        const popupRoot = detailsBtn.closest('.open-property');
        if (popupRoot && popupRoot.dataset.url) {
            const modal = document.getElementById('propertyModal');
            const iframe = document.getElementById('propertyFrame');
            if (modal && iframe) {
                let url = popupRoot.dataset.url.trim();
                if (!url.startsWith('http')) {
                    url = serikCanonicalOrigin() + (url.startsWith('/') ? url : '/' + url);
                }
                url += (url.includes('?') ? '&' : '?') + 'iframe=1&t=' + Date.now();
                ensurePropertyModalOnBody();
                modal.style.display = 'block';
                showPropertyIframeLoader();
                lockBodyForPropertyModal();
                bindPropertyIframeLoad(iframe);
                iframe.src = url;
            }
        }
        return;
    }

    const property = e.target.closest('.open-property');
    if (!property) return;

    if (property.closest('.hs-map-popup-full') || property.closest('.clusterpopup')) {
        return;
    }

    if (e.target.closest('.js-map-auth-open, .map-sold-login-gate, .hs-map-popup-gallery, .hs-map-consult-form, .hs-map-tab-btn, .hs-map-gallery-nav, .hs-map-gallery-thumbs img, .map-popup-details-btn, .hs-cluster-list-item')) {
        return;
    }

    if (property.classList.contains('blurred-content')) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof openAuthModal === 'function') {
            openAuthModal('login');
        }
        return;
    }

    if (!property.dataset.url || property.dataset.url === '/properties/' || property.dataset.url === '/properties/undefined') {
        e.preventDefault();
        e.stopPropagation();
        if (typeof openAuthModal === 'function') {
            openAuthModal('login');
        }
        return;
    }

    const modal = document.getElementById('propertyModal');
    const iframe = document.getElementById('propertyFrame');

    let url = property.dataset.url.trim();

    // Ensure absolute URL
    if (!url.startsWith('https')) {
        url = serikCanonicalOrigin() + (url.startsWith('/') ? url : '/' + url);
    }

    url += (url.includes('?') ? '&' : '?') + 'iframe=1&t=' + Date.now();

    // ============================
    // 📱 MOBILE
    // ============================
    if (window.innerWidth <= 991) {
        if (property.closest('.maplibregl-popup')) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        ensurePropertyModalOnBody();
        modal.style.display = 'block';
        showPropertyIframeLoader();
        lockBodyForPropertyModal();
        bindPropertyIframeLoad(iframe);
        iframe.src = url;

        return; // stop here for mobile
    }

    // ============================
    // 💻 DESKTOP (RESTORED)
    // ============================
    modal.style.display = 'block';

    showPropertyIframeLoader();
    lockBodyForPropertyModal();

    bindPropertyIframeLoad(iframe);
    iframe.src = url;
});

const iframe = document.getElementById('propertyFrame');


iframe.addEventListener('load', function() {
    hidePropertyIframeLoader();
    enablePropertyIframeScroll(iframe);
});

const clearBtn_popup = document.getElementById("clearBtn_popup");

clearBtn_popup.addEventListener("click", function(){
const modal = document.getElementById('propertyModal');
const iframe = document.getElementById('propertyFrame');

    modal.style.display = 'none';

    //Clear iframe
    iframe.src = '';
    showPropertyIframeLoader();
    unlockBodyForPropertyModal();

});

const input = document.getElementById("smartInput");
const dropdown = document.getElementById("searchDropdown");
const loadMoreBtn = document.getElementById("loadMoreBtn");
const loader = document.getElementById("dropdownLoader");
const clearBtn = document.getElementById("clearBtn");
let skip = 0;
let currentKeyword = "";
let searchAbortController = null;
loadMoreBtn.style.display = "block";
let typingTimer;        // REQUIRED
const typingDelay = 280;

// Client-side cache for smart-search responses (keyed by full URL incl. keyword+skip)
const smartSearchCache = new Map();
const SMART_SEARCH_CACHE_MAX = 60;
function smartSearchFetch(url, signal) {
    if (smartSearchCache.has(url)) {
        return Promise.resolve(smartSearchCache.get(url));
    }
    return fetch(url, {
        signal: signal,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => {
            if (!res.ok) { throw new Error('Search request failed'); }
            return res.json();
        })
        .then(data => {
            if (smartSearchCache.size >= SMART_SEARCH_CACHE_MAX) {
                smartSearchCache.delete(smartSearchCache.keys().next().value);
            }
            smartSearchCache.set(url, data);
            return data;
        });
}

input.addEventListener("keyup", function () {

    let keyword = this.value;
    currentKeyword = keyword;
    skip = 0;

    clearTimeout(typingTimer);

    if(keyword.length < 2){
        dropdown.style.display = "none";
        document.getElementById("locationResults").innerHTML = "";
        document.getElementById("listingResults").innerHTML = "";
        return;
    }

    loader.style.display = "flex";
    dropdown.style.display = "block";

    typingTimer = setTimeout(() => {
        loadResults(keyword, true);
    }, typingDelay);
    
    
    
});


function getMatchingCities(keyword) {
    if (!keyword) return [];

    keyword = keyword.trim().toLowerCase(); // trim keyword

    const unique = new Map();

    Object.keys(cityCoordinates).forEach(city => {
        const cleanedCity = city.trim(); // trim city name
        const lower = cleanedCity.toLowerCase();

        if (lower.includes(keyword)) {
            if (!unique.has(lower)) {
                unique.set(lower, cleanedCity); // store cleaned city
            }
        }
    });

    return Array.from(unique.values()).slice(0, 5);
}

function loadResults(keyword, reset = false){

    let cityHTML = "";
    let addressHTML = "";
    let listingsHTML = "";

    const isChineseText = (text) => {
        const t = String(text ?? '').trim().toLowerCase();
        if (!t) return false;
        return t === 'chinese' || t.includes('chinese') || t.includes('中文') || t.includes('香港') || t.includes('台灣');
    };

    // 🔹 Cities
    const matchedCities = getMatchingCities(keyword);
    
  

    matchedCities.forEach(city => {
        const coords = cityCoordinates[city];
        if (isChineseText(city)) {
            return;
        }

        cityHTML += `
            <div class="location-item city-item"
                data-city="${city}"
                data-lat="${coords.lat}"
                data-lng="${coords.lng}">
                🌆 ${city}
            </div>
        `;
    });

    document.getElementById("listingResults").innerHTML = "";

    if (searchAbortController) {
        searchAbortController.abort();
    }
    searchAbortController = new AbortController();
    const isMlsKey = /^[a-z]{1,2}\d{5,}$/i.test(String(keyword || '').trim());
    const searchTimeoutId = setTimeout(() => searchAbortController.abort(), isMlsKey ? 45000 : 15000);

    const searchUrl = `/api/v1/smart-search?keyword=${encodeURIComponent(keyword)}&skip=${skip}`;
    smartSearchFetch(searchUrl, searchAbortController.signal)
    .then(data => {

        loader.style.display = "none";
        dropdown.style.display = "block";

        if(!Array.isArray(data) || data.length === 0){
            if (reset) {
                document.getElementById("locationResults").innerHTML = cityHTML;
                document.getElementById("listingResults").innerHTML = '';
            }
            return;
        }

        data.forEach(item => {

            const listingStatus = item.MlsStatus === 'New'
                ? (item.TransactionType === 'For Sale' ? 'For Sale' : 'For Lease')
                : (item.MlsStatus ?? '');

            if (isChineseText(item.UnparsedAddress ?? '') || isChineseText(item.PropertySubType ?? '')) {
                return;
            }

            const garageCount = item.CoveredSpaces ?? item.covered_spaces ?? 0;
            const parkingCount = item.ParkingSpaces ?? 0;

            // 🔹 Addresses (suggestions)
            addressHTML += `
                <div class="location-item address-item" 
                    data-lat="${item.lat}"
                    data-lng="${item.lng}">
                    📍 ${item.UnparsedAddress}
                </div>
            `;

       listingsHTML += `
                 ${mapLoginGateHtml(listingStatus)}
                    <div class="listing-item ${mapBlurClass(listingStatus)}" style="width: 100%"
                      data-lat="${item.lat}"
                    data-lng="${item.lng}"
                     data-external_id="${item.ListingKey}"
                     data-price="$${Number(item.ListPrice).toLocaleString()}"
                     data-name="${item.UnparsedAddress}"
                     data-bedrooms="${item.BedroomsTotal ?? 0}"
                     data-bathrooms="${item.BathroomsTotalInteger ?? 0}"
                     data-parking="${garageCount}"
                     data-image="${item.MediaURL}"
                     data-square="${item.LivingAreaRange}"
                     data-agency="${item.ListOfficeName}"
                     data-transaction="${listingStatus}"
                     data-slug="${item.URL}"
                    >
                        <img src="${item.MediaURL}"   loading="lazy"
                        data-key="${item.ListingKey}"
                                class="property-image"
                                style="width:100px;height:80px;object-fit:cover;border-radius:6px;"
                            />
                        <div style="width: 100%">
                            <div class="price">
                                $${Number(item.ListPrice).toLocaleString()}
                                <p style="float:right">${
                                    item.MlsStatus === 'New'
                                      ? (item.TransactionType === 'For Sale' ? 'For Sale' : 'For Lease')
                                      : (item.MlsStatus ?? '')
                                  }</p>
                            </div>
                            <div>${item.UnparsedAddress}</div>
                            <p style="float:left">${item.PropertySubType}</p>
                            <small style="float:right">
                                🛏 ${item.BedroomsTotal ?? 0}
                                🛁 ${item.BathroomsTotalInteger ?? 0}
                                🚘 ${garageCount}
                            </small>
                        </div>
                    </div>
                
            `;
        });
        // Import only when user picks a listing (not on every keystroke — avoids TREB API storm)
        const finalSuggestions = cityHTML + addressHTML;

        if(reset){
            document.getElementById("locationResults").innerHTML = finalSuggestions;
            document.getElementById("listingResults").innerHTML = listingsHTML;
        } else {
            document.getElementById("listingResults")
                .insertAdjacentHTML("beforeend", listingsHTML);
        }

        setTimeout(loadImages, 0);

    })
    .catch(err => {
        if (err.name !== 'AbortError') {
            console.log(err);
        }
    })
    .finally(() => {
        clearTimeout(searchTimeoutId);
        loader.style.display = "none";
        if (!document.getElementById("listingResults").innerHTML && !document.getElementById("locationResults").innerHTML) {
            dropdown.style.display = cityHTML ? "block" : "none";
            if (cityHTML && reset) {
                document.getElementById("locationResults").innerHTML = cityHTML;
            }
        }
    });
}

function loadImages() {

    document.querySelectorAll(".property-image").forEach(img => {

        const listingKey = img.dataset.key;

        if (img.dataset.loaded) return;

        const currentSrc = img.getAttribute('src') || '';
        if (currentSrc.startsWith('http') && !currentSrc.includes('placeholder')) {
            img.dataset.loaded = 'true';
            return;
        }

        fetch(`/api/v1/property-image/${listingKey}`)
            .then(res => res.json())
            .then(data => {

                const imageUrl = data.media || (Array.isArray(data.images) ? data.images[0] : null);

                if (imageUrl) {

                    img.src = imageUrl;

                    const listingItem = img.closest('.listing-item');
                    if (listingItem) {
                        listingItem.dataset.image = imageUrl;
                    }

                    img.style.opacity = "0";
                    img.onload = () => {
                        img.style.transition = "opacity 0.3s ease";
                        img.style.opacity = "1";
                    };
                }

                img.dataset.loaded = "true";

            })
            .catch(() => {});
    });
}




// LOAD MORE CLICK
loadMoreBtn.addEventListener("click", function(){
    loader.style.display = "flex";
    skip += 5;  //NEXT PAGE
    loadResults(currentKeyword, false);

});

clearBtn.addEventListener("click", function(){
loader.style.display = "none";
        dropdown.style.display = "none";
        document.getElementById("smartInput").value='';

});






///////////////////////////////  filter menu bar ////////////////////////////////////////
document.addEventListener("DOMContentLoaded", function () {

    // Toggle Active Buttons
    document.querySelectorAll(".filter-btn[data-type]").forEach(btn => {
        btn.addEventListener("click", function () {
            let type = this.dataset.type;

            // Remove active from same type
            document.querySelectorAll(`.filter-btn[data-type="${type}"]`)
                .forEach(b => b.classList.remove("active"));

            this.classList.add("active");
        });
    });

    // Dropdown Toggle (desktop)
    document.querySelectorAll(".dropdown-toggle").forEach(toggle => {
        toggle.addEventListener("click", function (e) {
            if (this.dataset.type === 'status') {
                return;
            }
            if (window.innerWidth <= 991) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            let menu = this.closest('.dropdown')?.querySelector('.dropdown-menu');
            if (!menu) return;

            const isOpen = menu.style.display === 'block';
            document.querySelectorAll(".dropdown-menu").forEach(m => m.style.display = "none");

            if (!isOpen) {
                menu.style.display = "block";
            }
        });
    });

    document.querySelectorAll('.hs-split-value[data-hs-date-toggle]').forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            if (window.innerWidth <= 991) return;
            e.preventDefault();
            e.stopPropagation();
            const menu = this.closest('.dropdown')?.querySelector('.dropdown-menu');
            if (!menu) return;
            const isOpen = menu.style.display === 'block';
            document.querySelectorAll('.dropdown-menu').forEach((m) => m.style.display = 'none');
            if (!isOpen) menu.style.display = 'block';
        });
    });

    // Dropdown Item Click
    document.querySelectorAll(".dropdown-item").forEach(item => {
        item.addEventListener("click", function () {
            let parent = this.closest(".dropdown");
            let toggle = parent.querySelector(".dropdown-toggle");

            toggle.textContent = this.textContent + " ▾";
            toggle.classList.add("active");

            parent.querySelector(".dropdown-menu").style.display = "none";
        });

    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
        if (e.target.closest('.hs-split-value, .dropdown-toggle, .dropdown-menu')) {
            return;
        }
        document.querySelectorAll(".dropdown-menu")
            .forEach(m => m.style.display = "none");
    });
    
    
    document.addEventListener("click", function (e) {
    const dropdown = document.getElementById("searchDropdown");
    //const input = document.getElementById("smartInput");

    // If click is NOT inside dropdown AND NOT on input
    if (
        !dropdown.contains(e.target) &&
        !input.contains(e.target)
    ) {
        dropdown.style.display = "none";
    }
});

  

});





const dropdown1 = document.querySelector('.dropdown-card');

dropdown1?.addEventListener('click', function (e) {
  e.stopPropagation();
});




  

window.addEventListener("load", function () {

    let currentStep = 1;
    const steps = document.querySelectorAll(".form-step[data-step]");
    const totalSteps = steps.length;
    
    function showStep(step) {
        steps.forEach(el => el.classList.add("d-none"));
        const active = document.querySelector(`.form-step[data-step="${step}"]`);
        if (active) active.classList.remove("d-none");
    }
    
    console.log("Total steps:", totalSteps);
console.log("Current step:", currentStep);

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
  

document.addEventListener('DOMContentLoaded', function() {
    // Select the form by ID
    const form = document.getElementById('botble-real-estate-forms-fronts-auth-register-form');
    if (form) {
        // Find all elements inside the form with class 'row-cols-lg-2'
        const targets = form.querySelectorAll('.row-cols-lg-2');
        targets.forEach(el => {
            el.classList.remove('row-cols-lg-2'); // remove old class
            el.classList.add('row-cols-lg-1');    // add new class
        });
    }
});

  document.addEventListener("DOMContentLoaded", function () {
    const collapseEl = document.getElementById('collapseMain');
    if (!collapseEl) {
        return;
    }

    document.body.style.overflow = "hidden";

    function handleCollapse() {
        if (window.innerWidth <= 768) {
            collapseEl.classList.add('collapse');
        } else {
            collapseEl.classList.remove('collapse');
            collapseEl.classList.add('show');
        }
    }

    handleCollapse();
    window.addEventListener('resize', handleCollapse);
});
document.querySelector('.apply-all')?.addEventListener('click', function(){
    if(window.innerWidth < 768){
        let collapse = document.getElementById('filterCollapse');
        bootstrap.Collapse.getOrCreateInstance(collapse).hide();
    }
});


function closeMobileSheetsGlobal() {
    document.querySelectorAll('.hs-m-sheet').forEach((s) => {
        s.classList.remove('open');
        s.style.display = '';
    });
    const overlay = document.getElementById('mobileOverlay');
    if (overlay) overlay.classList.remove('active');
    if (window.innerWidth <= 991) {
        document.body.style.overflow = '';
    }
}

const overlay = document.getElementById('mobileOverlay');

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {

        if (window.innerWidth > 768) return;

        const dropdown = this.closest('.dropdown')?.querySelector('.dropdown-menu');
        if (!dropdown) return;

        e.preventDefault();
        e.stopPropagation();

        closeMobileSheetsGlobal();
        document.querySelectorAll('.dropdown-menu').forEach(d => {
            d.classList.remove('active-mobile');
        });

        dropdown.classList.add('active-mobile');
        overlay.classList.add('active');
    });
});

// Close
overlay?.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(d => {
        d.classList.remove('active-mobile');
    });
    closeMobileSheetsGlobal();
});
document.addEventListener("DOMContentLoaded", function () {

    const dropdown = document.getElementById("searchDropdown");
   // const input = document.getElementById("smartInput");

    // Close dropdown function
    function closeDropdown() {
        dropdown.style.display = "none";
    }

    // Open dropdown (optional if you already handle it)
    function openDropdown() {
        document.querySelectorAll('.modal-backdrop').forEach((el) => {
            if (!document.getElementById('modalLogin')?.classList.contains('show')) {
                el.remove();
            }
        });
        if (!document.getElementById('modalLogin')?.classList.contains('show')) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
        dropdown.style.display = "block";
    }

    // Close when clicking any item inside dropdown
    dropdown.addEventListener("click", function (e) {

        // location item OR listing item OR any clickable result
        if (
            e.target.closest(".location-item") ||
            e.target.closest(".listing-item")
        ) {
            closeDropdown();
        }
    });

    // Optional: open when typing
    input.addEventListener("focus", openDropdown);
});


function closeSearchDropdown() {
    const dropdown = document.getElementById("searchDropdown");
    const loader = document.getElementById("dropdownLoader");

    if (dropdown) dropdown.style.display = "none";
    if (loader) loader.style.display = "none";
}

</script>






