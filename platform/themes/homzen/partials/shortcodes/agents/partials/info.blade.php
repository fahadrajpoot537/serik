@php
    $title = \App\Support\AgentProfile::title($account);
    $bio = \App\Support\AgentProfile::cardBio($account);
    $specialties = \App\Support\AgentProfile::listFrom($account, 'specialties');
    $areas = \App\Support\AgentProfile::listFrom($account, 'service_areas');
    $languages = \App\Support\AgentProfile::listFrom($account, 'languages');
    $contactUrl = \App\Support\AgentInquiryFormContext::contactUrl($account);
    $profileUrl = $account->url ?? null;
@endphp

<div class="serik-agent-meta">
    @if($title !== '')
        <p class="serik-agent-meta__title">{{ $title }}</p>
    @endif

    @if($bio !== '')
        <p class="serik-agent-meta__bio">{{ $bio }}</p>
    @endif

    @if($specialties !== [])
        <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Specialties') }}:</span> {{ implode(', ', $specialties) }}</p>
    @endif

    @if($areas !== [])
        <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Service areas') }}:</span> {{ implode(', ', $areas) }}</p>
    @endif

    @if($languages !== [])
        <p class="serik-agent-meta__row"><span class="serik-agent-meta__label">{{ __('Languages') }}:</span> {{ implode(', ', $languages) }}</p>
    @endif

    @if ($account->properties_count)
        <p class="serik-agent-meta__row">
            <x-core::icon name="ti ti-home" />
            @if ($account->properties_count === 1)
                {{ __('1 Property') }}
            @else
                {{ __(':count Properties', ['count' => $account->properties_count]) }}
            @endif
        </p>
    @endif

    <div class="serik-agent-meta__ctas">
        @if($profileUrl && ! \Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile())
            <a href="{{ $profileUrl }}" class="serik-agent-meta__cta serik-agent-meta__cta--profile">{{ __('View Profile') }}</a>
        @endif
        @if($contactUrl)
            <a href="{{ $contactUrl }}" class="serik-agent-meta__cta serik-agent-meta__cta--contact">{{ __('Contact') }}</a>
        @endif
    </div>
</div>
