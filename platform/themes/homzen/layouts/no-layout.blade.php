 <link rel="icon" type="image/x-icon" href="https://serik.ca/storage/whatsapp-image-2025-1.png">

<style>
    .modal-content{
        height:95% !important;
        margin-top:-20px !important;
    }
</style>

@extends(Theme::getThemeNamespace('layouts.base'))

@section('content')
    {!! Theme::content() !!}
@endsection

{!! apply_filters('theme_front_footer_content', null) !!}