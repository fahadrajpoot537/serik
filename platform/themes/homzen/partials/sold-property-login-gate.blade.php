@once
<style>
    .property-login-overlay-caption {
        color: #fff !important;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .property-login-overlay-caption a {
        color: #fff !important;
        font-weight: 600;
        text-decoration: underline;
    }
</style>
@endonce

<div class="property-login-overlay {{ $overlayClass ?? '' }}">
    <div class="property-login-overlay-content text-center">
        <p class="property-login-overlay-caption">
            Local real estate board's rules require you to validate login to see this property.
            <a href="#modalLogin" data-bs-toggle="modal">(Full Details Here)</a>
        </p>
        <a href="#modalLogin" data-bs-toggle="modal" class="btn btn-light fw-bold">
            Confirm Login
        </a>
    </div>
</div>
