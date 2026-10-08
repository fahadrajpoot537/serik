@php
    $logoSlides = [
        ['src' => asset('OREA.png'), 'alt' => 'OREA'],
        ['src' => asset('Realtor.png'), 'alt' => 'Realtor'],
        ['src' => asset('Reco.png'), 'alt' => 'RECO'],
        ['src' => asset('TREB.png'), 'alt' => 'TREB'],
    ];
@endphp

<style>
/* Contact Us: partner logos — same seamless marquee pattern as photo carousel */
.serik-contact-logos{
    --scl-gap:28px;
    --scl-logo-w:clamp(120px, 16vw, 180px);
    --scl-logo-h:clamp(56px, 7vw, 80px);
    width:100%;
    padding:0.5rem 0 2.25rem;
    background:transparent;
}
.serik-contact-logos__viewport{
    --scl-fade:rgba(232,154,157,.92); /* Contact Us pink, slightly darker */
    position:relative;
    width:100%;
    overflow:hidden;
}
.serik-contact-logos__viewport::before,
.serik-contact-logos__viewport::after{
    content:"";
    position:absolute;
    top:0;
    bottom:0;
    width:clamp(48px,12%,110px);
    z-index:2;
    pointer-events:none;
}
.serik-contact-logos__viewport::before{
    left:0;
    background:linear-gradient(90deg, var(--scl-fade) 0%, rgba(232,154,157,0) 100%);
}
.serik-contact-logos__viewport::after{
    right:0;
    background:linear-gradient(270deg, var(--scl-fade) 0%, rgba(232,154,157,0) 100%);
}
.serik-contact-logos__track{
    display:flex;
    width:max-content;
    animation:serikContactLogosMarquee 28s linear infinite;
    will-change:transform;
}
.serik-contact-logos__track:hover{
    animation-play-state:paused;
}
.serik-contact-logos__group{
    display:flex;
    flex:0 0 auto;
    align-items:center;
    gap:var(--scl-gap);
    padding-inline-end:var(--scl-gap);
}
.serik-contact-logos__card{
    flex:0 0 var(--scl-logo-w);
    width:var(--scl-logo-w);
    height:var(--scl-logo-h);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0.35rem 0.5rem;
}
.serik-contact-logos__card img{
    display:block;
    width:100%;
    height:100%;
    object-fit:contain;
    object-position:center;
}
@keyframes serikContactLogosMarquee{
    from{transform:translate3d(0,0,0)}
    to{transform:translate3d(-50%,0,0)}
}
@media (max-width:991px){
    .serik-contact-logos{
        --scl-gap:18px;
        --scl-logo-w:clamp(100px, 28vw, 140px);
        --scl-logo-h:clamp(48px, 12vw, 64px);
        padding:0.25rem 0 1.75rem;
    }
}
@media (prefers-reduced-motion:reduce){
    .serik-contact-logos__track{animation:none}
}
</style>

<section class="serik-contact-logos" aria-label="{{ __('Industry affiliations') }}">
    <div class="serik-contact-logos__viewport">
        <div class="serik-contact-logos__track">
            @foreach ([false, true] as $isClone)
                <div class="serik-contact-logos__group" @if ($isClone) aria-hidden="true" @endif>
                    @foreach ($logoSlides as $i => $logo)
                        <div class="serik-contact-logos__card">
                            <img
                                src="{{ $logo['src'] }}"
                                alt="{{ $logo['alt'] }}"
                                loading="{{ (! $isClone && $i < 4) ? 'eager' : 'lazy' }}"
                                decoding="async"
                                width="180"
                                height="80"
                            >
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
