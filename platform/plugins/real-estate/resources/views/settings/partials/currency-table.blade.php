<textarea
    class="hidden"
    id="currencies"
    name="currencies"
>@json($currencies)</textarea>
<textarea
    class="hidden"
    id="deleted_currencies"
    name="deleted_currencies"
></textarea>

<div class="swatches-container">
    <div class="header">
        <div class="swatch-item">
            {{ trans('plugins/real-estate::currency.code') }}
        </div>
        <div class="swatch-item">
            {{ trans('plugins/real-estate::currency.symbol') }}
        </div>
        <div class="swatch-item swatch-exchange-rate">
            {{ trans('plugins/real-estate::currency.exchange_rate') }}
        </div>
        <div class="swatch-is-default">
            {{ trans('plugins/real-estate::currency.is_default') }}
        </div>
        <div class="swatch-advanced">
            {{ trans('plugins/real-estate::currency.advanced') }}
        </div>
        <div class="remove-item">
            {{ trans('plugins/real-estate::currency.remove') }}
        </div>
    </div>

    <ul class="swatches-list"></ul>

    <div class="d-flex justify-content-between w-100 align-items-center">
        <x-core::form.helper-text>
            {{ trans('plugins/real-estate::settings.currency.form.instruction') }}
        </x-core::form.helper-text>

        <a class="js-add-new-attribute" href="javascript:void(0)">
            {{ trans('plugins/real-estate::settings.currency.form.new_currency') }}
        </a>
    </div>
</div>
