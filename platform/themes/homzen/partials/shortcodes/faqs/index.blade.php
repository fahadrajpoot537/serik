<section class="flat-section">
    <div class="container">
        {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode, 'headingTag' => 'h2', 'defaultTitle' => __('Frequently Asked Questions')]) !!}

        <div class="row justify-content-center">
            <div class="col-lg-8">
                @switch($shortcode->display_type)
                    @case('list')
                        <div class="tf-faq serik-faq-list">
                            <ul class="box-faq" id="wrapper-faq">
                                @foreach($faqs as $faq)
                                    @php $expanded = $loop->first && ($shortcode->expand_first_time ?? true); @endphp
                                    <li class="faq-item">
                                        <a href="#accordion-faq-{{ $faq->getKey() }}" class="faq-header {{ $expanded ? '' : 'collapsed' }}" data-bs-toggle="collapse" aria-expanded="{{ $expanded ? 'true' : 'false' }}" aria-controls="accordion-faq-{{ $faq->getKey() }}" id="faq-question-{{ $faq->getKey() }}">
                                            {!! BaseHelper::clean($faq->question) !!}
                                        </a>
                                        <div id="accordion-faq-{{ $faq->getKey() }}" @class(['collapse', 'show' => $expanded]) data-bs-parent="#wrapper-faq" role="region" aria-labelledby="faq-question-{{ $faq->getKey() }}">
                                            <p class="faq-body">
                                                {!! BaseHelper::clean($faq->answer) !!}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @break

                    @default
                        @foreach($categories as $category)
                            <div class="tf-faq">
                                <h5>{{ $category->name }}</h5>
                                <ul class="box-faq" id="wrapper-faq-{{ $categorySlug = Str::slug($category->name) }}">
                                    @foreach($category->faqs as $faq)
                                        <li class="faq-item">
                                            <a href="#{{ $categorySlug }}-faq-{{ $faq->getKey() }}" class="faq-header collapsed" data-bs-toggle="collapse" aria-expanded="false" aria-controls="{{ $categorySlug }}-faq-{{ $faq->getKey() }}" id="{{ $categorySlug }}-q-{{ $faq->getKey() }}">
                                                {!! BaseHelper::clean($faq->question) !!}
                                            </a>
                                            <div id="{{ $categorySlug }}-faq-{{ $faq->getKey() }}" class="collapse" data-bs-parent="#wrapper-faq-{{ Str::slug($category->name) }}" role="region" aria-labelledby="{{ $categorySlug }}-q-{{ $faq->getKey() }}">
                                                <p class="faq-body">
                                                    {!! BaseHelper::clean($faq->answer) !!}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                @endswitch
            </div>
        </div>
    </div>
</section>
