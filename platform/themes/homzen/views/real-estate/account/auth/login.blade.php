@php
    if (theme_option('breadcrumb_background_image_login')) {
        Theme::set('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image_login'));
    }
@endphp

{!! $form->renderForm() !!}
