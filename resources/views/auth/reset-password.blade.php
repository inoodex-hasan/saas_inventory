@extends('layouts.landing')

@section('title', 'Reset password | ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout signin-layout">
    <section class="signup-card" aria-labelledby="reset-email-title">
      <header class="signup-heading">
        <h1 id="reset-email-title">Set new password</h1>
        <p>Choose a new password for your account.</p>
      </header>

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 14px;">
          {{ $errors->first() }}
        </p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email">Email Address</label>
        <input
          id="email"
          name="email"
          type="email"
          value="{{ old('email', $request->email) }}"
          required
          autofocus
          autocomplete="username"
        />

        <label for="password">New Password</label>
        <input
          id="password"
          name="password"
          type="password"
          required
          autocomplete="new-password"
          placeholder="At least 8 characters"
        />

        <label for="password_confirmation">Confirm New Password</label>
        <input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          placeholder="Re-type new password"
        />

        <button type="submit" class="signup-submit" style="margin-top:10px;">
          <span>Update Password</span>
          <span aria-hidden="true">&rarr;</span>
        </button>
      </form>

      <div class="signup-signin" style="margin-top:20px;text-align:center;">
        <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#087D61;text-decoration:none;">
          <span aria-hidden="true">&larr;</span> Back to Sign In
        </a>
      </div>
    </section>
  </div>
</div>
@endsection
