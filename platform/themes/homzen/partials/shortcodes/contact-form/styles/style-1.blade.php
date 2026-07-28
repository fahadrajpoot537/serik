@php
    use Botble\Shortcode\Facades\Shortcode;

    $contactInfo = Shortcode::fields()->getTabsData(['label', 'content'], $shortcode);
@endphp



<section class="flat-section flat-contact">
    <div class="container">
        @if($shortcode->show_information_box)
        <div class="row">
            <div class="col-lg-8">
        @endif
                <div class="contact-content">
                    @if($shortcode->title)
                        <h5>{!! BaseHelper::clean($shortcode->title) !!}</h5>
                    @endif
                    @if($shortcode->description)
                        <p class="body-2 text-variant-1">{!! BaseHelper::clean($shortcode->description) !!}</p>
                    @endif

                    {!! $form->renderForm() !!}
                </div>
        @if($shortcode->show_information_box)
            </div>
            <div class="col-lg-4">
                <div class="contact-info">
                    <h5>{!! BaseHelper::clean($shortcode->contact_title) !!}</h5>
                    <ul class="contact-form-list">
                        @foreach($contactInfo as $item)
                            <li class="box">
                                <div class="text-1 title">{!! BaseHelper::clean($item['label']) !!}</div>
                                <p class="p-16 text-variant-1">{!! BaseHelper::clean(nl2br(preg_replace('/[“”]/u', '"', $item['content']))) !!}</p>
                            </li>
                        @endforeach

                        @if($shortcode->show_social_links && ($items = Theme::getSocialLinks()))
                            <li class="box">
                                <div class="text-1 title">{{ __('Follow Us:') }}</div>
                                <ul class="box-social">
                                    @foreach($items as $item)
                                        <li>
                                            <a title="{{ $item->getName() }}" href="{{ $item->getUrl() }}" class="item">
                                                {!! $item->getIconHtml(['style' => 'stroke-width: 2']) !!}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
        </div>
    </div>
</section>


<script>
 const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
            if (
                node.nodeType === 1 &&
                node.innerText &&
                node.innerText.includes("Send message successfully!")
            ) {
                setTimeout(function () {
                    window.location.href = "{{ url('/contact-thanks') }}";
                }, 100);
            }
        });
    });
});

// Observe body for toast changes
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Ensure contact reCAPTCHA token is in FormData (explicit grecaptcha.render).
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('contact-form')) {
        return;
    }
    if (typeof grecaptcha === 'undefined' || window.contactRecaptchaWidgetId == null) {
        return;
    }
    const token = grecaptcha.getResponse(window.contactRecaptchaWidgetId) || '';
    let input = form.querySelector('textarea[name="g-recaptcha-response"], input[name="g-recaptcha-response"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'g-recaptcha-response';
        form.appendChild(input);
    }
    input.value = token;
}, true);

if (typeof window.initSerikRecaptcha === 'function') {
    window.initSerikRecaptcha();
}
</script>

