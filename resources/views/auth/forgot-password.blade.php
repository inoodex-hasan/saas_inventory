@extends('layouts.landing')

@section('title', 'Forgot password | ' . ($siteSettings['brand_name'] ?? config('app.name', 'InoManage')))

@push('styles')
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}?v={{ @filemtime(public_path('landing/signup.css')) }}" />
@endpush

@section('content')
<div class="signup-page-wrapper">
  <div class="signup-layout signin-layout">
    <section class="signup-card" aria-labelledby="forgot-title">
      <header class="signup-heading">
        <h1 id="forgot-title">Reset your password</h1>
        <p>Choose your preferred method to recover access to your account.</p>
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

      <!-- Switcher Tabs: Phone vs Email -->
      <div style="display:flex;background:#F1F5F9;border-radius:10px;padding:4px;margin-bottom:18px;">
        <button
          type="button"
          id="tab-phone-btn"
          onclick="switchMode('phone')"
          style="flex:1;padding:8px 12px;font-size:13px;font-weight:700;border:0;border-radius:8px;background:#FFFFFF;color:#0F172A;box-shadow:0 1px 3px rgba(0,0,0,0.08);cursor:pointer;transition:all 0.2s;"
        >
          📱 Via Mobile OTP
        </button>
        <button
          type="button"
          id="tab-email-btn"
          onclick="switchMode('email')"
          style="flex:1;padding:8px 12px;font-size:13px;font-weight:600;border:0;border-radius:8px;background:transparent;color:#64748B;cursor:pointer;transition:all 0.2s;"
        >
          ✉️ Via Email Link
        </button>
      </div>

      <!-- Option 1: Mobile SMS OTP Form -->
      <form id="phone-reset-form" class="auth-form" method="POST" action="/forgot-password/phone">
        @csrf
        <label for="phone">Registered Mobile Number</label>
        <input
          id="phone"
          name="phone"
          type="tel"
          value="{{ old('phone') }}"
          placeholder="01XXXXXXXXX"
          autocomplete="tel"
          required
          autofocus
        />
        <p style="margin:-4px 0 14px;font-size:12px;color:#64748b;">
          We will send a 6-digit OTP code to verify your identity.
        </p>

        <button type="submit" class="signup-submit">
          <span>Send Reset OTP Code</span>
          <span aria-hidden="true">&rarr;</span>
        </button>
      </form>

      <!-- Option 2: Email Password Reset (Temporarily Closed notice) -->
      <div id="email-reset-box" style="display:none;">
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px 14px;text-align:center;margin-bottom:16px;">
          <div style="width:36px;height:36px;border-radius:50%;background:#FEF3C7;color:#D97706;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <h2 style="font-size:14px;font-weight:700;color:#92400E;margin-bottom:4px;">Email Reset Temporarily Unavailable</h2>
          <p style="font-size:12px;color:#78350F;line-height:1.5;margin:0 0 14px;">
            Email recovery is temporarily suspended for maintenance. Please use the instant <strong>Mobile OTP</strong> option to recover your account right away.
          </p>
          <button
            type="button"
            onclick="switchMode('phone')"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:0;border-radius:8px;background:#065F46;color:#FFFFFF;font-size:12px;font-weight:700;cursor:pointer;"
          >
            <span>📱 Use Mobile OTP Instead</span>
            <span aria-hidden="true">&rarr;</span>
          </button>
        </div>
      </div>

      <div class="signup-signin" style="margin-top:20px;text-align:center;">
        <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#087D61;text-decoration:none;">
          <span aria-hidden="true">&larr;</span> Back to Sign In
        </a>
      </div>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function switchMode(mode) {
    const phoneForm = document.getElementById('phone-reset-form');
    const emailBox = document.getElementById('email-reset-box');
    const phoneBtn = document.getElementById('tab-phone-btn');
    const emailBtn = document.getElementById('tab-email-btn');
    const phoneInput = document.getElementById('phone');

    if (mode === 'email') {
      phoneForm.style.display = 'none';
      emailBox.style.display = 'block';
      phoneInput.removeAttribute('required');

      emailBtn.style.background = '#FFFFFF';
      emailBtn.style.color = '#0F172A';
      emailBtn.style.fontWeight = '700';
      emailBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)';

      phoneBtn.style.background = 'transparent';
      phoneBtn.style.color = '#64748B';
      phoneBtn.style.fontWeight = '600';
      phoneBtn.style.boxShadow = 'none';
    } else {
      emailBox.style.display = 'none';
      phoneForm.style.display = 'block';
      phoneInput.setAttribute('required', 'required');

      phoneBtn.style.background = '#FFFFFF';
      phoneBtn.style.color = '#0F172A';
      phoneBtn.style.fontWeight = '700';
      phoneBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)';

      emailBtn.style.background = 'transparent';
      emailBtn.style.color = '#64748B';
      emailBtn.style.fontWeight = '600';
      emailBtn.style.boxShadow = 'none';
    }
  }

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
@endpush
