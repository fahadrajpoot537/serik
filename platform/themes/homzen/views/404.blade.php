@php
    SeoHelper::setTitle(__('404 - Page Not found'));
    Theme::fireEventGlobalAssets();
@endphp

@extends(Theme::getThemeNamespace('layouts.base'))

@section('content')
    <div class="error-page">
        <div class="error-content">
            <div class="error-number-wrapper">
                <h1 class="error-number">
                    <span class="digit">4</span>
                    <span class="digit zero">
                        <svg viewBox="0 0 100 100" class="house-icon">
                            <path d="M50 10 L20 40 L20 80 L80 80 L80 40 Z" fill="currentColor" opacity="0.2"/>
                            <path d="M50 10 L20 40 L80 40 Z" fill="currentColor" opacity="0.3"/>
                            <rect x="35" y="50" width="15" height="20" fill="currentColor" opacity="0.4"/>
                            <rect x="55" y="50" width="15" height="15" fill="currentColor" opacity="0.4"/>
                            <rect x="45" y="70" width="10" height="10" fill="currentColor" opacity="0.5"/>
                        </svg>
                    </span>
                    <span class="digit">4</span>
                </h1>
            </div>
            
            <h2 class="error-title">{{ __('Page Not Found') }}</h2>
            <p class="error-description">{{ __('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.') }}</p>
            
            <div class="error-actions">
                <a href="{{ BaseHelper::getHomepageUrl() }}" class="tf-btn primary">
                    <i class="icon-home"></i>
                    {{ __('Back To Home') }}
                </a>
                <a href="javascript:history.back()" class="tf-btn style-border">
                    <i class="icon-arrow-left"></i>
                    {{ __('Go Back') }}
                </a>
            </div>
        </div>
    </div>
@endsection
