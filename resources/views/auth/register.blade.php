@extends('layouts.landing')

@section('title', 'Create your ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')) . ' account')

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout">
    <!-- <a class="signup-brand" href="{{ route('home') }}" aria-label="{{ $siteSettings['brand_name'] ?? 'InoManage' }} home">
      <img src="{{ $siteSettings['logo_url'] ?? asset('assets/images/inomanage.png') }}" alt="{{ $siteSettings['brand_name'] ?? 'InoManage' }}" class="signup-logo-img" width="34" height="34" style="width: 34px; height: 34px; max-width: 34px; max-height: 34px; object-fit: contain; border-radius: 9px;" />
      <span class="signup-wordmark">{{ $siteSettings['brand_name'] ?? 'InoManage' }}</span>
    </a> -->

    <section class="signup-card" aria-labelledby="signup-title">
      <header class="signup-heading">
        <h1 id="signup-title">Create your account</h1>
        <p>Get started with a 3-day free trial, no credit card required</p>
      </header>

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">
          {{ $errors->first() }}
        </p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Full Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Enter your full name" autocomplete="name" required autofocus />

        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="username" required />

        <label for="phone">Phone Number (OTP Verification)</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" autocomplete="tel" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required />

        <label for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter password" autocomplete="new-password" minlength="8" required />

        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
          <div style="display:flex;align-items:flex-start;gap:8px;margin:12px 0 6px;font-size:12px;color:#475569;">
            <input type="checkbox" name="terms" id="terms" required style="width:auto;height:auto;margin:2px 0 0;padding:0;flex-shrink:0;" />
            <label for="terms" style="margin:0;font-weight:500;cursor:pointer;">
              {!! __('I agree to the :terms_of_service and :privacy_policy', [
                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'">'.__('Terms of Service').'</a>',
                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'">'.__('Privacy Policy').'</a>',
              ]) !!}
            </label>
          </div>
        @endif

        <button type="submit" class="signup-submit">Create Free Account <span aria-hidden="true">&rarr;</span></button>
      </form>

      <div class="signup-signin">Already have an account? <a href="{{ route('login') }}">Sign In <span aria-hidden="true">&rarr;</span></a></div>
    </section>
  </div>
</div>
@endsection
