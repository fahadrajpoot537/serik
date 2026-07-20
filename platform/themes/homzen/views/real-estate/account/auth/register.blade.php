<style>
    .form-step {
    transition: all 0.3s ease;
}
</style>
@php
    if (theme_option('breadcrumb_background_image_register')) {
        Theme::set('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image_register'));
    }
@endphp

{!! $form->renderForm() !!}

