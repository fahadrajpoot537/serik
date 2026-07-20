
<style>
.tf-btn.primary.size-1 { /* new hover color */
    
    font-size:14px;
    /* smooth transition */
}
/* Change background color on hover */
.tf-btn.primary.size-1:hover { /* new hover color */
    color: #ffffff; /* optional: change text color on hover */
    transition: 0.3s;
    font-size:14px;
    border:#e20a0a;/* smooth transition */
}
</style>

@if($shortcode->button_label && $shortcode->button_url)
    <a style="padding:8px 15px;" href="{{ $shortcode->button_url }}" @class(['tf-btn primary size-1', $class ?? null])>
        {{ $shortcode->button_label }}
    </a>
@endif
