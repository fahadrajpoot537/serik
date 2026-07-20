@php
    if (theme_option('breadcrumb_background_image_forgot_password')) {
        Theme::set('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image_forgot_password'));
    }
@endphp

{!! $form->renderForm() !!}
