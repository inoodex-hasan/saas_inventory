@extends('layouts.landing')

@section('title', 'Verify your mobile number — ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout signin-layout">
    <section class="signup-card" aria-labelledby="verify-title">
      <header class="signup-heading">
        <h1 id="verify-title">Verify your mobile phone</h1>
        <p>We sent a 6-digit verification code to <strong>{{ $maskedPhone }}</strong>. Enter it below to activate your free trial and create your workspace.</p>
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
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">{{ $errors->first() }}</p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('register.verify-phone.submit') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />
        
        <label for="otp">6-Digit Verification Code</label>
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
          style="text-align:center;font-size:20px;letter-spacing:0.35em;font-weight:700;"
        />
        <button type="submit" class="signup-submit">Verify &amp; Create Account <span aria-hidden="true">&rarr;</span></button>
      </form>

      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:16px;font-size:13px;">
        <form method="POST" action="{{ route('register.resend-otp') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}" />
          <button
            id="resend-otp-btn"
            type="submit"
            @if ($cooldown > 0) disabled @endif
            style="background:none;border:0;padding:0;color:#0284c7;font-weight:600;cursor:pointer;"
          >
            @if ($cooldown > 0)
              Resend code in <span id="otp-countdown">{{ $cooldown }}</span>s
            @else
              Resend code
            @endif
          </button>
        </form>

        <a href="{{ route('register') }}" style="color:#64748b;text-decoration:underline;cursor:pointer;">
          Edit registration details
        </a>
      </div>

      <div class="signup-signin" style="margin-top:20px;border-top:1px solid #f1f5f9;padding-top:14px;">
        Already verified? <a href="{{ route('login') }}">Sign In &rarr;</a>
      </div>
    </section>
  </div>
</div>
@endsection

@push('scripts')
  <script>
    (function () {
      const toast = document.getElementById('statusToast');
      if (toast) {
        setTimeout(() => {
          toast.classList.add('fade-out');
          setTimeout(() => {
            if (toast.parentNode) {
              toast.parentNode.removeChild(toast);
            }
          }, 500);
        }, 4000);
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
            btn.textContent = 'Resend code';
          } else {
            counter.textContent = String(remaining);
          }
        }, 1000);
      })();
    </script>
  @endif
@endpush
