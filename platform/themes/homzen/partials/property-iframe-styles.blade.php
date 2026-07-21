@if(request()->boolean('iframe'))
<style>
    #header, .footer, .icon-bar, .top-header, .lc_text-widget, .mobile-bottom-nav,
    .breadcrumb-wrap, .flat-breadcrumb {
        display: none !important;
    }
    html {
        height: 100%;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
    }
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff;
        min-height: 100%;
        height: auto !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
        overscroll-behavior-y: contain;
        position: relative !important;
    }
    #wrapper {
        overflow: visible !important;
        min-height: 100%;
    }

    @media (max-width: 991px) {
        .flat-property-detail .row > .col-lg-4 {
            position: static !important;
            top: auto !important;
        }

        html,
        body {
            height: auto !important;
            min-height: 100%;
            overflow-x: hidden !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
            position: relative !important;
        }
    }

    /* Keep the action bar (wishlist / share / fullscreen) visible & pinned in popup */
    .property-page-nav {
        display: block !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 50;
        padding: 2px 10px !important;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
    }
    .property-page-nav .property-nav-wrapper {
        align-items: center !important;
    }
    .property-page-nav .property-nav-list {
        margin: 0 !important;
        gap: 18px !important;
        padding: 8px 0 !important;
    }
    .property-page-nav .property-nav-list li a {
        font-size: 13px !important;
    }
    .property-page-nav .property-actions {
        margin-right: 0 !important;
        gap: 8px !important;
    }
    .property-page-nav .property-actions button,
    .property-page-nav .property-actions .item {
        padding: 6px 10px !important;
        font-size: 13px !important;
        border-radius: 8px !important;
    }

    /* Pin the contact / schedule-viewing form while details scroll */
    .flat-property-detail .row > .col-lg-4 {
        align-self: flex-start;
        position: sticky;
        top: 52px;
    }
    .flat-section, .flat-section-v2, .flat-section-v3, .flat-section-v5 {
        padding-top: 6px !important;
        padding-bottom: 6px !important;
    }
    .container, .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    .header-property-detail {
        padding: 10px 12px !important;
        margin: 0 0 6px !important;
        border-radius: 10px !important;
    }
    .header-property-detail .content-top {
        padding-bottom: 8px !important;
        margin-bottom: 8px !important;
        gap: 8px !important;
    }
    .header-property-detail .title {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        font-size: 1.05rem !important;
        line-height: 1.3 !important;
    }
    .header-property-detail #cityRegion,
    .header-property-detail #propertyType {
        line-height: 1.25 !important;
    }
    .header-property-detail #propertyType {
        font-size: 14px !important;
    }
    .header-property-detail .box-price h4 {
        font-size: 15px !important;
        line-height: 1.35 !important;
        margin: 0 !important;
    }
    .single-property-overview,
    .single-property-desc,
    .single-property-contact,
    .widget-box {
        padding: 6px 8px !important;
        margin-bottom: 6px !important;
    }
    .single-property-contact .title,
    .single-property-contact .h7,
    .single-property-desc .title,
    .single-property-overview .h7.title {
        margin-bottom: 4px !important;
        font-size: 14px !important;
    }
    .single-property-contact .contact-form {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px !important;
    }
    .single-property-contact .contact-form .ip-group {
        margin-bottom: 0 !important;
        flex: 1 1 100%;
    }
    .single-property-contact .contact-form .ip-group:has([name="name"]),
    .single-property-contact .contact-form .ip-group:has([name="phone"]) {
        flex: 1 1 calc(50% - 3px);
        max-width: calc(50% - 3px);
    }
    .single-property-contact .contact-form label {
        margin-bottom: 2px !important;
        font-size: 11px !important;
    }
    .single-property-contact .contact-form .form-control,
    .single-property-contact .contact-form textarea {
        padding: 6px 8px !important;
        font-size: 12px !important;
        min-height: auto !important;
        line-height: 1.35 !important;
    }
    .single-property-contact .contact-form textarea {
        min-height: 52px !important;
    }
    .single-property-contact .tf-btn {
        padding: 8px 12px !important;
        font-size: 13px !important;
        margin-top: 4px !important;
        width: 100%;
    }
    .info-box1 {
        padding: 4px 0 !important;
        margin: 0 !important;
    }
    .info-box .col.item {
        padding-top: 2px !important;
        padding-bottom: 2px !important;
    }
    .info-box1 .content .label,
    .info-box1 .content span {
        font-size: 12px !important;
        line-height: 1.25 !important;
    }
    .flat-latest-property {
        padding-top: 6px !important;
        margin-top: 0 !important;
    }
    .flat-latest-property .section-title,
    .flat-latest-property .box-title {
        font-size: 1rem !important;
        margin-top: 4px !important;
        margin-bottom: 6px !important;
    }
    .row.g-3, .row.g-2, .row.g-lg-4 {
        --bs-gutter-y: 0.25rem;
        --bs-gutter-x: 0.25rem;
    }
    .tab button {
        padding: 8px 10px !important;
        font-size: 14px !important;
    }
    .tabcontent {
        padding: 4px 0 !important;
    }
</style>
@endif
<style>
    .hs-desc-truncated .hs-desc-full {
        display: none !important;
    }
    .hs-desc-expanded .hs-desc-short {
        display: none !important;
    }
    .hs-desc-expanded .hs-desc-full {
        display: block !important;
    }
    .hs-desc-toggle {
        color: #0255a1;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        margin-top: 4px;
        display: inline-block;
        border: none;
        background: none;
        padding: 0;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.hs-desc-toggle').forEach((btn) => {
        btn.addEventListener('click', function () {
            const wrap = this.closest('.hs-desc-wrap');
            const body = wrap?.querySelector('.hs-desc-body');
            if (!body) return;
            const expanded = body.classList.toggle('hs-desc-expanded');
            body.classList.toggle('hs-desc-truncated', !expanded);
            this.textContent = expanded ? 'Show Less' : 'Show More';
        });
    });
});
</script>
