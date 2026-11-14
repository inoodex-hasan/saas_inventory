@extends('layouts.landing')

@section('title', 'Set new password | ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout signin-layout">
    <section class="signup-card" aria-labelledby="reset-title">
      <header class="signup-heading">
        <h1 id="reset-title">Set new password</h1>
        <p>Enter the 6-digit OTP code sent to <strong>{{ $maskedPhone }}</strong> and choose a new password.</p>
      </header>

      @if (session('status'))
        <div id="statusToast" class="signup-status-in-card" role="status" aria-live="polite">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div id="errorToast" class="signup-error-in-card" role="alert" aria-live="polite">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form class="auth-form" method="POST" action="{{ route('password.update.phone') }}">
        @csrf

        <label for="otp">6-Digit Verification OTP</label>
        <input
          id="otp"
          name="otp"
          type="text"
          inputmode="numeric"
          pattern="[0-9]{6}"
          maxlength="6"
          required
          autofocus
          autocomplete="one-time-code"
          placeholder="123456"
          style="text-align:center;font-size:18px;letter-spacing:0.35em;font-weight:700;"
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
          <span>Reset Password &amp; Sign In</span>
          <span aria-hidden="true">&rarr;</span>
        </button>
      </form>

      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:16px;font-size:13px;">
        <form method="POST" action="{{ route('password.reset.phone.resend') }}">
          @csrf
          <button
            id="resend-otp-btn"
            type="submit"
            @if ($cooldown > 0) disabled @endif
            style="background:none;border:0;padding:0;color:#087D61;font-weight:600;cursor:pointer;"
          >
            @if ($cooldown > 0)
              Resend OTP in <span id="otp-countdown">{{ $cooldown }}</span>s
            @else
              Resend OTP
            @endif
          </button>
        </form>

        <a href="{{ route('password.request') }}" style="color:#64748B;text-decoration:underline;">
          Use different number
        </a>
      </div>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Auto fade out status & error toasts
  (function () {
    const statusToast = document.getElementById('statusToast');
    if (statusToast) {
      setTimeout(() => {
        statusToast.classList.add('fade-out');
        setTimeout(() => {
          if (statusToast.parentNode) statusToast.parentNode.removeChild(statusToast);
        }, 500);
      }, 3000);
    }

    const errorToast = document.getElementById('errorToast');
    if (errorToast) {
      setTimeout(() => {
        errorToast.classList.add('fade-out');
        setTimeout(() => {
          if (errorToast.parentNode) errorToast.parentNode.removeChild(errorToast);
        }, 500);
      }, 3500);
    }
  })();
</script>
@if ($cooldown > 0)
  <script>
    (function () {
      let remaining = {{ (int) $cooldown }};
      const btn = document.getElementById('resend-otp-btn');
      const counter = document.getElementById('otp-countdown');
      if (!btn || !counter) return;

      const timer = setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
          clearInterval(timer);
          btn.disabled = false;
          btn.textContent = 'Resend OTP';
        } else {
          counter.textContent = String(remaining);
        }
      }, 1000);
    })();
  </script>
@endif
@endpush
