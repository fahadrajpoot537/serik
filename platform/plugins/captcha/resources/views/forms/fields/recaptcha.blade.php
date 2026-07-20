@switch(setting('captcha_type'))
    @case('v2')
         <x-core::form-group>
            <div class="cf-turnstile"
                 data-sitekey="{{ config('services.turnstile.site_key') }}">
            </div>
        </x-core::form-group>
    @break

    @case('v3')
         <x-core::form-group>
            <div class="cf-turnstile"
                 data-sitekey="{{ config('services.turnstile.site_key') }}">
            </div>
        </x-core::form-group>
    @break
@endswitch
