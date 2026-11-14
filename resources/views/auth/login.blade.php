@extends('layouts.landing')

@section('title', 'Sign In | ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout signin-layout">
    <!-- <a class="signup-brand" href="{{ route('home') }}" aria-label="{{ $siteSettings['brand_name'] ?? 'InoManage' }} home">
      <img src="{{ $siteSettings['logo_url'] ?? asset('assets/images/inomanage.png') }}" alt="{{ $siteSettings['brand_name'] ?? 'InoManage' }}" class="signup-logo-img" width="34" height="34" style="width: 34px; height: 34px; max-width: 34px; max-height: 34px; object-fit: contain; border-radius: 9px;" />
      <span class="signup-wordmark">{{ $siteSettings['brand_name'] ?? 'InoManage' }}</span>
    </a> -->

    <section class="signup-card" aria-labelledby="signin-title">
      <header class="signup-heading">
        <h1 id="signin-title">Welcome back</h1>
        <p>Sign in to your {{ $siteSettings['brand_name'] ?? 'InoManage' }} account</p>
      </header>

      @if (session('status'))
        <p class="signup-status" role="status" aria-live="polite" style="margin-bottom: 12px;">{{ session('status') }}</p>
      @endif

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">
          {{ $errors->first() }}
        </p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="username" required autofocus />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required />

        @if (Route::has('password.request'))
          <a class="auth-forgot" href="{{ route('password.request') }}">Forgot password?</a>
        @endif

        <button type="submit" class="signup-submit">Sign In <span aria-hidden="true">&rarr;</span></button>
      </form>

      @if (Route::has('register'))
        <div class="signup-signin">New to {{ $siteSettings['brand_name'] ?? 'InoManage' }}? <a href="{{ route('register') }}">Create an account <span aria-hidden="true">&rarr;</span></a></div>
      @endif
    </section>
  </div>
</div>
@endsection
