@extends('core/base::forms.form')

@section('form_end')
    @if ($form->getModel()->id)
        <x-core::modal
            type="info"
            id="add-credit-modal"
            :title="trans('plugins/real-estate::account.add_credit_to_account')"
            button-id="confirm-add-credit-button"
            :button-label="trans('plugins/real-estate::account.add')"
        >
            @include('plugins/real-estate::account.admin.credit-form', ['account' => $form->getModel()])
        </x-core::modal>
    @endif
@endsection
