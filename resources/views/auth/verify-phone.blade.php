@extends('layouts.landing')

@section('title', 'Verify your phone — ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

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

    <section class="signup-card" aria-labelledby="verify-title">
      <header class="signup-heading">
        <h1 id="verify-title">Verify your phone number</h1>
        @if ($phone)
          <p>We sent a 6-digit verification code to <strong>{{ $maskedPhone }}</strong>. Enter it below to access your dashboard.</p>
        @else
          <p>Please add and verify your mobile number to activate your free trial and access your dashboard.</p>
        @endif
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

      @if ($phone)
        <form class="auth-form" method="POST" action="{{ route('phone.verify.confirm') }}">
          @csrf
          <label for="otp">6-Digit OTP Code</label>
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
          <button type="submit" class="signup-submit">Verify &amp; Access Dashboard <span aria-hidden="true">&rarr;</span></button>
        </form>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;font-size:13px;">
          <form method="POST" action="{{ route('phone.verify.resend') }}">
            @csrf
            <button
              id="resend-otp-btn"
              type="submit"
              @if ($cooldown > 0) disabled @endif
              style="background:none;border:0;padding:0;color:#0284c7;font-weight:600;cursor:pointer;"
            >
              @if ($cooldown > 0)
                Resend OTP in <span id="otp-countdown">{{ $cooldown }}</span>s
              @else
                Resend OTP
              @endif
            </button>
          </form>

          <button
            type="button"
            onclick="const box = document.getElementById('change-phone-box'); box.style.display = box.style.display === 'none' ? 'block' : 'none';"
            style="background:none;border:0;padding:0;color:#64748b;text-decoration:underline;cursor:pointer;"
          >
            Change phone number
          </button>
        </div>
      @endif

      <div id="change-phone-box" style="{{ $phone ? 'display:none;margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0;' : 'margin-top:8px;' }}">
        <form class="auth-form" method="POST" action="{{ route('phone.verify.update') }}">
          @csrf
          @method('PUT')
          <label for="update_phone">Mobile Number</label>
          <input id="update_phone" name="phone" type="tel" required value="{{ old('phone', $phone) }}" placeholder="017XXXXXXXX" />
          <button type="submit" class="signup-submit" style="margin-top:10px;">
            {{ $phone ? 'Update Number & Send OTP' : 'Send Verification OTP' }}
          </button>
        </form>
      </div>

      <div class="signup-signin">
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
          @csrf
          <button type="submit" style="background:none;border:0;padding:0;color:#64748b;text-decoration:underline;cursor:pointer;font-size:13px;">
            Log Out
          </button>
        </form>
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
        }, 3000);
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
