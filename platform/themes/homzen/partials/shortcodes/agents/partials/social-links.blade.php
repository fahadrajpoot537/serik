@php

$accountSocials = $account->getMetaData('social_links', true);

if ($accountSocials !== null && $accountSocials !== '[]') {
    $socials = Theme::convertSocialLinksToArray($accountSocials);
} else {
    $socials = [];
}
@endphp

<style>
    
    .box-agent .box-img .agent-social {
        background-color: #fff;
        border-radius: 8px;
        bottom: 0;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        left: 37px;
        opacity: 0;
        padding: 12px 0;
        position: absolute;
        right: 37px;
        transition: all .3s ease;
        visibility: hidden;
        z-index: 1;
    
        justify-items: center; /* Centers items horizontally in their grid cell */
        justify-content: center; /* Centers the whole grid if items are fewer than columns */
    }
    .box-agent .box-img .agent-social li:only-child {
    grid-column: 1 / -1;  /* Span all columns */
    justify-self: center;  /* Center it */
}
.box-agent .box-img .agent-social li a svg,
.box-agent .box-img .agent-social li a img {
    width: 150px;
    height: 150px;
}
    
</style>

@if ($socials)
    <ul class="agent-social">
        @foreach($socials as $social)
            @continue((! $social->getIcon() && ! $social->getImage()) || ! $social->getUrl())

            <li>
                <a href="{{ $social->getUrl() }}" target="_blank" rel="noopener noreferrer">
                    @if ($social->getImage())
                        {!! $social->getImageHtml() !!}
                    @else
                        {!! $social->getIconHtml() !!}
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
