<div class="col-lg-4 col-md-6">
    <div class="footer-cl-4 serik-footer-newsletter">
        @if (! empty($config['title']))
            <div class="serik-footer-newsletter__title">
                {!! BaseHelper::clean($config['title']) !!}
            </div>
        @endif

        @if (! empty($config['subtitle']))
            <p class="serik-footer-newsletter__subtitle">{!! BaseHelper::clean($config['subtitle']) !!}</p>
        @endif

        {!! $form->renderForm() !!}
    </div>
</div>
