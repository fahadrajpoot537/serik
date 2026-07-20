@php
    $layout = 'plugins/real-estate::themes.dashboard.layouts.master';
@endphp

@extends('core/table::table')

@section('content')
    @parent
@stop

@push('footer')
    <x-core::modal.action
        class="modal-confirm-renew"
        :title="trans('plugins/real-estate::account.renew_confirmation')"
        :description="(RealEstateHelper::isEnabledCreditsSystem()
            ? trans('plugins/real-estate::account.are_you_sure_you_want_to_renew_this_property_it_will_takes_1')
            : trans('plugins/real-estate::account.are_you_sure_you_want_to_renew_this_property')) . '?'"
        :submit-button-label="trans('plugins/real-estate::account.yes')"
        :submit-button-attrs="['class' => 'button-confirm-renew']"
    />
@endpush
