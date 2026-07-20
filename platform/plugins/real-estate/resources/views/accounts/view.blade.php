@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::account.account_info') }}
                    </x-core::card.title>
                </x-core::card.header>
                
                <x-core::card.body>
                    @if($account->avatar_url)
                        <div class="text-center mb-3">
                            <img src="{{ RvMedia::getImageUrl($account->avatar_url, 'medium') }}" alt="{{ $account->name }}" class="rounded" width="100" height="100">
                        </div>
                    @endif
                    
                    <dl class="row">
                        <dt class="col-5">{{ trans('plugins/real-estate::account.name') }}</dt>
                        <dd class="col-7">{{ $account->name }}</dd>
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.email') }}</dt>
                        <dd class="col-7">{{ $account->email }}</dd>
                        
                        @if($account->phone)
                            <dt class="col-5">{{ trans('plugins/real-estate::account.phone') }}</dt>
                            <dd class="col-7">{{ $account->phone }}</dd>
                        @endif
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.status') }}</dt>
                        <dd class="col-7">
                            @if($account->blocked_at)
                                <span class="badge bg-danger text-danger-fg">{{ trans('plugins/real-estate::account.blocked') }}</span>
                            @else
                                <span class="badge bg-success text-success-fg">{{ trans('plugins/real-estate::account.active') }}</span>
                            @endif
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.email_verified') }}</dt>
                        <dd class="col-7">
                            @if($account->confirmed_at)
                                <span class="badge bg-blue-lt text-blue">{{ trans('core/base::base.yes') }}</span>
                            @else
                                <span class="badge bg-yellow-lt text-yellow">{{ trans('core/base::base.no') }}</span>
                            @endif
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.account_credits') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-azure-lt text-azure badge-pill">{{ $account->credits ?: 0 }}</span>
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.properties_count') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-blue text-blue-fg">{{ $account->properties()->count() }}</span>
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/real-estate::account.projects_count') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-green text-green-fg">{{ $account->projects()->count() }}</span>
                        </dd>
                    </dl>
                </x-core::card.body>
            </x-core::card>
            
            {{-- Verification Section --}}
            <div class="card mt-3">
                @if($account->is_verified)
                    <div class="card-status-top bg-success"></div>
                @else
                    <div class="card-status-top bg-warning"></div>
                @endif
                
                <div class="card-header">
                    <h3 class="card-title">
                        <x-core::icon name="ti ti-shield-check" />
                        {{ trans('plugins/real-estate::account.verification_section') }}
                    </h3>
                </div>
                
                <div class="card-body">
                    @if($account->is_verified)
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex">
                                <div class="me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M5 12l5 5l10 -10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">{{ trans('plugins/real-estate::account.verified') }}</h4>
                                    <div class="text-secondary">{{ trans('plugins/real-estate::account.account_verified_successfully') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="datagrid">
                                    @if($account->verifiedBy)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ trans('plugins/real-estate::account.verified_by') }}</div>
                                            <div class="datagrid-content">
                                                <strong>{{ $account->verifiedBy->name }}</strong>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($account->verified_at)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ trans('plugins/real-estate::account.verified_at') }}</div>
                                            <div class="datagrid-content">
                                                {{ $account->verified_at->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($account->verification_note)
                                <div class="col-12">
                                    <div class="card bg-blue-lt">
                                        <div class="card-body">
                                            <h4 class="card-title">
                                                <x-core::icon name="ti ti-notes" />
                                                {{ trans('plugins/real-estate::account.verification_note') }}
                                            </h4>
                                            <p class="text-secondary mb-0">{{ $account->verification_note }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#unverify-modal">
                                <x-core::icon name="ti ti-shield-x" />
                                {{ trans('plugins/real-estate::account.unverify_account') }}
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <div class="d-flex">
                                <div class="me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">{{ trans('plugins/real-estate::account.not_verified') }}</h4>
                                    <div class="text-secondary">{{ trans('plugins/real-estate::account.account_not_verified_yet') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted mb-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"></path>
                                <circle cx="12" cy="11" r="1"></circle>
                                <line x1="12" y1="12" x2="12" y2="14.5"></line>
                            </svg>
                            <h3>{{ trans('plugins/real-estate::account.verification_pending') }}</h3>
                            <p class="text-muted">{{ trans('plugins/real-estate::account.click_verify_to_approve') }}</p>
                            
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#verify-modal">
                                <x-core::icon name="ti ti-shield-check" />
                                {{ trans('plugins/real-estate::account.verify_account') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/real-estate::account.recent_activity') }}
                    </x-core::card.title>
                </x-core::card.header>
                
                <x-core::card.body>
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ trans('plugins/real-estate::account.recent_properties') }}</h4>
                            @php
                                $recentProperties = $account->properties()->latest()->limit(5)->get();
                            @endphp
                            @if($recentProperties->count() > 0)
                                <div class="list-group">
                                    @foreach($recentProperties as $property)
                                        <a href="{{ route('property.edit', $property->id) }}" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">{{ $property->name }}</h5>
                                                    <small>{{ $property->created_at->diffForHumans() }}</small>
                                                </div>
                                                {!! BaseHelper::clean($property->status->toHtml()) !!}
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">{{ trans('plugins/real-estate::account.no_properties') }}</p>
                            @endif
                        </div>
                        
                        <div class="col-md-6">
                            <h4>{{ trans('plugins/real-estate::account.recent_projects') }}</h4>
                            @php
                                $recentProjects = $account->projects()->latest()->limit(5)->get();
                            @endphp
                            @if($recentProjects->count() > 0)
                                <div class="list-group">
                                    @foreach($recentProjects as $project)
                                        <a href="{{ route('project.edit', $project->id) }}" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">{{ $project->name }}</h5>
                                                    <small>{{ $project->created_at->diffForHumans() }}</small>
                                                </div>
                                                {!! BaseHelper::clean($project->status->toHtml()) !!}
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">{{ trans('plugins/real-estate::account.no_projects') }}</p>
                            @endif
                        </div>
                    </div>
                    
                    @if($account->company || $account->description)
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-md-12">
                                <h4>{{ trans('plugins/real-estate::account.account_details') }}</h4>
                                <dl class="row">
                                    @if($account->company)
                                        <dt class="col-3">{{ trans('plugins/real-estate::account.company') }}</dt>
                                        <dd class="col-9">{{ $account->company }}</dd>
                                    @endif
                                    
                                    @if($account->description)
                                        <dt class="col-3">{{ trans('plugins/real-estate::account.description') }}</dt>
                                        <dd class="col-9">{{ $account->description }}</dd>
                                    @endif
                                    
                                    @if($account->blocked_at && $account->blocked_reason)
                                        <dt class="col-3">{{ trans('plugins/real-estate::account.form.blocked_reason') }}</dt>
                                        <dd class="col-9">
                                            <div class="alert alert-danger" role="alert">
                                                {{ $account->blocked_reason }}
                                            </div>
                                        </dd>
                                    @endif
                                    
                                    <dt class="col-3">{{ trans('plugins/real-estate::account.member_since') }}</dt>
                                    <dd class="col-9">{{ $account->created_at->format('M d, Y') }}</dd>
                                </dl>
                            </div>
                        </div>
                    @endif
                </x-core::card.body>
                
                <x-core::card.footer>
                    <a href="{{ route('account.edit', $account->id) }}" class="btn btn-primary">
                        <x-core::icon name="ti ti-edit" />
                        {{ trans('plugins/real-estate::account.edit_account') }}
                    </a>
                    
                    <a href="{{ route('account.index') }}" class="btn btn-secondary">
                        <x-core::icon name="ti ti-arrow-left" />
                        {{ trans('plugins/real-estate::account.back_to_list') }}
                    </a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>

    <!-- Verify Modal -->
    @if(!$account->is_verified)
        <x-core::modal
            id="verify-modal"
            :title="trans('plugins/real-estate::account.verify_account_confirmation')"
            button-id="confirm-verify-button"
            :button-label="trans('plugins/real-estate::account.verify_account')"
            button-class="btn-success"
            size="md"
        >
            <x-core::form :url="route('account.verify', $account->id)" method="PUT">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                <polyline points="11 12 12 12 12 16 13 16"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">{{ trans('plugins/real-estate::account.verify_account_confirmation') }}</h4>
                            <div class="text-secondary">{{ trans('plugins/real-estate::account.verify_account_confirmation_desc', ['name' => $account->name]) }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <x-core::icon name="ti ti-notes" />
                        {{ trans('plugins/real-estate::account.verification_note') }}
                    </label>
                    <textarea 
                        class="form-control" 
                        name="verification_note" 
                        rows="3" 
                        placeholder="{{ trans('plugins/real-estate::account.verification_note_placeholder') }}"
                    ></textarea>
                    <small class="form-hint">{{ trans('plugins/real-estate::account.verification_note_help') }}</small>
                </div>
            </x-core::form>
        </x-core::modal>
    @else
        <x-core::modal
            id="unverify-modal"
            :title="trans('plugins/real-estate::account.unverify_account_confirmation')"
            button-id="confirm-unverify-button"
            :button-label="trans('plugins/real-estate::account.unverify_account')"
            button-class="btn-warning"
            size="md"
        >
            <x-core::form :url="route('account.unverify', $account->id)" method="PUT">
                <div class="alert alert-warning" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">{{ trans('plugins/real-estate::account.unverify_account_confirmation') }}</h4>
                            <div class="text-secondary">{{ trans('plugins/real-estate::account.unverify_account_confirmation_desc', ['name' => $account->name]) }}</div>
                        </div>
                    </div>
                </div>
                
                <p>{{ trans('plugins/real-estate::account.confirm_unverify') }}</p>
            </x-core::form>
        </x-core::modal>
    @endif
@endsection

@push('footer')
    <script>
        $(document).ready(function() {
            $('#confirm-verify-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $form = $('#verify-modal form');
                
                $button.addClass('button-loading');
                
                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.error) {
                            Botble.showNotice('error', response.message);
                        } else {
                            Botble.showNotice('success', response.message);
                            $('#verify-modal').modal('hide');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        Botble.handleError(xhr);
                    },
                    complete: function() {
                        $button.removeClass('button-loading');
                    }
                });
            });
            
            $('#confirm-unverify-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $form = $('#unverify-modal form');
                
                $button.addClass('button-loading');
                
                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.error) {
                            Botble.showNotice('error', response.message);
                        } else {
                            Botble.showNotice('success', response.message);
                            $('#unverify-modal').modal('hide');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        Botble.handleError(xhr);
                    },
                    complete: function() {
                        $button.removeClass('button-loading');
                    }
                });
            });
        });
    </script>
@endpush