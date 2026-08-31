@php
    Theme::set('breadcrumbEnabled', 'no');
    Theme::set('pageTitle', $account->name);
    Theme::set('pageH1ProvidedByContent', true);
@endphp

<section class="agent-detail-section">
    <div class="agent-header">
        <div class="agent-avatar">
            {{ RvMedia::image($account->avatar_url, $account->name, 'medium-square') }}
        </div>
        <div class="agent-info">
            <h1 class="h2 agent-name">{{ $account->name }} {!! $account->badge !!}</h1>
            @if($account->company)
                <p class="agent-company">{!! BaseHelper::clean(__('Company Agent at :company', ['company' => "<strong>$account->company</strong>"])) !!}</p>
            @endif
            @php
                $agentTitle = \App\Support\AgentProfile::title($account);
                $agentSpecialties = \App\Support\AgentProfile::listFrom($account, 'specialties');
                $agentAreas = \App\Support\AgentProfile::listFrom($account, 'service_areas');
                $agentLanguages = \App\Support\AgentProfile::listFrom($account, 'languages');
            @endphp
            @if($agentTitle !== '' && $agentTitle !== trim((string) $account->company))
                <p class="serik-agent-meta__title">{{ $agentTitle }}</p>
            @endif
            <!--div class="agent-contact-info">
                @if($account->phone && ! setting('real_estate_hide_agency_phone', false))
                    <a href="tel:{{ $account->phone }}" class="agent-info-item">
                        <x-core::icon name="ti ti-phone" />
                        {{ $account->phone }}
                    </a>
                @endif
                @if($account->email && ! setting('real_estate_hide_agency_email', false))
                    <a href="mailto:{{ $account->email }}" class="agent-info-item">
                        <x-core::icon name="ti ti-mail" />
                        {{ $account->email }}
                    </a>
                @endif
                <div class="agent-info-item">
                    <x-core::icon name="ti ti-calendar" />
                    {{ __('Joined') }} {{ $account->created_at->diffForHumans() }}
                </div>
            </div-->

            {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}

           
                <div class="agent-whatsapp-section mt-3 serik-agent-meta__ctas">
                    @php
                        $agentContactUrl = \App\Support\AgentInquiryFormContext::contactUrl($account);
                    @endphp
                    @if($agentContactUrl)
                        <a href="{{ $agentContactUrl }}" class="tf-btn primary">{{ __('Contact') }}</a>
                    @else
                        <p class="serik-agent-meta__unavailable" role="status">{{ __('Contact is currently unavailable for this agent.') }}</p>
                    @endif
                </div>
            
        </div>
    </div>

    @if($account->description)
        <div class="agent-about-section">
            <h5>{{ __('About Agent') }}</h5>
            <p class="agent-description">{!! BaseHelper::clean($account->description) !!}</p>
            @if($agentSpecialties !== [])
                <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Specialties') }}:</span> {{ implode(', ', $agentSpecialties) }}</p>
            @endif
            @if($agentAreas !== [])
                <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Service areas') }}:</span> {{ implode(', ', $agentAreas) }}</p>
            @endif
            @if($agentLanguages !== [])
                <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Languages') }}:</span> {{ implode(', ', $agentLanguages) }}</p>
            @endif
        </div>
    @endif

    @if ($properties->isNotEmpty())
        <div class="agent-properties-section">
            <h5>{{ __('Properties by this agent') }}</h5>
            @include(Theme::getThemeNamespace('views.real-estate.properties.index'))
        </div>
    @endif

    {!! apply_filters('real_estate_agent_details', null, $account) !!}
</section>
