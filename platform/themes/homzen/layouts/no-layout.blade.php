<style>
    /* Property detail iframe only — do not stretch login/auth modals. */
    #propertyModal .modal-content,
    .property-modal .modal-content {
        height: 95% !important;
        margin-top: -20px !important;
    }
</style>

@extends(Theme::getThemeNamespace('layouts.base'))

@section('content')
    {!! Theme::content() !!}
@endsection

{!! apply_filters('theme_front_footer_content', null) !!}
