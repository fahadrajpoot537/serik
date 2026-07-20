@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.body class="text-center">
            <h3 class="mb-3">{{ trans('plugins/real-estate::category.name') }}</h3>
            <p class="text-muted mb-4">{{ trans('plugins/real-estate::category.intro.description', ['default' => 'Organize your properties and projects into categories for better content management.']) }}</p>

            <x-core::button
                tag="a"
                :href="route('property_category.create')"
                color="primary"
                icon="ti ti-plus"
            >
                {{ trans('plugins/real-estate::category.create') }}
            </x-core::button>
        </x-core::card.body>
    </x-core::card>
@endsection