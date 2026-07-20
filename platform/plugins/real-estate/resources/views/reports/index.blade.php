@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="row row-cards g-2">
                <div class="col-6 col-sm-6 col-lg-3">
                    <x-core::stat-widget.item
                        :label="trans('plugins/real-estate::reports.total_properties')"
                        :value="number_format($propertyStats['total'])"
                        icon="ti ti-building"
                        color="primary"
                        column="col-12"
                    />
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                    <x-core::stat-widget.item
                        :label="trans('plugins/real-estate::reports.active_properties')"
                        :value="number_format($propertyStats['active'])"
                        icon="ti ti-check"
                        color="success"
                        column="col-12"
                    />
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                    <x-core::stat-widget.item
                        :label="trans('plugins/real-estate::reports.pending_properties')"
                        :value="number_format($propertyStats['pending'])"
                        icon="ti ti-clock"
                        color="warning"
                        column="col-12"
                    />
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                    <x-core::stat-widget.item
                        :label="trans('plugins/real-estate::reports.expired_properties')"
                        :value="number_format($propertyStats['expired'])"
                        icon="ti ti-alert-triangle"
                        color="danger"
                        column="col-12"
                    />
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-8 mb-3 mb-md-0">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.monthly_properties') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="monthly-property-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-12 col-md-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.property_by_type') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="property-by-type-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.project_statistics') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row row-cards g-2">
                        <div class="col-6">
                            <x-core::stat-widget.item
                                :label="trans('plugins/real-estate::reports.total_projects')"
                                :value="number_format($projectStats['total'])"
                                icon="ti ti-building-skyscraper"
                                color="info"
                                column="col-12"
                            />
                        </div>
                        <div class="col-6">
                            <x-core::stat-widget.item
                                :label="trans('plugins/real-estate::reports.featured_projects')"
                                :value="number_format($projectStats['featured'])"
                                icon="ti ti-star"
                                color="warning"
                                column="col-12"
                            />
                        </div>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-12 col-md-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.account_statistics') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row row-cards g-2">
                        <div class="col-6">
                            <x-core::stat-widget.item
                                :label="trans('plugins/real-estate::reports.total_accounts')"
                                :value="number_format($accountStats['total'])"
                                icon="ti ti-users"
                                color="primary"
                                column="col-12"
                            />
                        </div>
                        <div class="col-6">
                            <x-core::stat-widget.item
                                :label="trans('plugins/real-estate::reports.new_accounts_this_month')"
                                :value="number_format($accountStats['new_this_month'])"
                                icon="ti ti-user-plus"
                                color="success"
                                column="col-12"
                            />
                        </div>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.property_by_location') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    @if(count($propertyByLocationStats) > 0)
                        <div class="table-responsive">
                            <x-core::table>
                                <x-core::table.header>
                                    <x-core::table.header.cell>
                                        {{ trans('plugins/real-estate::reports.location') }}
                                    </x-core::table.header.cell>
                                    <x-core::table.header.cell>
                                        {{ trans('plugins/real-estate::reports.count') }}
                                    </x-core::table.header.cell>
                                </x-core::table.header>
                                <x-core::table.body>
                                    @foreach($propertyByLocationStats as $item)
                                        <x-core::table.body.row>
                                            <x-core::table.body.cell>
                                                {{ $item->name }}
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell>
                                                {{ number_format($item->total) }}
                                            </x-core::table.body.cell>
                                        </x-core::table.body.row>
                                    @endforeach
                                </x-core::table.body>
                            </x-core::table>
                        </div>
                    @else
                        <x-core::empty-state :title="trans('plugins/real-estate::report.no_data_available')" />
                    @endif
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-12 col-md-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::reports.recent_transactions') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    @if(count($recentTransactions) > 0)
                        <div class="table-responsive">
                            <x-core::table>
                                <x-core::table.header>
                                    <x-core::table.header.cell>
                                        {{ trans('plugins/real-estate::reports.account') }}
                                    </x-core::table.header.cell>
                                    <x-core::table.header.cell>
                                        {{ trans('plugins/real-estate::reports.amount') }}
                                    </x-core::table.header.cell>
                                    <x-core::table.header.cell class="d-none d-md-table-cell">
                                        {{ trans('plugins/real-estate::reports.created_at') }}
                                    </x-core::table.header.cell>
                                </x-core::table.header>
                                <x-core::table.body>
                                    @foreach($recentTransactions as $transaction)
                                        <x-core::table.body.row>
                                            <x-core::table.body.cell>
                                                {{ $transaction->account->name }}
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell>
                                                {{ format_price($transaction->amount) }}
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell class="d-none d-md-table-cell">
                                                {{ $transaction->created_at->format('M d, Y') }}
                                            </x-core::table.body.cell>
                                        </x-core::table.body.row>
                                    @endforeach
                                </x-core::table.body>
                            </x-core::table>
                        </div>
                    @else
                        <x-core::empty-state :title="trans('plugins/real-estate::report.no_transactions_found')" />
                    @endif
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        window.propertyByTypeStats = {!! json_encode($propertyByTypeStats) !!};
        window.monthlyPropertyStats = {!! json_encode($monthlyPropertyStats) !!};
        window.trans = {
            property_count: '{{ trans('plugins/real-estate::reports.property_count') }}'
        };
    </script>
@endpush
