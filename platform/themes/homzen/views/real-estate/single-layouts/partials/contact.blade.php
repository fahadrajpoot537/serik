@php
    $model = $model ?? $property ?? null;
    $isProject = $model instanceof \Botble\RealEstate\Models\Project;
@endphp

<div @class(['widget-box single-property-contact', $class ?? null])>
    @if (! RealEstateHelper::hideAgentInfoInPropertyDetailPage() && ($account = $model->author) && $account->exists)
        <div class="h7 title fw-7">{{ __('Contact Agency') }}</div>

        <!--div class="box-avatar">
            <div class="avatar avt-100 round">
                <a href="{{ $account->url }}" class="d-block">
                    {{ RvMedia::image($account->avatar?->url ?: $account->avatar_url, $account->name) }}
                </a>
            </div>
            <div class="info line-clamp-1">
                <div class="text-1 name">
                    <a href="{{ $account->url }}">{{ $account->name }} {!! $account->badge !!}</a>
                </div>
                @if ($account->phone && ! setting('real_estate_hide_agency_phone', false))
                    <a href="tel:{{ $account->phone }}" class="info-item">{{ $account->phone }}</a>
                @elseif($hotline = theme_option('hotline'))
                    <a href="tel:{{ $hotline }}" class="info-item">{{ $hotline }}</a>
                @endif
                @if ($account->email && ! setting('real_estate_hide_agency_email', false))
                    <a href="mailto:{{ $account->email }}" class="info-item">{{ $account->email }}</a>
                @endif
            </div>
        </div-->
    @else
        <div class="h7 title fw-7">{{ __('Contact') }}</div>
    @endif

    @if ($isProject)
        {!! apply_filters('project_right_details_info', null, $model) !!}
    @else
        {!! apply_filters('property_right_details_info', null, $model) !!}
    @endif

    @if (RealEstateHelper::isEnabledConsultForm())
        {!! apply_filters('before_consult_form', null, $model) !!}

        {!! \Botble\RealEstate\Forms\Fronts\ConsultForm::create()
            ->formClass('contact-form')
            ->setFormInputWrapperClass('ip-group')
            ->modify('content', 'textarea', ['attr' => ['class' => 'form-control']])
            ->modify('submit', 'submit', ['attr' => ['class' => 'tf-btn primary w-100'], 'label' => __('Schedule Viewing')])
            ->add('type', 'hidden', ['attr' => ['value' => $isProject ? 'project' : 'property']])
            ->add('data_id', 'hidden', ['attr' => ['value' => $model->getKey()]])
            ->addBefore('content', 'data_name', 'text', ['label' => false, 'attr' => ['value' => $model->name, 'disabled' => true]])
            ->renderForm()
        !!}

        {!! apply_filters('after_consult_form', null, $model) !!}
    @endif

    @if (! RealEstateHelper::hideAgentInfoInPropertyDetailPage() && ($account = $model->author) && $account->exists && $account->whatsapp)
        <div class="whatsapp-contact-section mt-4 pt-4" style="border-top: 1px solid #e4e4e4;">
            <div class="text-center mb-3">
                <p class="mb-2" style="color: #666; font-size: 14px;">{{ __('Or get instant response via WhatsApp') }}</p>
            </div>
            <div class="text-center">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $account->whatsapp) }}" target="_blank" class="contact-whatsapp-btn justify-content-center">
                    <x-core::icon name="ti ti-brand-whatsapp" />
                    <span>{{ __('Chat on WhatsApp') }}</span>
                </a>
            </div>
        </div>
    @endif
</div>

